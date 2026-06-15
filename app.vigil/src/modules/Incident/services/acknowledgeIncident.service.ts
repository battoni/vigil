import type { Incident } from '../interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export default async function (id: string): Promise<ApiResponse<Incident>> {
  return API({
    endpoint: `incidents/${id}/acknowledge`,
    method: 'patch',
  });
}
