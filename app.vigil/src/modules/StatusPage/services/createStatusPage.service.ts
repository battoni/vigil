import type { StatusPage } from '../interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export interface CreateStatusPagePayload {
  name: string;
  custom_domain?: string | null;
  branding?: Record<string, unknown>;
  is_public?: boolean;
}

export default async function (payload: CreateStatusPagePayload): Promise<ApiResponse<StatusPage>> {
  return API({
    endpoint: 'status-pages',
    method: 'post',
    payload,
  });
}
