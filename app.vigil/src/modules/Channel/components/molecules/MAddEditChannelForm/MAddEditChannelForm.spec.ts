import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mountWithPlugins } from '@/test/mount';

const { createChannel, updateChannel } = vi.hoisted(() => ({
  createChannel: vi.fn(() => Promise.resolve({ data: { id: '99', name: 'Ops' } })),
  updateChannel: vi.fn(() => Promise.resolve({ data: { id: '1', name: 'Ops' } })),
}));

vi.mock('../../../services', () => ({
  CreateChannelService: createChannel,
  UpdateChannelService: updateChannel,
}));

const { default: MAddEditChannelForm } = await import('./MAddEditChannelForm.vue');

const formStub = {
  emits: ['submit'],
  template:
    "<form class=\"form-stub\" @submit.prevent=\"$emit('submit', { valid: true, values: { name: 'Ops' } })\"><slot /></form>",
};
const fieldStub = { template: '<div><slot :error="undefined" /></div>' };

function mountForm(props: Record<string, unknown>) {
  return mountWithPlugins(MAddEditChannelForm, {
    props: props as never,
    global: {
      stubs: { Form: formStub, FormField: fieldStub, Select: true, InputText: true, Button: true, AFormError: true },
    },
  });
}

describe('MAddEditChannelForm', () => {
  beforeEach(() => {
    createChannel.mockClear();
    updateChannel.mockClear();
  });

  it('creates a slack channel with a snake_case payload and webhook config', async () => {
    const wrapper = mountForm({ channel: null });

    await wrapper.find('.form-stub').trigger('submit');

    expect(createChannel).toHaveBeenCalledWith(
      expect.objectContaining({ name: 'Ops', type: 'slack', is_active: true })
    );
    const [payload] = createChannel.mock.calls[0] as unknown as [{ config: Record<string, unknown> }];
    expect(payload.config).toHaveProperty('webhook_url');
  });

  it('builds an email config with recipients array and resend key', async () => {
    const channel = {
      id: '2',
      name: 'Mail',
      type: 'email',
      config: { recipients: ['a@x.com', 'b@x.com'], resend_api_key: 'rk', from: 'alerts@x.com' },
      isActive: true,
    };
    const wrapper = mountForm({ channel });

    await wrapper.find('.form-stub').trigger('submit');

    const [, payload] = updateChannel.mock.calls[0] as unknown as [string, { config: Record<string, unknown> }];
    expect(payload.config.recipients).toEqual(['a@x.com', 'b@x.com']);
    expect(payload.config).toHaveProperty('resend_api_key', 'rk');
  });

  it('emits onSuccess with the saved channel', async () => {
    const wrapper = mountForm({ channel: null });

    await wrapper.find('.form-stub').trigger('submit');
    await Promise.resolve();

    expect(wrapper.emitted('onSuccess')).toBeTruthy();
  });
});
