<?php

namespace App\Modules\StatusPage\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\StatusPage\Repositories\StatusPageRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Caddy on-demand TLS "ask" endpoint. Caddy queries this before minting a
 * certificate for an incoming custom domain; we return 200 only for a domain
 * registered as a status_pages.custom_domain, otherwise 404. Without this gate,
 * on-demand TLS would mint certs for any domain pointed at the box. See PLAN.md §10.
 */
class TlsAllowedController extends Controller
{
    public function __construct(
        private StatusPageRepository $statusPageRepository
    ) {}

    public function check(Request $request): Response
    {
        $domain = (string) $request->query('domain', '');

        if ($domain !== '' && $this->statusPageRepository->existsByCustomDomain($domain)) {
            return response('', 200);
        }

        return response('', 404);
    }
}
