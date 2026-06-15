import { waitFor } from '@testing-library/vue';
import { http, HttpResponse } from 'msw';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mockChannel } from '@/test/msw/handlers';
import { server } from '@/test/msw/server';
import { renderWithPlugins } from '@/test/render';
import ChannelsView from './ChannelsView.view.vue';

vi.mock('primevue/useconfirm', () => ({
  useConfirm: () => ({ require: ({ accept }: { accept: () => void }) => accept() }),
}));

const stubs = {
  DataTable: { props: ['value'], inheritAttrs: true, template: '<div class="dt-stub" :data-rows="value.length"><slot /><slot name="empty" /></div>' },
  Column: { template: '<div><slot :data="{ id: \'1\', name: \'Ops Slack\', type: \'slack\', isActive: true }" /></div>' },
  Tag: { props: ['value'], template: '<span>{{ value }}</span>' },
  Button: { props: ['label'], emits: ['click'], template: '<button class="btn-stub" @click="$emit(\'click\', $event)">{{ label }}</button>' },
  MMainDialog: { props: ['visible'], template: '<div v-if="visible" class="dialog-stub"><slot /></div>' },
  MAddEditChannelForm: {
    props: ['channel'],
    emits: ['onClose', 'onSuccess'],
    template: '<div class="channel-form-stub"><button class="form-submit" @click="$emit(\'onSuccess\', { id: \'99\', name: \'New Channel\', type: \'slack\', isActive: true })">submit</button></div>',
  },
  ConfirmPopup: true,
};

function setup() {
  const pinia = createPinia();
  setActivePinia(pinia);
  return pinia;
}

describe('ChannelsView — integration (MSW)', () => {
  beforeEach(() => localStorage.clear());

  it('lists channels on mount', async () => {
    const second = { ...mockChannel, id: '2', name: 'Alerts Email', type: 'email' };
    server.use(http.get('http://localhost/channels', () => HttpResponse.json({ data: [mockChannel, second] })));

    renderWithPlugins(ChannelsView, { pinia: setup(), global: { stubs } });

    await waitFor(() => expect(document.querySelector('[data-testid="channels-table"]')?.getAttribute('data-rows')).toBe('2'));
  });

  it('prepends a created channel without refetching', async () => {
    server.use(http.get('http://localhost/channels', () => HttpResponse.json({ data: [mockChannel] })));

    renderWithPlugins(ChannelsView, { pinia: setup(), global: { stubs } });

    await waitFor(() => expect(document.querySelector('[data-testid="channels-table"]')?.getAttribute('data-rows')).toBe('1'));

    (document.querySelector('[data-testid="add-channel"]') as HTMLButtonElement).click();
    await waitFor(() => expect(document.querySelector('.form-submit')).toBeInTheDocument());
    (document.querySelector('.form-submit') as HTMLButtonElement).click();

    await waitFor(() => expect(document.querySelector('[data-testid="channels-table"]')?.getAttribute('data-rows')).toBe('2'));
  });
});
