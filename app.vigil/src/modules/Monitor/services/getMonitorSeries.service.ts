import type { MonitorSeriesPoint } from '../interfaces';
import type { UptimeRange } from '../types';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export default async function (id: string, range: UptimeRange = '7d'): Promise<ApiResponse<MonitorSeriesPoint[]>> {
  return API({
    endpoint: `monitors/${id}/uptime-series?range=${range}`,
    method: 'get',
  });
}
