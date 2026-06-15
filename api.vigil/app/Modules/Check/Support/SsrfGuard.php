<?php

namespace App\Modules\Check\Support;

use Closure;

/**
 * Guards every user-supplied monitor target against SSRF.
 *
 * - assertSafe(): hermetic create-time check (literal IPs + obvious internal
 *   names, no DNS) so blatant targets are rejected at validation.
 * - resolveAndPin(): authoritative run-time check that resolves DNS, rejects if
 *   ANY resolved address is private/loopback/link-local/metadata, and pins one
 *   vetted public IP to connect to (defeats DNS rebinding / TOCTOU).
 * - assertIpSafe(): per-hop re-check used while following HTTP redirects.
 *
 * See PLAN.md §10.
 */
class SsrfGuard
{
    /** @var array<string> Host names that are always internal. */
    private const BLOCKED_HOST_SUFFIXES = ['localhost', '.localhost', '.local', '.internal'];

    /**
     * @param  Closure(string):array<string>|null  $resolver  Override DNS resolution (tests).
     */
    public function __construct(
        private ?Closure $resolver = null,
    ) {}

    /**
     * Hermetic create-time validation. Does not perform DNS resolution.
     */
    public function assertSafe(string $target): void
    {
        $host = $this->extractHost($target);

        if ($host === '') {
            throw new SsrfException('Could not determine a host from the target.');
        }

        if ($this->isBlockedHostname($host)) {
            throw new SsrfException("The target host '{$host}' is not allowed.");
        }

        if (filter_var($host, FILTER_VALIDATE_IP) && ! $this->isPublicIp($host)) {
            throw new SsrfException("The target IP '{$host}' is in a blocked range.");
        }
    }

    /**
     * Authoritative run-time validation: resolve, vet every address, pin one.
     */
    public function resolveAndPin(string $target): PinnedTarget
    {
        $host = $this->extractHost($target);

        if ($host === '' || $this->isBlockedHostname($host)) {
            throw new SsrfException("The target host '{$host}' is not allowed.");
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $this->assertIpSafe($host);

            return new PinnedTarget($target, $host, $host);
        }

        $addresses = $this->resolve($host);

        if (empty($addresses)) {
            throw new SsrfException("Could not resolve host '{$host}'.");
        }

        $pinned = null;

        foreach ($addresses as $address) {
            // Strict: a single blocked address fails the whole target so an
            // attacker cannot mix one public and one internal record.
            $this->assertIpSafe($address, $host);
            $pinned ??= $address;
        }

        return new PinnedTarget($target, $host, $pinned);
    }

    public function assertIpSafe(string $ip, ?string $host = null): void
    {
        if (! $this->isPublicIp($ip)) {
            $where = $host ? "host '{$host}' resolves to" : 'target IP';
            throw new SsrfException("The {$where} a blocked address '{$ip}'.");
        }
    }

    public function isBlockedHostname(string $host): bool
    {
        $lower = strtolower($host);

        foreach (self::BLOCKED_HOST_SUFFIXES as $suffix) {
            if ($lower === $suffix || str_ends_with($lower, $suffix)) {
                return true;
            }
        }

        return false;
    }

    public function isPublicIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->isPublicIpv4($ip);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->isPublicIpv6($ip);
        }

        return false;
    }

    private function isPublicIpv4(string $ip): bool
    {
        $isPrivateOrReserved = ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if ($isPrivateOrReserved) {
            return false;
        }

        // Carrier-grade NAT (100.64.0.0/10) is not covered by the filter flags.
        return ! $this->ipv4InCidr($ip, '100.64.0.0', 10);
    }

    private function isPublicIpv6(string $ip): bool
    {
        $packed = inet_pton($ip);

        if ($packed === false) {
            return false;
        }

        // IPv4-mapped (::ffff:a.b.c.d) — validate the embedded IPv4 address.
        if ($this->ipv6InPrefix($packed, '::ffff:0:0', 96)) {
            return $this->isPublicIpv4(inet_ntop(substr($packed, 12, 4)));
        }

        $blockedPrefixes = [
            ['::1', 128],        // loopback
            ['::', 128],         // unspecified
            ['fc00::', 7],       // unique local
            ['fe80::', 10],      // link-local
            ['fec0::', 10],      // deprecated site-local
            ['ff00::', 8],       // multicast
            ['2001:db8::', 32],  // documentation
            ['fd00:ec2::', 32],  // cloud metadata (IMDS over IPv6)
        ];

        foreach ($blockedPrefixes as [$prefix, $bits]) {
            if ($this->ipv6InPrefix($packed, $prefix, $bits)) {
                return false;
            }
        }

        return true;
    }

    private function ipv4InCidr(string $ip, string $subnet, int $bits): bool
    {
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = -1 << (32 - $bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }

    private function ipv6InPrefix(string $packed, string $prefix, int $bits): bool
    {
        $prefixPacked = inet_pton($prefix);

        if ($prefixPacked === false) {
            return false;
        }

        $wholeBytes = intdiv($bits, 8);
        $remainderBits = $bits % 8;

        if ($wholeBytes > 0 && substr($packed, 0, $wholeBytes) !== substr($prefixPacked, 0, $wholeBytes)) {
            return false;
        }

        if ($remainderBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainderBits)) & 0xFF;

        return (ord($packed[$wholeBytes]) & $mask) === (ord($prefixPacked[$wholeBytes]) & $mask);
    }

    private function extractHost(string $target): string
    {
        $target = trim($target);

        if (str_contains($target, '://')) {
            return (string) (parse_url($target, PHP_URL_HOST) ?? '');
        }

        // Bracketed IPv6 literal, optionally with a port: [::1]:8080
        if (str_starts_with($target, '[')) {
            $end = strpos($target, ']');

            return $end !== false ? substr($target, 1, $end - 1) : '';
        }

        // Bare IPv6 literal (multiple colons, no brackets).
        if (substr_count($target, ':') > 1) {
            return $target;
        }

        // host or host:port
        if (str_contains($target, ':')) {
            return explode(':', $target, 2)[0];
        }

        // Strip any path that may follow a bare host.
        return explode('/', $target, 2)[0];
    }

    private function resolve(string $host): array
    {
        if ($this->resolver !== null) {
            return array_values(($this->resolver)($host));
        }

        $addresses = gethostbynamel($host) ?: [];

        $records = @dns_get_record($host, DNS_AAAA) ?: [];
        foreach ($records as $record) {
            if (! empty($record['ipv6'])) {
                $addresses[] = $record['ipv6'];
            }
        }

        return array_values(array_unique($addresses));
    }
}
