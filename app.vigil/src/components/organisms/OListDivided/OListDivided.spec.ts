import { h } from 'vue';
import { describe, expect, it } from 'vitest';
import type { ComponentMountingOptions } from '@vue/test-utils';
import { mountWithPlugins } from '@/test/mount';
import OListDivided from './OListDivided.vue';

type Slots = ComponentMountingOptions<typeof OListDivided>['slots'];

const primevueStubs = {
  Tag: { props: ['value', 'class', 'rounded', 'icon'], template: '<span class="tag-stub">{{ value }}</span>' },
  Button: {
    props: ['icon', 'severity', 'text', 'rounded', 'class', 'label'],
    emits: ['click'],
    template: '<button class="button-stub" :data-icon="icon" @click="$emit(\'click\', $event)">{{ label }}</button>',
  },
  MMainDialog: {
    props: ['visible', 'isFooterless', 'title'],
    template: '<div v-if="visible" class="dialog-stub"><slot /><slot name="footer" /></div>',
  },
};

const amountConfig = { valueKey: 'amount' };
const titleConfig = { valueKey: 'name' };

const items = [
  {
    id: '1',
    name: 'Alice',
    amount: '$100',
    status: 'active',
    statusTone: 'success',
    type: 'credit',
    typeTone: 'primary',
  },
  { id: '2', name: 'Bob', amount: '$200', status: 'inactive', statusTone: 'error', type: 'debit', typeTone: 'danger' },
];

function mount(props: Record<string, unknown> = {}, slots: Slots = {}) {
  return mountWithPlugins(OListDivided, {
    props: { items, amount: amountConfig, title: titleConfig, ...props },
    slots,
    global: { stubs: primevueStubs },
  });
}

describe('OListDivided', () => {
  it('shows the empty state message when items is empty', () => {
    expect(mount({ items: [] }).text()).toContain('No items');
  });

  it('renders a row for each item', () => {
    const wrapper = mount();
    expect(wrapper.text()).toContain('Alice');
    expect(wrapper.text()).toContain('Bob');
  });

  it('renders the amount for each item', () => {
    const wrapper = mount();
    expect(wrapper.text()).toContain('$100');
    expect(wrapper.text()).toContain('$200');
  });

  it('renders without an amount config', () => {
    const wrapper = mountWithPlugins(OListDivided, {
      props: { items, title: titleConfig },
      global: { stubs: primevueStubs },
    });
    expect(wrapper.text()).toContain('Alice');
    expect(wrapper.text()).not.toContain('$100');
  });

  it('applies valueFormatter to the amount', () => {
    const wrapper = mount({ amount: { valueKey: 'amount', valueFormatter: (raw: unknown) => `formatted-${raw}` } });
    expect(wrapper.text()).toContain('formatted-$100');
  });

  it('renders badge tags when badge config is provided', () => {
    const wrapper = mount({ badge: { valueKey: 'type', toneKey: 'typeTone' } });
    expect(wrapper.findAll('.tag-stub').length).toBeGreaterThanOrEqual(2);
  });

  it('renders status tags when status config is provided', () => {
    const wrapper = mount({ status: { valueKey: 'status', toneKey: 'statusTone' } });
    expect(wrapper.findAll('.tag-stub').some((tag) => tag.text() === 'active')).toBe(true);
  });

  it('renders a details button when showDetails is true', () => {
    const wrapper = mount({ showDetails: true });
    expect(wrapper.findAll('[data-icon="pi pi-eye"]').length).toBe(items.length);
  });

  it('renders only the gated inline action buttons', () => {
    const wrapper = mount({ canEdit: true });
    expect(wrapper.findAll('[data-icon="pi pi-pencil"]').length).toBe(items.length);
    expect(wrapper.find('[data-icon="pi pi-inbox"]').exists()).toBe(false);
    expect(wrapper.find('[data-icon="pi pi-trash"]').exists()).toBe(false);
  });

  it('emits onEdit with the item when the inline edit button is clicked', async () => {
    const wrapper = mount({ canEdit: true });

    await wrapper.find('[data-icon="pi pi-pencil"]').trigger('click');

    expect(wrapper.emitted('onEdit')?.[0]?.[0]).toMatchObject({ id: '1', name: 'Alice' });
  });

  it('emits onDelete with the item when the inline delete button is clicked', async () => {
    const wrapper = mount({ canDelete: true });

    await wrapper.find('[data-icon="pi pi-trash"]').trigger('click');

    expect(wrapper.emitted('onDelete')?.[0]?.[0]).toMatchObject({ id: '1', name: 'Alice' });
  });

  it('renders the custom #actions slot per row with the item', () => {
    const wrapper = mount({}, { actions: ({ item }) => h('button', { class: 'custom-action', 'data-id': item.id }) });
    expect(wrapper.findAll('.custom-action').length).toBe(items.length);
  });

  it('opens the detail dialog and passes the item to the #details slot', async () => {
    const wrapper = mount(
      { showDetails: true },
      { details: ({ item }) => h('span', { class: 'detail-content' }, String(item.name)) }
    );

    expect(wrapper.find('.dialog-stub').exists()).toBe(false);

    await wrapper.find('[data-icon="pi pi-eye"]').trigger('click');

    expect(wrapper.find('.dialog-stub').exists()).toBe(true);
    expect(wrapper.find('.detail-content').text()).toBe('Alice');
  });

  it('mirrors the actions into the dialog footer and closes the dialog after acting', async () => {
    const wrapper = mount({ showDetails: true, canEdit: true });

    await wrapper.find('[data-icon="pi pi-eye"]').trigger('click');

    expect(wrapper.findAll('.dialog-stub [data-icon="pi pi-pencil"]').length).toBe(1);

    await wrapper.find('.dialog-stub [data-icon="pi pi-pencil"]').trigger('click');

    expect(wrapper.emitted('onEdit')?.[0]?.[0]).toMatchObject({ id: '1' });
    expect(wrapper.find('.dialog-stub').exists()).toBe(false);
  });

  it('lets a custom #actions button dismiss the dialog via the slot close()', async () => {
    const wrapper = mount(
      { showDetails: true },
      { actions: ({ item, close }) => h('button', { class: 'custom-action', 'data-id': item.id, onClick: close }) }
    );

    await wrapper.find('[data-icon="pi pi-eye"]').trigger('click');
    expect(wrapper.find('.dialog-stub').exists()).toBe(true);

    await wrapper.find('.dialog-stub .custom-action').trigger('click');

    expect(wrapper.find('.dialog-stub').exists()).toBe(false);
  });
});
