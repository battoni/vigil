import type { VerifyLoginCodePayload, VerifyLoginCodeResponse } from '../interfaces';
import type { ApiResponse } from '@Providers';

export default async function verifyLoginCodeService(
  payload: VerifyLoginCodePayload
): Promise<ApiResponse<VerifyLoginCodeResponse>> {
  console.log(payload);

  return new Promise((resolve) =>
    setTimeout(
      () =>
        resolve({
          data: {
            email: 'mock.user@vigil.dev',
            id: 1,
            name: 'Mock User',
          },
        }),
      500
    )
  );
}
