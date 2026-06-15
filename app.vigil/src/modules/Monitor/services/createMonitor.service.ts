import type { Monitor } from '../interfaces';
import type { MonitorType } from '../types';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export interface CreateMonitorPayload {
  project_id: number;
  name: string;
  type: MonitorType;
  target: string;
  config?: Record<string, unknown>;
  interval_seconds: number;
  timeout_ms?: number;
  confirmation_threshold?: number;
  recovery_threshold?: number;
}

export default async function (payload: CreateMonitorPayload): Promise<ApiResponse<Monitor>> {
  return API({
    endpoint: 'monitors',
    method: 'post',
    payload,
  });
}
