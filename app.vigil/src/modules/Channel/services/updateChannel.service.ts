import type { Channel } from '../interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export interface UpdateChannelPayload {
  name?: string;
  config?: Record<string, unknown>;
  is_active?: boolean;
}

export default async function (id: string, payload: UpdateChannelPayload): Promise<ApiResponse<Channel>> {
  return API({
    endpoint: `channels/${id}`,
    method: 'patch',
    payload,
  });
}
