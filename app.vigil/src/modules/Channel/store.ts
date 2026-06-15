import { defineStore } from 'pinia';
import type { Channel } from './interfaces';
import { GetChannelsService } from './services';

interface ChannelState {
  channels: Channel[];
  loading: boolean;
}

export const useChannelStore = defineStore('channel', {
  state: (): ChannelState => ({
    channels: [],
    loading: false,
  }),
  actions: {
    fetchChannels(): Promise<void> {
      this.loading = true;

      return GetChannelsService()
        .then(({ data }) => {
          this.channels = data;
        })
        .finally(() => (this.loading = false));
    },
    addChannel(channel: Channel): void {
      this.channels = [channel, ...this.channels];
    },
    replaceChannel(channel: Channel): void {
      this.channels = this.channels.map((existing) => (existing.id === channel.id ? channel : existing));
    },
    removeChannel(id: string): void {
      this.channels = this.channels.filter((channel) => channel.id !== id);
    },
  },
});
