import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const getIncidentsService = vi.fn();

vi.mock('./services', () => ({
  GetIncidentsService: getIncidentsService,
}));

const { useIncidentStore } = await import('./store');

const incidentA = { id: '1', monitorId: '10', isOpen: true, cause: 'timeout' };
const incidentB = { id: '2', monitorId: '10', isOpen: false, cause: 'http_error' };

describe('useIncidentStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    getIncidentsService.mockReset();
  });

  it('initialises empty', () => {
    const store = useIncidentStore();
    expect(store.incidents).toEqual([]);
  });

  it('fetchIncidents loads incidents and clears loading', async () => {
    getIncidentsService.mockResolvedValue({ data: [incidentA, incidentB] });
    const store = useIncidentStore();

    await store.fetchIncidents({ monitorId: '10' });

    expect(getIncidentsService).toHaveBeenCalledWith({ monitorId: '10' });
    expect(store.incidents).toHaveLength(2);
    expect(store.loading).toBe(false);
  });

  it('replaceIncident swaps by id', () => {
    const store = useIncidentStore();
    getIncidentsService.mockResolvedValue({ data: [incidentA] });

    store.incidents = [incidentA as never];
    store.replaceIncident({ ...incidentA, cause: 'acknowledged' } as never);

    expect(store.incidents[0]?.cause).toBe('acknowledged');
  });
});
