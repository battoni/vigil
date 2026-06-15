import { defineStore } from 'pinia';
import type { Incident } from './interfaces';
import { GetIncidentsService } from './services';

interface IncidentState {
  incidents: Incident[];
  loading: boolean;
}

export const useIncidentStore = defineStore('incident', {
  state: (): IncidentState => ({
    incidents: [],
    loading: false,
  }),
  actions: {
    fetchIncidents(params: { monitorId?: string; open?: boolean } = {}): Promise<void> {
      this.loading = true;

      return GetIncidentsService(params)
        .then(({ data }) => {
          this.incidents = data;
        })
        .finally(() => (this.loading = false));
    },
    replaceIncident(incident: Incident): void {
      this.incidents = this.incidents.map((existing) => (existing.id === incident.id ? incident : existing));
    },
  },
});
