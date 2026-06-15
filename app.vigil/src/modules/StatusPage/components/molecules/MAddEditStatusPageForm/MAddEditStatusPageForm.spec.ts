import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mountWithPlugins } from '@/test/mount';

const { createStatusPage, updateStatusPage } = vi.hoisted(() => ({
  createStatusPage: vi.fn(() => Promise.resolve({ data: { id: '99', name: 'Public' } })),
  updateStatusPage: vi.fn(() => Promise.resolve({ data: { id: '1', name: 'Public' } })),
}));

vi.mock('../../../services', () => ({
  CreateStatusPageService: createStatusPage,
  UpdateStatusPageService: updateStatusPage,
}));

const { default: MAddEditStatusPageForm } = await import('./MAddEditStatusPageForm.vue');

const formStub = {
  emits: ['submit'],
  template:
    "<form class=\"form-stub\" @submit.prevent=\"$emit('submit', { valid: true, values: { name: 'Public' } })\"><slot /></form>",
};
const fieldStub = { template: '<div><slot :error="undefined" /></div>' };

function mountForm(props: Record<string, unknown>) {
  return mountWithPlugins(MAddEditStatusPageForm, {
    props: props as never,
    global: {
      stubs: { Form: formStub, FormField: fieldStub, InputText: true, ToggleSwitch: true, Button: true, AFormError: true },
    },
  });
}

describe('MAddEditStatusPageForm', () => {
  beforeEach(() => {
    createStatusPage.mockClear();
    updateStatusPage.mockClear();
  });

  it('creates a status page with a snake_case payload and branding headline', async () => {
    const wrapper = mountForm({ statusPage: null });

    await wrapper.find('.form-stub').trigger('submit');

    expect(createStatusPage).toHaveBeenCalledOnce();
    const [payload] = createStatusPage.mock.calls[0] as unknown as [Record<string, unknown>];
    expect(payload).toMatchObject({ name: 'Public', is_public: true });
    expect(payload.branding).toHaveProperty('headline');
  });

  it('updates an existing status page', async () => {
    const statusPage = { id: '1', name: 'Old', slug: 'old', customDomain: null, branding: {}, isPublic: false };
    const wrapper = mountForm({ statusPage });

    await wrapper.find('.form-stub').trigger('submit');

    const [id] = updateStatusPage.mock.calls[0] as unknown as [string, Record<string, unknown>];
    expect(id).toBe('1');
  });

  it('emits onSuccess with the saved status page', async () => {
    const wrapper = mountForm({ statusPage: null });

    await wrapper.find('.form-stub').trigger('submit');
    await Promise.resolve();

    expect(wrapper.emitted('onSuccess')).toBeTruthy();
  });
});
