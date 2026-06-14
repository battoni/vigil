import { flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mountWithPlugins } from '@/test/mount';

// vi.hoisted ensures mock vars are initialised before the hoisted vi.mock factories run.
const { getUsersService, archiveUserService, deleteUserService } = vi.hoisted(() => ({
  getUsersService: vi.fn(),
  archiveUserService: vi.fn(),
  deleteUserService: vi.fn(),
}));

vi.mock('../../services', () => ({
  GetUsersService: getUsersService,
  ArchiveUserService: archiveUserService,
  DeleteUserService: deleteUserService,
}));

const storePermissions: string[] = [];

vi.mock('../../store', () => ({
  useUserStore: () => ({
    hasPermission: (key: string) => storePermissions.includes(key),
  }),
}));

import UsersView from './UsersView.view.vue';

const layoutStub = { template: '<div class="layout-stub"><slot name="pageHeader" /><slot /></div>' };
const pageHeaderStub = { template: '<div class="page-header-stub"><slot name="actions" /></div>' };
const morderByStub = { props: ['options', 'orderBy'], emits: ['update:orderBy'], template: '<div />' };
const dialogStub = {
  props: ['visible', 'isFooterless', 'title'],
  emits: ['update:visible'],
  template: '<div v-if="visible" class="dialog-stub"><slot /></div>',
};
const userFormStub = {
  name: 'MAddEditUserForm',
  props: ['user'],
  emits: ['onClose', 'onSuccess'],
  template: '<div class="user-form-stub" />',
};
const userCardStub = {
  props: ['user', 'canEdit', 'canArchive', 'canDelete'],
  emits: ['onEditUserRequest', 'onArchiveUserRequest', 'onDeleteUserRequest'],
  template: '<div class="user-card-stub">{{ user.name }}</div>',
};
const buttonStub = {
  props: ['label', 'icon', 'class'],
  template: '<button class="add-button-stub" v-bind="$attrs">{{ label }}</button>',
};

const globalStubs = {
  TheLayout: layoutStub,
  ThePageHeader: pageHeaderStub,
  MOrderBy: morderByStub,
  MMainDialog: dialogStub,
  MAddEditUserForm: userFormStub,
  MUserCard: userCardStub,
  Button: buttonStub,
  ConfirmPopup: true,
  InputGroup: { template: '<div><slot /></div>' },
  InputGroupAddon: true,
  MultiSelect: true,
  Skeleton: true,
};

const users = [
  { id: 1, name: 'Alice', last_name: 'Smith', username: 'alice', role: 'Admin', status: 'active' },
  { id: 2, name: 'Bob', last_name: 'Jones', username: 'bob', role: 'User', status: 'active' },
];

function mount() {
  return mountWithPlugins(UsersView, { global: { stubs: globalStubs } });
}

describe('UsersView', () => {
  beforeEach(() => {
    storePermissions.length = 0;
    getUsersService.mockReset();
    archiveUserService.mockReset();
    deleteUserService.mockReset();
  });

  it('does not call GetUsersService when user lacks users.read permission', async () => {
    mount();
    await flushPromises();
    expect(getUsersService).not.toHaveBeenCalled();
  });

  it('calls GetUsersService on mount when user has users.read', async () => {
    storePermissions.push('users.read');
    getUsersService.mockResolvedValue({ data: users });
    mount();
    await flushPromises();
    expect(getUsersService).toHaveBeenCalledOnce();
  });

  it('renders a user card for each user returned by the service', async () => {
    storePermissions.push('users.read');
    getUsersService.mockResolvedValue({ data: users });
    const wrapper = mount();
    await flushPromises();
    expect(wrapper.findAll('.user-card-stub')).toHaveLength(users.length);
  });

  it('renders user names inside cards', async () => {
    storePermissions.push('users.read');
    getUsersService.mockResolvedValue({ data: users });
    const wrapper = mount();
    await flushPromises();
    expect(wrapper.text()).toContain('Alice');
    expect(wrapper.text()).toContain('Bob');
  });

  it('opens the dialog when the Add button is clicked', async () => {
    storePermissions.push('users.read', 'users.create');
    getUsersService.mockResolvedValue({ data: [] });
    const wrapper = mount();
    await flushPromises();
    await wrapper.find('.add-button-stub').trigger('click');
    expect(wrapper.find('.dialog-stub').exists()).toBe(true);
  });

  it('prepends a newly created user to the list without refetching', async () => {
    storePermissions.push('users.read', 'users.create');
    getUsersService.mockResolvedValue({ data: users });
    const wrapper = mount();
    await flushPromises();

    await wrapper.find('.add-button-stub').trigger('click');

    const newUser = { id: 99, name: 'Carol', last_name: 'White', username: 'carol', role: 'User', status: 'active' };
    await wrapper.findComponent(userFormStub).vm.$emit('onSuccess', newUser);
    await flushPromises();

    const cards = wrapper.findAll('.user-card-stub');
    expect(cards).toHaveLength(users.length + 1);
    expect(cards[0]?.text()).toContain('Carol');
    expect(getUsersService).toHaveBeenCalledOnce();
  });

  it('replaces an updated user in the list without refetching', async () => {
    storePermissions.push('users.read', 'users.create');
    getUsersService.mockResolvedValue({ data: users });
    const wrapper = mount();
    await flushPromises();

    await wrapper.find('.add-button-stub').trigger('click');

    const updatedUser = { ...users[0], name: 'Alice Updated' };
    await wrapper.findComponent(userFormStub).vm.$emit('onSuccess', updatedUser);
    await flushPromises();

    expect(wrapper.findAll('.user-card-stub')).toHaveLength(users.length);
    expect(wrapper.text()).toContain('Alice Updated');
    expect(getUsersService).toHaveBeenCalledOnce();
  });
});
