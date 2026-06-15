import { waitFor } from '@testing-library/vue';
import { http, HttpResponse } from 'msw';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mockStatusPage } from '@/test/msw/handlers';
import { server } from '@/test/msw/server';
import { renderWithPlugins } from '@/test/render';
import StatusPagesView from './StatusPagesView.view.vue';

vi.mock('primevue/useconfirm', () => ({
  useConfirm: () => ({ require: ({ accept }: { accept: () => void }) => accept() }),
}));

const stubs = {
  DataTable: { props: ['value'], inheritAttrs: true, template: '<div class="dt-stub" :data-rows="value.length"><slot /><slot name="empty" /></div>' },
  Column: { template: '<div><slot name="body" :data="{ id: \'1\', name: \'Public Status\', slug: \'public-status\', isPublic: true }" /></div>' },
  Tag: { props: ['value'], template: '<span>{{ value }}</span>' },
  Button: { props: ['label'], emits: ['click'], inheritAttrs: true, template: '<button class="btn-stub" @click="$emit(\'click\', $event)">{{ label }}</button>' },
  MMainDialog: { props: ['visible'], template: '<div v-if="visible" class="dialog-stub"><slot /></div>' },
  MAddEditStatusPageForm: {
    props: ['statusPage'],
    emits: ['onClose', 'onSuccess'],
    template: '<div class="sp-form-stub"><button class="form-submit" @click="$emit(\'onSuccess\', { id: \'99\', name: \'New Page\', slug: \'new-page\', isPublic: true })">submit</button></div>',
  },
  ConfirmPopup: true,
};

function setup() {
  const pinia = createPinia();
  setActivePinia(pinia);
  return pinia;
}

describe('StatusPagesView — integration (MSW)', () => {
  beforeEach(() => localStorage.clear());

  it('lists status pages on mount', async () => {
    const second = { ...mockStatusPage, id: '2', name: 'Internal', slug: 'internal' };
    server.use(http.get('http://localhost/status-pages', () => HttpResponse.json({ data: [mockStatusPage, second] })));

    renderWithPlugins(StatusPagesView, { pinia: setup(), global: { stubs } });

    await waitFor(() => expect(document.querySelector('[data-testid="status-pages-table"]')?.getAttribute('data-rows')).toBe('2'));
  });

  it('prepends a created status page without refetching', async () => {
    server.use(http.get('http://localhost/status-pages', () => HttpResponse.json({ data: [mockStatusPage] })));

    renderWithPlugins(StatusPagesView, { pinia: setup(), global: { stubs } });

    await waitFor(() => expect(document.querySelector('[data-testid="status-pages-table"]')?.getAttribute('data-rows')).toBe('1'));

    (document.querySelector('[data-testid="add-status-page"]') as HTMLButtonElement).click();
    await waitFor(() => expect(document.querySelector('.form-submit')).toBeInTheDocument());
    (document.querySelector('.form-submit') as HTMLButtonElement).click();

    await waitFor(() => expect(document.querySelector('[data-testid="status-pages-table"]')?.getAttribute('data-rows')).toBe('2'));
  });

  it('opens manage monitors and attaches a monitor', async () => {
    server.use(http.get('http://localhost/status-pages', () => HttpResponse.json({ data: [mockStatusPage] })));

    renderWithPlugins(StatusPagesView, { pinia: setup(), global: { stubs } });

    await waitFor(() => expect(document.querySelector('[data-testid="status-pages-table"]')).toBeInTheDocument());

    (document.querySelector('[data-testid="manage-status-monitors"]') as HTMLButtonElement).click();

    function manageButton(label: string): HTMLButtonElement | undefined {
      return Array.from(document.querySelectorAll('[data-testid="manage-monitors"] .btn-stub')).find((button) =>
        button.textContent?.includes(label)
      ) as HTMLButtonElement | undefined;
    }

    await waitFor(() => expect(manageButton('Add')).toBeTruthy());
    manageButton('Add')!.click();

    await waitFor(() => expect(manageButton('Remove')).toBeTruthy());
  });
});
