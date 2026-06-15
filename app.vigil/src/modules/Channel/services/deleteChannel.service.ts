import type { Channel } from '../interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export default async function (id: string): Promise<ApiResponse<Channel>> {
  return API({
    endpoint: `channels/${id}`,
    method: 'delete',
  });
}
