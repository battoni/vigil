<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';
import type { PermissionGroup, Profile } from './interfaces';
import { useUserStore } from '@UserModule';
import { DeleteRoleService, GetRolesService, UpdateRolePermissionsService } from '../../services';

const userStore = useUserStore();
const confirm = useConfirm();
const { t } = useI18n();
const toast = useToast();

const activeTab = ref<string>('');
const deleteInProgress = ref(false);
const editingProfile = ref<Profile | null>(null);
const loading = ref(true);
const pendingDeleteRoleId = ref<string | null>(null);
const panelResetKey = ref(0);
const panelStates = ref<Record<string, boolean>>({});
const permissionGroups = ref<PermissionGroup[]>([]);
const profileDialogVisible = ref(false);
const roles = ref<Profile[]>([]);

const activeRole = computed(() => roles.value.find((role) => role.id === activeTab.value) ?? null);

const allPanelsExpandedModel = computed({
  get: () => Object.values(panelStates.value).every((collapsed) => !collapsed),
  set: (value: boolean) => {
    (activeRole.value?.permissionGroups ?? []).forEach((group) => {
      panelStates.value[group.id] = !value;
    });
  },
});

onMounted(onLoadRoles);

// HELPERS
function resetPanelStates() {
  if (!activeRole.value?.permissionGroups) return;

  const newStates = { ...panelStates.value };
  activeRole.value.permissionGroups.forEach(({ id }) => (newStates[id] = true));
  panelStates.value = newStates;
  panelResetKey.value += 1;
}

function setPermissionGroups(groups: PermissionGroup[]) {
  permissionGroups.value = groups.map(({ icon, id, nameKey, permissions }) => ({
    icon,
    id,
    nameKey,
    permissions: permissions.map(({ key, labelKey }) => ({ key, labelKey, value: false })),
  }));

  panelStates.value = Object.fromEntries(groups.map(({ id }) => [id, true]));
}

// EVENTS
function onLoadRoles() {
  loading.value = true;

  GetRolesService()
    .then(({ data }) => {
      roles.value = data;

      const firstRole = data[0];
      if (firstRole && !activeTab.value) activeTab.value = firstRole.id;

      if (firstRole?.permissionGroups) setPermissionGroups(firstRole.permissionGroups);
    })
    .catch(() =>
      toast.add({
        severity: 'error',
        summary: t('errors.somethingWentWrong'),
        detail: t('errors.refreshAndContactAdmin'),
        life: 10000,
      })
    )
    .finally(() => (loading.value = false));
}

function onSavePermissionsRequest() {
  if (!activeRole.value) return;

  loading.value = true;

  const permissions: Record<string, boolean> = {};
  activeRole.value.permissionGroups.forEach((group) => {
    group.permissions.forEach(({ key, value }) => (permissions[key] = value));
  });

  UpdateRolePermissionsService(activeRole.value.id, permissions)
    .then(async ({ data }) => {
      const roleIndex = roles.value.findIndex(({ id }) => id === data.id);

      const isEditing = roleIndex !== -1;
      if (isEditing) roles.value[roleIndex] = data;

      resetPanelStates();
      allPanelsExpandedModel.value = false;

      await userStore.fetchMe();

      toast.add({
        severity: 'success',
        summary: t('rolesAndPermissions.roleUpdatedSuccess', { name: data.name }),
        life: 5000,
      });
    })
    .catch(() =>
      toast.add({
        severity: 'error',
        summary: t('errors.somethingWentWrong'),
        detail: t('errors.refreshAndContactAdmin'),
        life: 10000,
      })
    )
    .finally(() => (loading.value = false));
}

function onNewProfileRequest() {
  editingProfile.value = null;
  profileDialogVisible.value = true;
}

function onEditProfileRequest() {
  editingProfile.value = activeRole.value;
  profileDialogVisible.value = true;
}

function onNewEditProfile(updatedRole: Profile) {
  profileDialogVisible.value = false;
  editingProfile.value = null;

  const roleIndex = roles.value.findIndex(({ id }) => id === updatedRole.id);

  setPermissionGroups(updatedRole.permissionGroups);
  allPanelsExpandedModel.value = false;

  const isEditing = roleIndex !== -1;
  if (isEditing) {
    roles.value[roleIndex] = updatedRole;
    resetPanelStates();
    return;
  }

  roles.value.push(updatedRole);
  if (!activeTab.value) activeTab.value = updatedRole.id;
  panelResetKey.value += 1;
}

function onDeleteProfileRequest(event: Event) {
  if (!activeRole.value) return;

  const { name, id, users = [] } = activeRole.value;

  const target = event.currentTarget as HTMLElement;

  if (users.length > 0) {
    const userList = users.map((userName: string) => userName).join('\n');
    confirm.require({
      group: 'delete-error',
      header: t('rolesAndPermissions.cannotDeleteRole'),
      message: `${t('rolesAndPermissions.usersUsingRole', { count: users.length })}\n\n${userList}`,
      acceptProps: {
        label: t('common.actions.ok'),
        severity: 'secondary',
      },
      accept: () => {},
    });
    return;
  }

  pendingDeleteRoleId.value = id;
  confirm.require({
    target,
    group: 'delete',
    header: t('rolesAndPermissions.deleteConfirmHeader'),
    message: t('rolesAndPermissions.deleteConfirmMessage', { name }),
    rejectProps: {
      label: t('common.actions.cancel'),
      severity: 'secondary',
    },
  });
}

function onConfirmDeleteAccept(acceptCallback: () => void) {
  const roleId = pendingDeleteRoleId.value;
  if (!roleId) return;

  deleteInProgress.value = true;

  DeleteRoleService(roleId)
    .then(() => {
      roles.value = roles.value.filter((role) => role.id !== roleId);

      const firstRole = roles.value[0];
      activeTab.value = firstRole?.id ?? '';
      if (firstRole?.permissionGroups) setPermissionGroups(firstRole.permissionGroups);

      pendingDeleteRoleId.value = null;
      acceptCallback();

      toast.add({
        severity: 'success',
        summary: t('rolesAndPermissions.roleDeletedSuccess'),
        life: 5000,
      });
    })
    .catch(() =>
      toast.add({
        severity: 'error',
        summary: t('errors.somethingWentWrong'),
        detail: t('errors.refreshAndContactAdmin'),
        life: 10000,
      })
    )
    .finally(() => (deleteInProgress.value = false));
}

function onConfirmDeleteReject(rejectCallback: () => void) {
  pendingDeleteRoleId.value = null;
  rejectCallback();
}
</script>

<template>
  <TheLayout>
    <template #pageHeader>
      <ThePageHeader title="rolesAndPermissions.title">
        <template
          v-if="!profileDialogVisible"
          #actions
        >
          <Button
            icon="pi pi-plus"
            :label="$t('rolesAndPermissions.newProfile')"
            @click="onNewProfileRequest"
          />
        </template>
      </ThePageHeader>
    </template>

    <MRolesAndPermissionsSkeleton v-if="loading" />

    <Tabs
      v-else
      v-model:value="activeTab"
    >
      <TabList class="border-none bg-transparent shadow-none">
        <Tab
          v-for="role in roles"
          class="relative cursor-pointer text-xl"
          :key="role.id"
          :value="role.id"
        >
          {{ role.name }}
        </Tab>
      </TabList>

      <TabPanels class="bg-transparent">
        <TabPanel
          v-for="role in roles"
          class="bg-transparent"
          :key="role.id"
          :value="role.id"
        >
          <MPermissionToolbar
            v-model="allPanelsExpandedModel"
            :canDeleteProfile="activeRole?.slug !== 'administrator'"
            :loading
            :tab="role.id"
            @onDeleteProfileRequest="onDeleteProfileRequest"
            @onEditProfileRequest="onEditProfileRequest"
            @onSavePermissionsRequest="onSavePermissionsRequest"
          />

          <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3">
            <MPermissionPanel
              v-for="group in activeRole?.permissionGroups ?? []"
              v-model:collapsed="panelStates[group.id]"
              :key="`${group.id}-${panelResetKey}`"
              :allPanelsExpanded="allPanelsExpandedModel"
              :loading
              :permissionGroup="group"
            />
          </div>
        </TabPanel>
      </TabPanels>
    </Tabs>

    <aside>
      <MConfirmPopup
        group="delete"
        popupClass="p-2 max-w-80"
        :loading="deleteInProgress"
        @onAccept="onConfirmDeleteAccept"
        @onReject="onConfirmDeleteReject"
      />

      <ConfirmDialog
        class="max-w-80 p-4"
        group="delete-error"
      >
        <template #container="{ message, acceptCallback }">
          <div class="flex flex-col gap-4">
            <h2 class="m-0 text-lg font-semibold">
              {{ message?.header }}
            </h2>

            <span class="block whitespace-pre-line">{{ message?.message }}</span>

            <div class="flex justify-end">
              <Button
                severity="secondary"
                :label="$t('common.actions.ok')"
                @click="acceptCallback()"
              />
            </div>
          </div>
        </template>
      </ConfirmDialog>

      <MMainDialog
        v-model:visible="profileDialogVisible"
        isFooterless
        :title="
          editingProfile ? 'rolesAndPermissions.form.editProfileHeader' : 'rolesAndPermissions.form.newProfileHeader'
        "
      >
        <MAddEditProfileForm
          :key="editingProfile?.id ?? 'new'"
          :permissionGroups
          :profile="editingProfile"
          @onClose="profileDialogVisible = false"
          @onSuccess="onNewEditProfile"
        />
      </MMainDialog>
    </aside>
  </TheLayout>
</template>
