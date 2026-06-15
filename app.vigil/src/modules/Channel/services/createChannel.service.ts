import type { Channel } from '../interfaces';
import type { ChannelType } from '../types';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export interface CreateChannelPayload {
  name: string;
  type: ChannelType;
  config?: Record<string, unknown>;
  is_active?: boolean;
}

export default async function (payload: CreateChannelPayload): Promise<ApiResponse<Channel>> {
  return API({
    endpoint: 'channels',
    method: 'post',
    payload,
  });
}
