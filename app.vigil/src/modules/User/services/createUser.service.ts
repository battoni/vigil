import type { User } from '../interfaces';
import type { UserStatus } from '../types';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export interface CreateUserPayload {
  name: string;
  last_name: string;
  username: string;
  role_id: number;
  status: UserStatus;
  password: string;
  permissions?: Record<string, boolean>;
}

export default async function (payload: CreateUserPayload): Promise<ApiResponse<User>> {
  return API({
    endpoint: 'auth/users',
    method: 'post',
    payload,
  });
}
