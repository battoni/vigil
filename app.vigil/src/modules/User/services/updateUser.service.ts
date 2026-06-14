import type { User } from '../interfaces';
import type { UserStatus } from '../types';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export interface UpdateUserPayload {
  name?: string;
  last_name?: string;
  username?: string;
  role_id?: number;
  status?: UserStatus;
  password?: string | null;
  permissions?: Record<string, boolean>;
}

export default async function (id: number, payload: UpdateUserPayload): Promise<ApiResponse<User>> {
  return API({
    endpoint: `auth/users/${id}`,
    method: 'patch',
    payload,
  });
}
