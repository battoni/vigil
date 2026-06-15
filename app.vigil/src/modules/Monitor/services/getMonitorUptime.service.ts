import type { MonitorUptime } from '../interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export default async function (id: string): Promise<ApiResponse<MonitorUptime>> {
  return API({
    endpoint: `monitors/${id}/uptime`,
    method: 'get',
  });
}
