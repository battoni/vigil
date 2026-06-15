import { defineStore } from 'pinia';
import type { Monitor } from './interfaces';
import { GetMonitorsService } from './services';

interface MonitorState {
  monitors: Monitor[];
  loadedProjectId: string | null;
  loading: boolean;
}

export const useMonitorStore = defineStore('monitor', {
  state: (): MonitorState => ({
    monitors: [],
    loadedProjectId: null,
    loading: false,
  }),
  actions: {
    fetchForProject(projectId: string): Promise<void> {
      this.loading = true;

      return GetMonitorsService({ projectId })
        .then(({ data }) => {
          this.monitors = data;
          this.loadedProjectId = projectId;
        })
        .finally(() => (this.loading = false));
    },
    addMonitor(monitor: Monitor): void {
      this.monitors = [monitor, ...this.monitors];
    },
    replaceMonitor(monitor: Monitor): void {
      this.monitors = this.monitors.map((existing) => (existing.id === monitor.id ? monitor : existing));
    },
    removeMonitor(id: string): void {
      this.monitors = this.monitors.filter((monitor) => monitor.id !== id);
    },
  },
});
