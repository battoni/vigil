import { screen, waitFor } from '@testing-library/vue';
import { http, HttpResponse } from 'msw';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import { mockIncident } from '@/test/msw/handlers';
import { server } from '@/test/msw/server';
import { renderWithPlugins } from '@/test/render';
import IncidentsView from './IncidentsView.view.vue';

const stubs = {
  DataTable: { props: ['value'], inheritAttrs: true, template: '<div class="dt-stub" :data-rows="value.length"><slot /><slot name="empty" /></div>' },
  Column: { template: '<div><slot :data="{ isOpen: true, acknowledgedAt: null }" /></div>' },
  Tag: { props: ['value'], template: '<span class="tag-stub">{{ value }}</span>' },
  Button: { props: ['label'], emits: ['click'], template: '<button class="ack-btn" @click="$emit(\'click\')">{{ label }}</button>' },
  SelectButton: true,
};

function setup() {
  const pinia = createPinia();
  setActivePinia(pinia);
  return pinia;
}

describe('IncidentsView — integration (MSW)', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  it('loads and renders incidents on mount', async () => {
    const second = { ...mockIncident, id: '2', monitorName: 'Checkout' };
    server.use(http.get('http://localhost/incidents', () => HttpResponse.json({ data: [mockIncident, second] })));

    const pinia = setup();
    renderWithPlugins(IncidentsView, { pinia, global: { stubs } });

    await waitFor(() => expect(document.querySelector('[data-testid="incidents-table"]')?.getAttribute('data-rows')).toBe('2'));
  });

  it('shows the empty state when there are no incidents', async () => {
    server.use(http.get('http://localhost/incidents', () => HttpResponse.json({ data: [] })));

    const pinia = setup();
    renderWithPlugins(IncidentsView, { pinia, global: { stubs } });

    await waitFor(() => expect(screen.getByText('No incidents')).toBeInTheDocument());
  });
});
