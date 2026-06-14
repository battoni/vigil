import type { RequestEmailOtpPayload, RequestEmailOtpResponse } from '../interfaces';
import type { ApiResponse } from '@Providers';

/**
 * Mock for requesting a login / verification code sent to email. Replace with `API({ ... })` when the backend exists.
 */

export default async function requestEmailOtpService(
  payload: RequestEmailOtpPayload
): Promise<ApiResponse<RequestEmailOtpResponse>> {
  console.log(payload);

  return new Promise((resolve) => setTimeout(() => resolve({ data: { sent: true } }), 500));
}
