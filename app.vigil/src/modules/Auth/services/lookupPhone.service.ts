import type { LookupPhonePayload, LookupPhoneResponse } from '../interfaces';
import type { ApiResponse } from '@Providers';

/**
 * Mock for `POST auth/lookup-phone` (or equivalent). Replace with `API({ ... })` when the backend exists.
 * Same rule as the former helper: only `99999999999` (digits) is treated as an existing account.
 */
const MOCK_EXISTING_PHONE_DIGITS = '99999999999';

export default async function lookupPhoneService(
  payload: LookupPhonePayload
): Promise<ApiResponse<LookupPhoneResponse>> {
  console.log(payload);

  const digits = payload.phone.replace(/\D/g, '');
  const hasAccount = digits === MOCK_EXISTING_PHONE_DIGITS;

  return new Promise((resolve) => setTimeout(() => resolve({ data: { hasAccount } }), 500));
}
