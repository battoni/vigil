import type { MonitorCheck } from '../interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export interface GetMonitorChecksParams {
  since?: string;
}

export default async function (id: string, params: GetMonitorChecksParams = {}): Promise<ApiResponse<MonitorCheck[]>> {
  const query = params.since ? `?since=${encodeURIComponent(params.since)}` : '';

  return API({
    endpoint: `monitors/${id}/checks${query}`,
    method: 'get',
  });
}
