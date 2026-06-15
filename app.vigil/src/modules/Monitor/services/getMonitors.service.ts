import type { Monitor } from '../interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export interface GetMonitorsParams {
  projectId?: string;
}

export default async function (params: GetMonitorsParams = {}): Promise<ApiResponse<Monitor[]>> {
  const query = params.projectId ? `?project_id=${params.projectId}` : '';

  return API({
    endpoint: `monitors${query}`,
    method: 'get',
  });
}
