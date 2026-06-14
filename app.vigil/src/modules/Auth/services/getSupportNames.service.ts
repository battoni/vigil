import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export default async function (): Promise<ApiResponse<string[]>> {
  return API({
    endpoint: 'auth/support-names',
    method: 'get',
  });
}
