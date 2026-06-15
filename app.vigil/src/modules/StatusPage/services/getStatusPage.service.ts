import type { StatusPage } from '../interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export default async function (id: string): Promise<ApiResponse<StatusPage>> {
  return API({
    endpoint: `status-pages/${id}`,
    method: 'get',
  });
}
