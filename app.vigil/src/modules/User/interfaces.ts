import type { Status } from '@Types';

interface User {
  id: number;
  name: string;
  last_name: string;
  username: string;
  role: string;
  role_slug?: string;
  status?: Status | string;
  permissions?: string[];
  profile_picture?: string;
}

export type { User };
