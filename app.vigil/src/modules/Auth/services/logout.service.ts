import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export default async function (): Promise<ApiResponse<null>> {
  return API({
    endpoint: 'auth/logout',
    method: 'post',
  });
}
