import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mountWithPlugins } from '@/test/mount';

const { storeRef, setActiveProject, fetchProjects } = vi.hoisted(() => ({
  storeRef: { current: null as unknown as Record<string, unknown> },
  setActiveProject: vi.fn(),
  fetchProjects: vi.fn(() => Promise.resolve()),
}));

vi.mock('../../../store', () => ({
  useProjectStore: () => storeRef.current,
}));

const { default: MProjectSwitcher } = await import('./MProjectSwitcher.vue');

const selectStub = {
  props: ['options', 'modelValue'],
  emits: ['update:modelValue'],
  template:
    '<select class="select-stub" @change="$emit(\'update:modelValue\', $event.target.value)">' +
    '<option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>' +
    '</select>',
};

function setStore(projects: Array<{ id: string; name: string; slug: string }>, activeProjectId: string | null = null) {
  storeRef.current = { projects, activeProjectId, setActiveProject, fetchProjects };
}

function mountSwitcher() {
  return mountWithPlugins(MProjectSwitcher, { global: { stubs: { Select: selectStub } } });
}

describe('MProjectSwitcher', () => {
  beforeEach(() => {
    setActiveProject.mockReset();
    fetchProjects.mockReset();
    fetchProjects.mockImplementation(() => Promise.resolve());
  });

  it('renders a select with the project names when there are projects', () => {
    setStore([{ id: '1', name: 'Acme', slug: 'acme' }], '1');
    const wrapper = mountSwitcher();

    expect(wrapper.find('.select-stub').exists()).toBe(true);
    expect(wrapper.text()).toContain('Acme');
  });

  it('renders nothing when there are no projects', () => {
    setStore([]);
    const wrapper = mountSwitcher();

    expect(wrapper.find('.select-stub').exists()).toBe(false);
  });

  it('fetches projects on mount when the store is empty', () => {
    setStore([]);
    mountSwitcher();

    expect(fetchProjects).toHaveBeenCalledOnce();
  });

  it('does not refetch when projects are already loaded', () => {
    setStore([{ id: '1', name: 'Acme', slug: 'acme' }], '1');
    mountSwitcher();

    expect(fetchProjects).not.toHaveBeenCalled();
  });

  it('sets the active project on change', async () => {
    setStore(
      [
        { id: '1', name: 'Acme', slug: 'acme' },
        { id: '2', name: 'Globex', slug: 'globex' },
      ],
      '1'
    );
    const wrapper = mountSwitcher();

    await wrapper.find('.select-stub').setValue('2');

    expect(setActiveProject).toHaveBeenCalledWith('2');
  });
});
