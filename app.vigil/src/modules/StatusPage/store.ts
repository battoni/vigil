import { defineStore } from 'pinia';
import type { StatusPage } from './interfaces';
import { GetStatusPagesService } from './services';

interface StatusPageState {
  statusPages: StatusPage[];
  loading: boolean;
}

export const useStatusPageStore = defineStore('statusPage', {
  state: (): StatusPageState => ({
    statusPages: [],
    loading: false,
  }),
  actions: {
    fetchStatusPages(): Promise<void> {
      this.loading = true;

      return GetStatusPagesService()
        .then(({ data }) => {
          this.statusPages = data;
        })
        .finally(() => (this.loading = false));
    },
    addStatusPage(statusPage: StatusPage): void {
      this.statusPages = [statusPage, ...this.statusPages];
    },
    replaceStatusPage(statusPage: StatusPage): void {
      this.statusPages = this.statusPages.map((existing) => (existing.id === statusPage.id ? statusPage : existing));
    },
    removeStatusPage(id: string): void {
      this.statusPages = this.statusPages.filter((statusPage) => statusPage.id !== id);
    },
  },
});
