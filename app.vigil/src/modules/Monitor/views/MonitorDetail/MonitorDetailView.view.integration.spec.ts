import { screen, waitFor } from '@testing-library/vue';
import { http, HttpResponse } from 'msw';
import { describe, expect, it } from 'vitest';
import { createMemoryHistory, createRouter } from 'vue-router';
import { mockMonitor } from '@/test/msw/handlers';
import { server } from '@/test/msw/server';
import { renderWithPlugins } from '@/test/render';
import MonitorDetailView from './MonitorDetailView.view.vue';

const stubs = {
  Tag: { props: ['severity', 'value'], template: '<span class="tag-stub">{{ value }}</span>' },
  Button: { props: ['label'], template: '<button>{{ label }}</button>' },
  Skeleton: true,
};

async function renderDetail(id = '1') {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/monitors/:id', name: 'monitor-detail-en', component: MonitorDetailView },
      { path: '/monitors', name: 'monitors-en', component: { template: '<div />' } },
    ],
  });

  await router.push(`/monitors/${id}`);
  await router.isReady();

  return renderWithPlugins(MonitorDetailView, { global: { plugins: [router], stubs } });
}

describe('MonitorDetailView — integration (MSW)', () => {
  it('renders the monitor header from the API', async () => {
    await renderDetail('1');

    await waitFor(() => {
      expect(screen.getByText('Homepage')).toBeInTheDocument();
      expect(screen.getByText('https://example.com')).toBeInTheDocument();
    });
  });

  it('renders the four uptime summary cards from rollups', async () => {
    await renderDetail('1');

    await waitFor(() => expect(screen.getByText('99.9%')).toBeInTheDocument());
    expect(document.querySelector('[data-testid="uptime-cards"]')?.children.length).toBe(4);
  });

  it('shows a not-found message when the monitor is missing', async () => {
    server.use(http.get('http://localhost/monitors/:id', () => HttpResponse.error()));

    await renderDetail('999');

    await waitFor(() => expect(screen.getByText(/not found/i)).toBeInTheDocument());
  });

  it('shows the heartbeat ping URL for heartbeat monitors', async () => {
    server.use(
      http.get('http://localhost/monitors/:id', () =>
        HttpResponse.json({
          data: { ...mockMonitor, type: 'heartbeat', heartbeatUrl: 'https://vigil.test/api/heartbeats/tok' },
        })
      )
    );

    await renderDetail('1');

    await waitFor(() => expect(screen.getByText('https://vigil.test/api/heartbeats/tok')).toBeInTheDocument());
  });
});
