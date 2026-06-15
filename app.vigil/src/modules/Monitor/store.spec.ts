import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const getMonitorsService = vi.fn();

vi.mock('./services', () => ({
  GetMonitorsService: getMonitorsService,
}));

const { useMonitorStore } = await import('./store');

const monitorA = { id: '1', projectId: '10', name: 'API', status: 'up' };
const monitorB = { id: '2', projectId: '10', name: 'Web', status: 'down' };

describe('useMonitorStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    getMonitorsService.mockReset();
  });

  it('initialises empty', () => {
    const store = useMonitorStore();
    expect(store.monitors).toEqual([]);
    expect(store.loadedProjectId).toBeNull();
  });

  it('fetchForProject loads monitors and records the project', async () => {
    getMonitorsService.mockResolvedValue({ data: [monitorA, monitorB] });
    const store = useMonitorStore();

    await store.fetchForProject('10');

    expect(getMonitorsService).toHaveBeenCalledWith({ projectId: '10' });
    expect(store.monitors).toHaveLength(2);
    expect(store.loadedProjectId).toBe('10');
    expect(store.loading).toBe(false);
  });

  it('addMonitor prepends granularly', () => {
    const store = useMonitorStore();
    store.addMonitor(monitorA as never);
    store.addMonitor(monitorB as never);
    expect(store.monitors.map((monitor) => monitor.id)).toEqual(['2', '1']);
  });

  it('replaceMonitor swaps by id', () => {
    const store = useMonitorStore();
    store.addMonitor(monitorA as never);
    store.replaceMonitor({ ...monitorA, name: 'API v2' } as never);
    expect(store.monitors[0]?.name).toBe('API v2');
  });

  it('removeMonitor drops by id', () => {
    const store = useMonitorStore();
    store.addMonitor(monitorA as never);
    store.addMonitor(monitorB as never);
    store.removeMonitor('1');
    expect(store.monitors.map((monitor) => monitor.id)).toEqual(['2']);
  });
});
