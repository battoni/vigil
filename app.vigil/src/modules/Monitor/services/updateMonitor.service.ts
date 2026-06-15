import type { Monitor } from '../interfaces';
import type { MonitorStatus } from '../types';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export interface UpdateMonitorPayload {
  name?: string;
  target?: string;
  config?: Record<string, unknown>;
  interval_seconds?: number;
  timeout_ms?: number;
  confirmation_threshold?: number;
  recovery_threshold?: number;
  status?: MonitorStatus;
}

export default async function (id: string, payload: UpdateMonitorPayload): Promise<ApiResponse<Monitor>> {
  return API({
    endpoint: `monitors/${id}`,
    method: 'patch',
    payload,
  });
}
