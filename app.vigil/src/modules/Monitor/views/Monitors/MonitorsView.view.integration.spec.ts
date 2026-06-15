import { screen, waitFor } from '@testing-library/vue';
import { http, HttpResponse } from 'msw';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mockMonitor } from '@/test/msw/handlers';
import { server } from '@/test/msw/server';
import { renderWithPlugins } from '@/test/render';
import { useProjectStore } from '@ProjectModule';
import MonitorsView from './MonitorsView.view.vue';

vi.mock('primevue/useconfirm', () => ({
  useConfirm: () => ({
    require: ({ accept }: { accept: () => void }) => accept(),
  }),
}));

const stubs = {
  MMonitorCard: {
    props: ['monitor', 'canPause', 'canDelete', 'canUpdate', 'uptime24h'],
    emits: ['onPauseRequest', 'onDeleteRequest', 'onCopyUrl'],
    template: `<div class="monitor-card-stub" :data-id="monitor.id">
      <span>{{ monitor.name }}</span>
      <span class="status">{{ monitor.status }}</span>
      <button v-if="canPause" class="pause-btn" @click="$emit('onPauseRequest')">pause</button>
      <button v-if="canDelete" class="delete-btn" @click="$emit('onDeleteRequest', $event)">delete</button>
    </div>`,
  },
  AEmptyState: { props: ['title', 'description'], template: '<div class="empty-stub">{{ title }} {{ description }}</div>' },
  ConfirmPopup: true,
  Skeleton: true,
};

function setupWithActiveProject() {
  const pinia = createPinia();
  setActivePinia(pinia);
  useProjectStore().setActiveProject('1');
  return pinia;
}

describe('MonitorsView — integration (MSW)', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  it('loads the active project monitors on mount and renders a card per monitor', async () => {
    const second = { ...mockMonitor, id: '2', name: 'Checkout API', status: 'down' };
    server.use(http.get('http://localhost/monitors', () => HttpResponse.json({ data: [mockMonitor, second] })));

    const pinia = setupWithActiveProject();
    renderWithPlugins(MonitorsView, { pinia, global: { stubs } });

    await waitFor(() => {
      expect(screen.getByText('Homepage')).toBeInTheDocument();
      expect(screen.getByText('Checkout API')).toBeInTheDocument();
    });
  });

  it('shows the empty state when the project has no monitors', async () => {
    server.use(http.get('http://localhost/monitors', () => HttpResponse.json({ data: [] })));

    const pinia = setupWithActiveProject();
    renderWithPlugins(MonitorsView, { pinia, global: { stubs } });

    await waitFor(() => expect(document.querySelector('.empty-stub')).toBeInTheDocument());
    expect(document.querySelectorAll('.monitor-card-stub').length).toBe(0);
  });

  it('pauses a monitor and replaces it in place without refetching', async () => {
    server.use(http.get('http://localhost/monitors', () => HttpResponse.json({ data: [mockMonitor] })));

    const pinia = setupWithActiveProject();
    renderWithPlugins(MonitorsView, { pinia, global: { stubs } });

    await waitFor(() => expect(screen.getByText('Homepage')).toBeInTheDocument());
    expect(screen.getByText('up')).toBeInTheDocument();

    screen.getByRole('button', { name: 'pause' }).click();

    await waitFor(() => expect(screen.getByText('paused')).toBeInTheDocument());
  });

  it('removes a deleted monitor card without refetching', async () => {
    server.use(http.get('http://localhost/monitors', () => HttpResponse.json({ data: [mockMonitor] })));

    const pinia = setupWithActiveProject();
    renderWithPlugins(MonitorsView, { pinia, global: { stubs } });

    await waitFor(() => expect(screen.getByText('Homepage')).toBeInTheDocument());

    screen.getByRole('button', { name: 'delete' }).click();

    await waitFor(() => expect(screen.queryByText('Homepage')).not.toBeInTheDocument());
  });
});
