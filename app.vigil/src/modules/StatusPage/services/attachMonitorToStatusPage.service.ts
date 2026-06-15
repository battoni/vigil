import type { StatusPage } from '../interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export interface AttachMonitorPayload {
  group_name?: string | null;
  sort_order?: number;
}

export default async function (
  statusPageId: string,
  monitorId: string,
  payload: AttachMonitorPayload = {}
): Promise<ApiResponse<StatusPage>> {
  return API({
    endpoint: `status-pages/${statusPageId}/monitors/${monitorId}`,
    method: 'post',
    payload,
  });
}
