import type { Profile } from '../views/RolesAndPermissions/interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export default async function (): Promise<ApiResponse<Profile[]>> {
  return API({
    endpoint: 'auth/roles',
    method: 'get',
  });
}
