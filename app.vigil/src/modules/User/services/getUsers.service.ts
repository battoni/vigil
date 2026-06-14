import type { User } from '../interfaces';
import type { ApiResponse } from '@Providers';
import { API } from '@Providers';

export interface GetUsersParams {
  ids?: number[];
  order_by?: string;
}

export default async function (params: GetUsersParams = {}): Promise<ApiResponse<User[]>> {
  const searchParams = new URLSearchParams();
  if (params.ids?.length) {
    params.ids.forEach((id) => searchParams.append('ids[]', String(id)));
  }

  if (params.order_by) searchParams.set('order_by', params.order_by);
  const query = searchParams.toString();
  const endpoint = query ? `auth/users?${query}` : 'auth/users';

  return API({
    endpoint,
    method: 'get',
  });
}
