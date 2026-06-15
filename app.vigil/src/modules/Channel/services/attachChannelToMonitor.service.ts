import type { Channel } from '../interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export interface AttachChannelPayload {
  min_severity?: string | null;
  notify_on_recovery?: boolean;
}

export default async function (
  channelId: string,
  monitorId: string,
  payload: AttachChannelPayload = {}
): Promise<ApiResponse<Channel>> {
  return API({
    endpoint: `channels/${channelId}/monitors/${monitorId}`,
    method: 'post',
    payload,
  });
}
