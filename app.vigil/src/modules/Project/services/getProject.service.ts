import type { Project } from '../interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export default async function (id: string): Promise<ApiResponse<Project>> {
  return API({
    endpoint: `projects/${id}`,
    method: 'get',
  });
}
