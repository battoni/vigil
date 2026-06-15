import { screen, waitFor } from '@testing-library/vue';
import { http, HttpResponse } from 'msw';
import { describe, expect, it } from 'vitest';
import { createMemoryHistory, createRouter } from 'vue-router';
import { mockPublicStatus } from '@/test/msw/handlers';
import { server } from '@/test/msw/server';
import { renderWithPlugins } from '@/test/render';
import PublicStatusView from './PublicStatusView.view.vue';

const stubs = {
  Tag: { props: ['severity', 'value'], template: '<span class="tag-stub">{{ value }}</span>' },
  Skeleton: true,
};

async function renderPublic(slug = 'public-status') {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [{ path: '/status/:slug', name: 'public-status-en', component: PublicStatusView, meta: { allowAnonymous: true } }],
  });

  await router.push(`/status/${slug}`);
  await router.isReady();

  return renderWithPlugins(PublicStatusView, { global: { plugins: [router], stubs } });
}

describe('PublicStatusView — integration (MSW)', () => {
  it('renders the public status board (groups + monitors) without auth', async () => {
    await renderPublic('public-status');

    await waitFor(() => {
      expect(screen.getByText('Public Status')).toBeInTheDocument();
      expect(screen.getByText('Core')).toBeInTheDocument();
      expect(screen.getByText('Homepage')).toBeInTheDocument();
    });
  });

  it('shows the operational banner', async () => {
    await renderPublic('public-status');

    await waitFor(() =>
      expect(document.querySelector('[data-testid="overall-status"]')?.textContent).toMatch(/operational/i)
    );
  });

  it('shows a degraded banner when overall status is degraded', async () => {
    server.use(
      http.get('http://localhost/status/:slug', () =>
        HttpResponse.json({ data: { ...mockPublicStatus, overallStatus: 'degraded' } })
      )
    );

    await renderPublic('public-status');

    await waitFor(() =>
      expect(document.querySelector('[data-testid="overall-status"]')?.textContent).toMatch(/degraded/i)
    );
  });

  it('shows a not-found message for an unknown slug', async () => {
    server.use(http.get('http://localhost/status/:slug', () => HttpResponse.error()));

    await renderPublic('nope');

    await waitFor(() => expect(screen.getByText(/not found/i)).toBeInTheDocument());
  });
});
