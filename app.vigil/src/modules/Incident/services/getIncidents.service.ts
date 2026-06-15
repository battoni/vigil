import type { Incident } from '../interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export interface GetIncidentsParams {
  monitorId?: string;
  open?: boolean;
}

export default async function (params: GetIncidentsParams = {}): Promise<ApiResponse<Incident[]>> {
  const searchParams = new URLSearchParams();

  if (params.monitorId) {
    searchParams.set('monitor_id', params.monitorId);
  }

  if (params.open !== undefined) {
    searchParams.set('open', params.open ? '1' : '0');
  }

  const query = searchParams.toString();
  const endpoint = query ? `incidents?${query}` : 'incidents';

  return API({
    endpoint,
    method: 'get',
  });
}
