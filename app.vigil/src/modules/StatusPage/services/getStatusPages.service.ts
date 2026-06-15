import type { StatusPage } from '../interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export default async function (): Promise<ApiResponse<StatusPage[]>> {
  return API({
    endpoint: 'status-pages',
    method: 'get',
  });
}
