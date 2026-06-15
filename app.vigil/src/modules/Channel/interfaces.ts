import type { ChannelType } from './types';

interface Channel {
  id: string;
  name: string;
  type: ChannelType;
  config: Record<string, unknown>;
  isActive: boolean;
  createdAt?: string;
  updatedAt?: string;
}

export type { Channel };
