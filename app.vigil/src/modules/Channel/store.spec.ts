import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const getChannelsService = vi.fn();

vi.mock('./services', () => ({
  GetChannelsService: getChannelsService,
}));

const { useChannelStore } = await import('./store');

const channelA = { id: '1', name: 'Slack', type: 'slack', config: {}, isActive: true };
const channelB = { id: '2', name: 'Email', type: 'email', config: {}, isActive: true };

describe('useChannelStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    getChannelsService.mockReset();
  });

  it('initialises empty', () => {
    expect(useChannelStore().channels).toEqual([]);
  });

  it('fetchChannels loads channels and clears loading', async () => {
    getChannelsService.mockResolvedValue({ data: [channelA, channelB] });
    const store = useChannelStore();

    await store.fetchChannels();

    expect(store.channels).toHaveLength(2);
    expect(store.loading).toBe(false);
  });

  it('addChannel prepends granularly', () => {
    const store = useChannelStore();
    store.addChannel(channelA as never);
    store.addChannel(channelB as never);
    expect(store.channels.map((channel) => channel.id)).toEqual(['2', '1']);
  });

  it('replaceChannel swaps by id', () => {
    const store = useChannelStore();
    store.addChannel(channelA as never);
    store.replaceChannel({ ...channelA, name: 'Renamed' } as never);
    expect(store.channels[0]?.name).toBe('Renamed');
  });

  it('removeChannel drops by id', () => {
    const store = useChannelStore();
    store.addChannel(channelA as never);
    store.addChannel(channelB as never);
    store.removeChannel('1');
    expect(store.channels.map((channel) => channel.id)).toEqual(['2']);
  });
});
