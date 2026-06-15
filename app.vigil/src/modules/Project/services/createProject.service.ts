import type { Project } from '../interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export interface CreateProjectPayload {
  name: string;
}

export default async function (payload: CreateProjectPayload): Promise<ApiResponse<Project>> {
  return API({
    endpoint: 'projects',
    method: 'post',
    payload,
  });
}
