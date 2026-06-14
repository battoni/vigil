import type { ApiParams } from './interfaces';
import { useGlobalAbortController } from '@Composables';
import { API_URL } from '@Constants';
import { axios } from '@Libraries';

const DEFAULT_AXIOS_OPTIONS = {
  validateStatus: (status: number) => status >= 200 && status < 300,
};

export default async function (params: ApiParams) {
  const { method, payload, isUpload, forwardError } = params;

  const endpoint = `${API_URL}/${params.endpoint}`;

  const { getSignal } = useGlobalAbortController();

  const axiosOptions = {
    ...DEFAULT_AXIOS_OPTIONS,
    method,
    data: payload,
    forwardError,
    headers: { 'Content-Type': isUpload ? 'multipart/form-data' : 'application/json' },
    signal: getSignal(),
  };

  return axios(endpoint, { ...axiosOptions }).then(({ data }: any) => data);
}
