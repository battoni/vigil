import type { ConfirmPasswordResetPayload, ConfirmPasswordResetResponse } from '../interfaces';
import type { ApiResponse } from '@Providers';

/**
 * Mock: confirm reset with code and new password. Replace with `API({ ... })` when the backend exists.
 */

export default async function confirmPasswordResetService(
  payload: ConfirmPasswordResetPayload
): Promise<ApiResponse<ConfirmPasswordResetResponse>> {
  console.log(payload);

  return new Promise((resolve) =>
    setTimeout(
      () =>
        resolve({
          data: {
            ok: true,
          },
        }),
      400
    )
  );
}
