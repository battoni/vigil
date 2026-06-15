import type { Monitor } from '../interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export default async function (id: string): Promise<ApiResponse<Monitor>> {
  return API({
    endpoint: `monitors/${id}`,
    method: 'get',
  });
}
