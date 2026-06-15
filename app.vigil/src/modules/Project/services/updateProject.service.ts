import type { Project } from '../interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export interface UpdateProjectPayload {
  name?: string;
}

export default async function (id: string, payload: UpdateProjectPayload): Promise<ApiResponse<Project>> {
  return API({
    endpoint: `projects/${id}`,
    method: 'patch',
    payload,
  });
}
