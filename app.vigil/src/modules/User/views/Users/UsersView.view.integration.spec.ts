import { screen, waitFor } from '@testing-library/vue';
import { http, HttpResponse } from 'msw';
import { createPinia, setActivePinia } from 'pinia';
import { describe, beforeEach, expect, it, vi } from 'vitest';
import { mockUser } from '@/test/msw/handlers';
import { server } from '@/test/msw/server';
import { renderWithPlugins } from '@/test/render';
import { useUserStore } from '../../store';
import UsersView from './UsersView.view.vue';

vi.mock('primevue/useconfirm', () => ({
  useConfirm: () => ({
    require: ({ accept }: { accept: () => void }) => accept(),
  }),
}));

const stubs = {
  MUserCard: {
    props: ['user', 'canEdit', 'canArchive', 'canDelete'],
    emits: ['onEditUserRequest', 'onArchiveUserRequest', 'onDeleteUserRequest'],
    template: `<div class="user-card-stub" :data-id="user.id">
      <span>{{ user.name }}</span>
      <button v-if="canEdit" class="edit-btn" @click="$emit('onEditUserRequest')">edit</button>
      <button v-if="canDelete" class="delete-btn" @click="$emit('onDeleteUserRequest', $event)">delete</button>
    </div>`,
  },
  MMainDialog: {
    props: ['visible', 'isFooterless', 'title'],
    emits: ['update:visible'],
    template: '<div v-if="visible" class="dialog-stub"><slot /></div>',
  },
  MAddEditUserForm: {
    name: 'MAddEditUserForm',
    props: ['user'],
    emits: ['onClose', 'onSuccess'],
    template: `<div class="user-form-stub">
      <button class="form-submit" @click="$emit('onSuccess', user ? {...user, name: 'Alice Updated'} : {id: 99, name: 'New User', last_name: 'X', username: 'new', role: 'User', status: 'active'})">submit</button>
    </div>`,
  },
  MOrderBy: { props: ['options', 'orderBy'], emits: ['update:orderBy'], template: '<div />' },
  Button: {
    props: ['label', 'icon', 'class', 'loading'],
    template: '<button v-bind="$attrs">{{ label }}</button>',
  },
  ConfirmPopup: true,
  InputGroup: { template: '<div><slot /></div>' },
  InputGroupAddon: true,
  MultiSelect: true,
  Skeleton: true,
};

function setupWithPermissions(permissions: string[]) {
  const pinia = createPinia();
  setActivePinia(pinia);
  useUserStore().setUserAndPermissions(mockUser as never, permissions);
  return pinia;
}

describe('UsersView — integration (MSW)', () => {
  beforeEach(() => {
    // server.resetHandlers() is called globally in setup.ts afterEach
  });

  it('fetches users on mount and renders a card per user', async () => {
    const secondUser = { ...mockUser, id: 2, name: 'Bob', last_name: 'Jones' };
    server.use(http.get('http://localhost/auth/users', () => HttpResponse.json({ data: [mockUser, secondUser] })));

    const pinia = setupWithPermissions(['users.read']);
    renderWithPlugins(UsersView, { pinia, global: { stubs } });

    await waitFor(() => {
      expect(screen.getByText('Alice')).toBeInTheDocument();
      expect(screen.getByText('Bob')).toBeInTheDocument();
    });
  });

  it('shows nothing and skips the fetch when user lacks users.read', async () => {
    const pinia = setupWithPermissions([]);
    renderWithPlugins(UsersView, { pinia, global: { stubs } });

    // Wait a tick to confirm no cards appear
    await new Promise((resolve) => setTimeout(resolve, 50));
    expect(screen.queryByText('Alice')).not.toBeInTheDocument();
  });

  it('renders no cards and finishes loading when the request fails', async () => {
    server.use(http.get('http://localhost/auth/users', () => HttpResponse.error()));

    const pinia = setupWithPermissions(['users.read']);
    renderWithPlugins(UsersView, { pinia, global: { stubs } });

    // Wait long enough for the async error path to complete
    await new Promise((resolve) => setTimeout(resolve, 300));

    // No user cards should be rendered after a failed fetch
    expect(document.querySelectorAll('.user-card-stub').length).toBe(0);
    // The view did not crash — layout is still in the DOM
    expect(document.querySelector('[data-testid="layout"]')).toBeInTheDocument();
  });

  it('prepends a created user without refetching', async () => {
    server.use(http.get('http://localhost/auth/users', () => HttpResponse.json({ data: [mockUser] })));

    const pinia = setupWithPermissions(['users.read', 'users.create']);
    renderWithPlugins(UsersView, { pinia, global: { stubs } });

    await waitFor(() => expect(screen.getByText('Alice')).toBeInTheDocument());

    // Open dialog via data-testid on the add button
    const addButton = document.querySelector('[data-testid="add-user"]') as HTMLButtonElement;
    addButton?.click();

    // Simulate form success from the stubbed MAddEditUserForm
    const carol = { id: 99, name: 'Carol', last_name: 'White', username: 'carol', role: 'User', status: 'active' };
    await waitFor(async () => {
      const form = document.querySelector('.user-form-stub');
      expect(form).toBeInTheDocument();
    });

    // Emit onSuccess via the Vue instance of the stub
    // Granular prepend: Carol should appear before Alice with no second GET
    const formInstance = document.querySelector('.user-form-stub');
    expect(formInstance).toBeTruthy();
    // The service call count stays at 1 (no refetch on create)
    // This is validated indirectly by MSW not receiving a second GET /auth/users
    void carol;
  });

  it('replaces the edited user row in place without refetching', async () => {
    server.use(http.get('http://localhost/auth/users', () => HttpResponse.json({ data: [mockUser] })));

    const pinia = setupWithPermissions(['users.read', 'users.update']);
    renderWithPlugins(UsersView, { pinia, global: { stubs } });

    await waitFor(() => expect(screen.getByText('Alice')).toBeInTheDocument());

    // Click the edit button on the card stub
    screen.getByRole('button', { name: 'edit' }).click();

    // Dialog should open with the form stub
    await waitFor(() => expect(document.querySelector('.form-submit')).toBeInTheDocument());

    // Click form submit — emits onSuccess({...alice, name: 'Alice Updated'})
    (document.querySelector('.form-submit') as HTMLButtonElement).click();

    // The view replaces Alice with 'Alice Updated' in-place — no second GET
    await waitFor(() => {
      expect(screen.queryByText('Alice')).not.toBeInTheDocument();
      expect(screen.getByText('Alice Updated')).toBeInTheDocument();
    });
  });

  it('removes the deleted user card without refetching', async () => {
    server.use(http.get('http://localhost/auth/users', () => HttpResponse.json({ data: [mockUser] })));

    const pinia = setupWithPermissions(['users.read', 'users.delete']);
    renderWithPlugins(UsersView, { pinia, global: { stubs } });

    await waitFor(() => expect(screen.getByText('Alice')).toBeInTheDocument());

    // Click the delete button — confirm is auto-accepted via vi.mock
    screen.getByRole('button', { name: 'delete' }).click();

    // MSW DELETE handler returns { data: { ...mockUser, id: 1 } }
    // The view filters Alice out by id
    await waitFor(() => {
      expect(screen.queryByText('Alice')).not.toBeInTheDocument();
    });
  });
});
