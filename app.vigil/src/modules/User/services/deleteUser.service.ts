import type { User } from '../interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export default async function (id: number): Promise<ApiResponse<User>> {
  return API({
    endpoint: `auth/users/${id}`,
    method: 'delete',
  });
}
