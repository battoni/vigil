import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const getStatusPagesService = vi.fn();

vi.mock('./services', () => ({
  GetStatusPagesService: getStatusPagesService,
}));

const { useStatusPageStore } = await import('./store');

const pageA = { id: '1', name: 'Public', slug: 'public', customDomain: null, branding: {}, isPublic: true };
const pageB = { id: '2', name: 'Internal', slug: 'internal', customDomain: null, branding: {}, isPublic: false };

describe('useStatusPageStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    getStatusPagesService.mockReset();
  });

  it('initialises empty', () => {
    expect(useStatusPageStore().statusPages).toEqual([]);
  });

  it('fetchStatusPages loads and clears loading', async () => {
    getStatusPagesService.mockResolvedValue({ data: [pageA, pageB] });
    const store = useStatusPageStore();

    await store.fetchStatusPages();

    expect(store.statusPages).toHaveLength(2);
    expect(store.loading).toBe(false);
  });

  it('addStatusPage prepends granularly', () => {
    const store = useStatusPageStore();
    store.addStatusPage(pageA as never);
    store.addStatusPage(pageB as never);
    expect(store.statusPages.map((page) => page.id)).toEqual(['2', '1']);
  });

  it('replaceStatusPage swaps by id', () => {
    const store = useStatusPageStore();
    store.addStatusPage(pageA as never);
    store.replaceStatusPage({ ...pageA, name: 'Renamed' } as never);
    expect(store.statusPages[0]?.name).toBe('Renamed');
  });

  it('removeStatusPage drops by id', () => {
    const store = useStatusPageStore();
    store.addStatusPage(pageA as never);
    store.addStatusPage(pageB as never);
    store.removeStatusPage('1');
    expect(store.statusPages.map((page) => page.id)).toEqual(['2']);
  });
});
