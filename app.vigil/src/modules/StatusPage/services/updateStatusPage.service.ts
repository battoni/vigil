import type { StatusPage } from '../interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export interface UpdateStatusPagePayload {
  name?: string;
  custom_domain?: string | null;
  branding?: Record<string, unknown>;
  is_public?: boolean;
}

export default async function (id: string, payload: UpdateStatusPagePayload): Promise<ApiResponse<StatusPage>> {
  return API({
    endpoint: `status-pages/${id}`,
    method: 'patch',
    payload,
  });
}
