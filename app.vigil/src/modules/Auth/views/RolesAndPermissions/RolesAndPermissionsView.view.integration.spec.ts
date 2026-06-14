import { screen, waitFor } from '@testing-library/vue';
import { http, HttpResponse } from 'msw';
import { createPinia, setActivePinia } from 'pinia';
import { describe, expect, it } from 'vitest';
import { mockUser, mockRole } from '@/test/msw/handlers';
import { server } from '@/test/msw/server';
import { renderWithPlugins } from '@/test/render';
import { useUserStore } from '@UserModule';
import RolesAndPermissionsView from './RolesAndPermissionsView.view.vue';

const stubs = {
  MRolesAndPermissionsSkeleton: true,
  MPermissionToolbar: {
    props: ['loading', 'tab'],
    emits: ['onSavePermissionsRequest', 'onEditProfileRequest', 'onDeleteProfileRequest'],
    template:
      '<div class="permission-toolbar-stub"><button class="save-btn" @click="$emit(\'onSavePermissionsRequest\')">Save</button></div>',
  },
  MPermissionPanel: {
    props: ['permissionGroup', 'loading', 'collapsed', 'allPanelsExpanded'],
    emits: ['update:collapsed'],
    template: '<div class="permission-panel-stub">{{ permissionGroup?.nameKey }}</div>',
  },
  MConfirmPopup: true,
  MMainDialog: {
    props: ['visible', 'isFooterless', 'title'],
    emits: ['update:visible'],
    template: '<div v-if="visible" class="dialog-stub"><slot /></div>',
  },
  MAddEditProfileForm: {
    props: ['permissionGroups', 'profile'],
    emits: ['onClose', 'onSuccess'],
    template: '<div class="profile-form-stub" />',
  },
  Button: {
    props: ['label', 'icon'],
    template: '<button v-bind="$attrs">{{ label }}</button>',
  },
  Tabs: { props: ['value'], emits: ['update:value'], template: '<div class="tabs-stub"><slot /></div>' },
  TabList: { template: '<div class="tab-list-stub"><slot /></div>' },
  Tab: { props: ['value'], template: '<button class="tab-stub" v-bind="$attrs"><slot /></button>' },
  TabPanels: { template: '<div class="tab-panels-stub"><slot /></div>' },
  TabPanel: { props: ['value'], template: '<div class="tab-panel-stub"><slot /></div>' },
  ConfirmDialog: true,
};

function setupStore() {
  const pinia = createPinia();
  setActivePinia(pinia);
  useUserStore().setUserAndPermissions(mockUser as never, mockUser.permissions ?? []);
  return pinia;
}

describe('RolesAndPermissionsView — integration (MSW)', () => {
  it('loads roles from the API and renders a tab per role', async () => {
    const secondRole = { ...mockRole, id: 'role-2', name: 'Support', slug: 'support' };
    server.use(http.get('http://localhost/auth/roles', () => HttpResponse.json({ data: [mockRole, secondRole] })));

    const pinia = setupStore();
    renderWithPlugins(RolesAndPermissionsView, { pinia, global: { stubs } });

    await waitFor(() => {
      expect(screen.getByText('Admin')).toBeInTheDocument();
      expect(screen.getByText('Support')).toBeInTheDocument();
    });
  });

  it('shows the permission groups for the first loaded role', async () => {
    server.use(http.get('http://localhost/auth/roles', () => HttpResponse.json({ data: [mockRole] })));

    const pinia = setupStore();
    renderWithPlugins(RolesAndPermissionsView, { pinia, global: { stubs } });

    await waitFor(() => {
      expect(screen.getByText('permissions.users')).toBeInTheDocument();
    });
  });

  it('calls PATCH on save and view remains intact after success', async () => {
    let patchCalled = false;
    server.use(
      http.get('http://localhost/auth/roles', () => HttpResponse.json({ data: [mockRole] })),
      http.patch('http://localhost/auth/roles/:id/permissions', () => {
        patchCalled = true;
        return HttpResponse.json({ data: mockRole });
      }),
      http.get('http://localhost/auth/me', () => HttpResponse.json({ data: mockUser }))
    );

    const pinia = setupStore();
    renderWithPlugins(RolesAndPermissionsView, { pinia, global: { stubs } });

    await waitFor(() => screen.getByText('Save'));
    screen.getByText('Save').click();

    // Wait for async save + fetchMe to complete, then confirm PATCH was called
    // and the view did not crash (role data still visible)
    await waitFor(() => expect(patchCalled).toBe(true), { timeout: 3000 });
    expect(screen.getByText('Admin')).toBeInTheDocument();
  });

  it('shows an error toast when the roles fetch fails', async () => {
    server.use(http.get('http://localhost/auth/roles', () => HttpResponse.error()));

    const pinia = setupStore();
    renderWithPlugins(RolesAndPermissionsView, { pinia, global: { stubs } });

    await waitFor(
      () => {
        const hasError =
          document.querySelector('[data-pc-name="toast"]') !== null || document.body.innerHTML.includes('error');
        expect(hasError).toBe(true);
      },
      { timeout: 3000 }
    );
  });
});
