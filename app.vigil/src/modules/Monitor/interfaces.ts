import type { MonitorStatus, MonitorType, UptimeRange } from './types';

interface Monitor {
  id: string;
  projectId: string;
  name: string;
  type: MonitorType;
  target: string;
  config: Record<string, unknown>;
  intervalSeconds: number;
  timeoutMs: number;
  confirmationThreshold: number;
  recoveryThreshold: number;
  status: MonitorStatus;
  consecutiveFailures: number;
  consecutiveSuccesses: number;
  heartbeatToken?: string;
  heartbeatUrl?: string;
  lastPingAt?: string | null;
  lastCheckedAt?: string | null;
  nextCheckAt?: string | null;
  createdAt?: string;
  updatedAt?: string;
}

type MonitorUptime = Record<UptimeRange, number | null>;

interface MonitorCheck {
  id: string;
  result: 'up' | 'down';
  responseTimeMs: number | null;
  statusCode: number | null;
  error: string | null;
  region: string;
  checkedAt: string;
}

interface MonitorSeriesPoint {
  bucketStart: string;
  uptimeRatio: number;
  checksTotal: number;
  p50Ms: number | null;
  p95Ms: number | null;
  maxMs: number | null;
}

export type { Monitor, MonitorCheck, MonitorSeriesPoint, MonitorUptime };
