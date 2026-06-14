import type { Profile } from '../views/RolesAndPermissions/interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export default async function (id: string): Promise<ApiResponse<Profile>> {
  return API({
    endpoint: `auth/roles/${id}`,
    method: 'get',
  });
}
