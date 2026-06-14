import { describe, expect, it } from 'vitest';
import { mountWithPlugins } from '@/test/mount';
import ABrandMark from '../ABrandMark/ABrandMark.vue';
import AEmptyState from './AEmptyState.vue';

const global = { components: { ABrandMark } };

describe('AEmptyState', () => {
  it('renders the title and description', () => {
    const wrapper = mountWithPlugins(AEmptyState, {
      global,
      props: { title: 'No users yet', description: 'Add one to start.' },
    });

    expect(wrapper.text()).toContain('No users yet');
    expect(wrapper.text()).toContain('Add one to start.');
  });

  it('shows the default code-symbol glyph', () => {
    expect(mountWithPlugins(AEmptyState, { global }).text()).toContain('>_');
  });

  it('renders an icon instead of the monogram when provided', () => {
    const wrapper = mountWithPlugins(AEmptyState, {
      global,
      props: { icon: 'pi pi-users' },
    });

    expect(wrapper.find('i.pi-users').exists()).toBe(true);
  });
});
