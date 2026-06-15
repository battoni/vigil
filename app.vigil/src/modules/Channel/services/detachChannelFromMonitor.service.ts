import type { Channel } from '../interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export default async function (channelId: string, monitorId: string): Promise<ApiResponse<Channel>> {
  return API({
    endpoint: `channels/${channelId}/monitors/${monitorId}`,
    method: 'delete',
  });
}
