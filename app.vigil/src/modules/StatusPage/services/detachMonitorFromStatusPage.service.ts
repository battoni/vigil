import type { StatusPage } from '../interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export default async function (statusPageId: string, monitorId: string): Promise<ApiResponse<StatusPage>> {
  return API({
    endpoint: `status-pages/${statusPageId}/monitors/${monitorId}`,
    method: 'delete',
  });
}
