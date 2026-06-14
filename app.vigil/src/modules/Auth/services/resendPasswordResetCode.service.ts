import type { ResendPasswordResetPayload, ResendPasswordResetResponse } from '../interfaces';
import type { ApiResponse } from '@Providers';

/**
 * Mock: resend reset code. Replace with `API({ ... })` when the backend exists.
 */

export default async function resendPasswordResetCodeService(
  payload: ResendPasswordResetPayload
): Promise<ApiResponse<ResendPasswordResetResponse>> {
  console.log(payload);

  return new Promise((resolve) =>
    setTimeout(
      () =>
        resolve({
          data: {
            ok: true,
          },
        }),
      300
    )
  );
}
