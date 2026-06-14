import type { ApiResponse } from '@Providers';
import type { User } from '@UserModule';
import { API } from '@Providers';

interface LoginRequestPayload {
  username: string;
  password: string;
}

export default async function (payload: LoginRequestPayload): Promise<ApiResponse<User>> {
  console.log(payload);

  return API({
    payload,
    endpoint: 'auth/login',
    method: 'post',
  });
}
