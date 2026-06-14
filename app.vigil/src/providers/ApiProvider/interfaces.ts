interface PaginatorLinks {
  active: boolean;
  label: string;
  url: string | null;
}

interface PaginateResult<T = any> {
  current_page: number;
  data: T[];
  first_page_url: string;
  from: number;
  last_page: number;
  last_page_url: string;
  links: PaginatorLinks[];
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number;
  total: number;
}

interface ApiParams {
  endpoint: string;
  forwardError?: boolean;
  isUpload?: boolean;
  method?: 'get' | 'post' | 'delete' | 'patch' | 'put';
  payload?: any;
}

interface ApiResponse<T = any> {
  data: T;
}

export type { ApiParams, ApiResponse, PaginateResult };
