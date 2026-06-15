import type { PublicStatus } from '../interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export default async function (slug: string): Promise<ApiResponse<PublicStatus>> {
  return API({
    endpoint: `status/${slug}`,
    method: 'get',
  });
}
