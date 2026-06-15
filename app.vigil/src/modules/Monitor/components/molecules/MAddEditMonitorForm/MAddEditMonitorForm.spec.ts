import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mountWithPlugins } from '@/test/mount';

const { createMonitor, updateMonitor } = vi.hoisted(() => ({
  createMonitor: vi.fn(() => Promise.resolve({ data: { id: '99', name: 'API' } })),
  updateMonitor: vi.fn(() => Promise.resolve({ data: { id: '1', name: 'API' } })),
}));

vi.mock('../../../services', () => ({
  CreateMonitorService: createMonitor,
  UpdateMonitorService: updateMonitor,
}));

const { default: MAddEditMonitorForm } = await import('./MAddEditMonitorForm.vue');

const formStub = {
  emits: ['submit'],
  template:
    "<form class=\"form-stub\" @submit.prevent=\"$emit('submit', { valid: true, values: { name: 'API', target: 'https://x', interval_seconds: 60 } })\"><slot /><button class=\"do-submit\">go</button></form>",
};
const fieldStub = { template: '<div><slot :error="undefined" /></div>' };

function mountForm(props: Record<string, unknown>) {
  return mountWithPlugins(MAddEditMonitorForm, {
    props: props as never,
    global: {
      stubs: {
        Form: formStub,
        FormField: fieldStub,
        Select: true,
        InputText: true,
        InputNumber: true,
        Button: true,
        AFormError: true,
      },
    },
  });
}

describe('MAddEditMonitorForm', () => {
  beforeEach(() => {
    createMonitor.mockClear();
    updateMonitor.mockClear();
  });

  it('creates a monitor with a snake_case payload including project_id and type', async () => {
    const wrapper = mountForm({ monitor: null, projectId: '5' });

    await wrapper.find('.form-stub').trigger('submit');

    expect(createMonitor).toHaveBeenCalledOnce();
    expect(createMonitor).toHaveBeenCalledWith(
      expect.objectContaining({
        project_id: 5,
        type: 'http',
        interval_seconds: 60,
        name: 'API',
        target: 'https://x',
      })
    );
  });

  it('emits onSuccess with the created monitor', async () => {
    const wrapper = mountForm({ monitor: null, projectId: '5' });

    await wrapper.find('.form-stub').trigger('submit');
    await Promise.resolve();

    expect(wrapper.emitted('onSuccess')).toBeTruthy();
  });

  it('updates an existing monitor (no type in payload)', async () => {
    const monitor = { id: '1', projectId: '5', name: 'Old', type: 'http', config: {} };
    const wrapper = mountForm({ monitor, projectId: '5' });

    await wrapper.find('.form-stub').trigger('submit');

    expect(updateMonitor).toHaveBeenCalledOnce();
    const [id, payload] = updateMonitor.mock.calls[0] as unknown as [string, Record<string, unknown>];
    expect(id).toBe('1');
    expect(payload).not.toHaveProperty('type');
    expect(payload).toHaveProperty('interval_seconds', 60);
  });

  it('includes ssl_warn_days in config for ssl monitors', async () => {
    const monitor = { id: '2', projectId: '5', name: 'Cert', type: 'ssl', config: { ssl_warn_days: 21 } };
    const wrapper = mountForm({ monitor, projectId: '5' });

    await wrapper.find('.form-stub').trigger('submit');

    const [, payload] = updateMonitor.mock.calls[0] as unknown as [string, Record<string, unknown>];
    expect(payload.config).toEqual({ ssl_warn_days: 21 });
  });
});
