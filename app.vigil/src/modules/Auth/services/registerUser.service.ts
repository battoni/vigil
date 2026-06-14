import type { RegisterUserPayload, RegisterUserResponse } from '../interfaces';
import type { ApiResponse } from '@Providers';

/**
 * Mock for sign-up / complete registration. Replace with `API({ ... })` when the backend exists.
 */

export default async function registerUserService(
  payload: RegisterUserPayload
): Promise<ApiResponse<RegisterUserResponse>> {
  console.log(payload);

  const name = `${payload.firstName} ${payload.lastName}`.trim();

  return new Promise((resolve) =>
    setTimeout(
      () =>
        resolve({
          data: {
            email: 'mock.user@vigil.dev',
            id: 1,
            name: name || 'Mock User',
          },
        }),
      500
    )
  );
}
