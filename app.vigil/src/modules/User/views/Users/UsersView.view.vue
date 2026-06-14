<script setup lang="ts">
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';
import type { User } from '../../interfaces';
import type { Option } from '@Interfaces';
import { ArchiveUserService, DeleteUserService, GetUsersService } from '../../services';
import { useUserStore } from '../../store';

const userStore = useUserStore();
const confirm = useConfirm();
const { t } = useI18n();
const toast = useToast();

const searchMultiselectRef = useTemplateRef<{ hide: () => void }>('searchMultiselectRef');

const allUsersForSearch = ref<User[]>([]);
const dialogVisible = ref(false);
const editingUser = ref<User | null>(null);
const loading = ref(false);
const orderBy = ref<string>('name_asc');
const search = ref<number[]>([]);
const users = ref<User[]>([]);

const canCreate = computed(() => userStore.hasPermission('users.create'));
const canRead = computed(() => userStore.hasPermission('users.read'));

const orderByOptions = computed<Option[]>(() => [
  { label: t('users.sort.nameAsc'), value: 'name_asc' },
  { label: t('users.sort.nameDesc'), value: 'name_desc' },
  { label: t('users.sort.statusAsc'), value: 'status_asc' },
  { label: t('users.sort.statusDesc'), value: 'status_desc' },
  { label: t('users.sort.roleAsc'), value: 'role_asc' },
  { label: t('users.sort.roleDesc'), value: 'role_desc' },
]);
const searchOptions = computed<Option[]>(() => {
  const list = [...allUsersForSearch.value].sort((first, second) =>
    fullName(first).localeCompare(fullName(second), undefined, { sensitivity: 'base' })
  );

  return list.map((userItem) => ({
    label: fullName(userItem),
    value: userItem.id,
  }));
});

watch(orderBy, onOrderByChange);

onMounted(onUsersViewMounted);

// HELPERS
function fullName(user: User): string {
  return `${user.name} ${user.last_name}`.trim();
}

function loadUsers(params?: { ids?: number[]; order_by?: string }) {
  loading.value = true;

  const ids = params?.ids?.length ? params.ids : undefined;
  const order_by = params?.order_by ?? orderBy.value;

  GetUsersService({ ids, order_by })
    .then(({ data }) => {
      users.value = data ?? [];

      if (ids == null) {
        allUsersForSearch.value = data ?? [];
      }
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

// EVENTS
function onArchiveUserRequest(event: Event, user: User) {
  const isInactive = user.status?.toLowerCase() === 'inactive' || user.status?.toLowerCase() === 'inativo';
  const userName = `${user.name} ${user.last_name}`;

  confirm.require({
    accept: onArchiveAccept,
    acceptProps: {
      label: isInactive ? t('users.confirmActions.yesActivate') : t('users.confirmActions.yesArchive'),
      class: 'celer-button-warning',
    },
    group: 'archive',
    message: isInactive
      ? t('users.activateConfirmMessage', { name: userName })
      : t('users.archiveConfirmMessage', { name: userName }),
    rejectProps: {
      label: t('common.actions.cancel'),
      class: 'celer-button-secondary',
    },
    target: event.currentTarget as HTMLElement,
  });

  function onArchiveAccept() {
    const name = userName;

    ArchiveUserService(user.id)
      .then(({ data }) => {
        if (data?.id) {
          users.value = users.value.map((currentUser) => (currentUser.id === data.id ? data : currentUser));

          const successKey = isInactive ? 'users.userActivatedSuccess' : 'users.userArchivedSuccess';

          toast.add({
            severity: 'success',
            summary: t(successKey, { name }),
            life: 5000,
          });
        }
      })
      .catch(() =>
        toast.add({
          severity: 'error',
          summary: t('errors.somethingWentWrong'),
          detail: t('errors.refreshAndContactAdmin'),
          life: 10000,
        })
      );
  }
}

function onCreateUserRequest() {
  editingUser.value = null;
  dialogVisible.value = true;
}

function onDeleteUserRequest(event: Event, user: User) {
  const userName = `${user.name} ${user.last_name}`;

  confirm.require({
    accept: onDeleteAccept,
    acceptProps: {
      label: t('users.confirmActions.yesDelete'),
      class: 'celer-button-danger',
    },
    group: 'delete',
    message: t('users.deleteConfirmMessage', { name: userName }),
    rejectProps: {
      label: t('common.actions.cancel'),
      class: 'celer-button-secondary',
    },
    target: event.currentTarget as HTMLElement,
  });

  function onDeleteAccept() {
    DeleteUserService(user.id)
      .then(({ data }) => {
        if (data?.id) {
          users.value = users.value.filter((currentUser) => currentUser.id !== data.id);

          toast.add({
            severity: 'success',
            summary: t('users.userDeletedSuccess'),
            life: 5000,
          });
        }
      })
      .catch(() =>
        toast.add({
          severity: 'error',
          summary: t('errors.somethingWentWrong'),
          detail: t('errors.refreshAndContactAdmin'),
          life: 10000,
        })
      );
  }
}

function onDialogSuccess(user: User) {
  dialogVisible.value = false;
  editingUser.value = null;

  const index = users.value.findIndex((currentUser) => currentUser.id === user.id);
  if (index >= 0) {
    users.value = users.value.map((currentUser) => (currentUser.id === user.id ? user : currentUser));
    return;
  }

  users.value = [user, ...users.value];
}

function onEditUserRequest(user: User) {
  editingUser.value = user;
  dialogVisible.value = true;
}

function onOrderByChange() {
  if (!canRead.value) return;

  const ids = search.value?.length ? search.value : undefined;

  loadUsers({ ids, order_by: orderBy.value });
}

function onSearchCancel() {
  searchMultiselectRef.value?.hide();
  search.value = [];

  loadUsers({ order_by: orderBy.value });
}

function onSearchSubmit() {
  searchMultiselectRef.value?.hide();

  if (!canRead.value) return;

  const ids = search.value?.length ? search.value : undefined;

  loadUsers({ ids, order_by: orderBy.value });
}

function onUsersViewMounted() {
  if (canRead.value) loadUsers();
}
</script>

<template>
  <TheLayout>
    <template #pageHeader>
      <ThePageHeader title="users.title">
        <template
          v-if="!dialogVisible"
          #actions
        >
          <Button
            v-if="canCreate"
            class="celer-button-soft-primary w-fit shadow transition-all duration-300 active:scale-95"
            data-testid="add-user"
            icon="pi pi-plus"
            :label="$t('users.add')"
            @click="onCreateUserRequest"
          />
        </template>
      </ThePageHeader>
    </template>

    <div class="flex flex-row flex-wrap justify-start gap-4">
      <InputGroup class="sm:w-fit">
        <InputGroupAddon class="border-none shadow">
          <i class="pi pi-search" />
        </InputGroupAddon>

        <MultiSelect
          v-model="search"
          filter
          class="min-w-32 border-none shadow"
          inputId="users-search"
          optionLabel="label"
          optionValue="value"
          ref="searchMultiselectRef"
          :emptyFilterMessage="t('common.multiselect.emptyFilterMessage')"
          :emptyMessage="t('common.multiselect.emptyMessage')"
          :filterPlaceholder="t('common.multiselect.filterPlaceholder')"
          :options="searchOptions"
          :placeholder="t('common.search')"
        >
          <template #footer>
            <div class="border-line flex justify-end gap-2 border-t p-2">
              <Button
                class="flex-1"
                severity="secondary"
                size="small"
                :label="$t('common.actions.cancel')"
                @click="onSearchCancel"
              />

              <Button
                class="flex-1"
                size="small"
                :label="$t('common.search')"
                @click="onSearchSubmit"
              />
            </div>
          </template>
        </MultiSelect>

        <label
          class="sr-only"
          for="users-search"
        >
          {{ $t('common.search') }}
        </label>
      </InputGroup>

      <MOrderBy
        v-model:orderBy="orderBy"
        class="sm:w-fit"
        :options="orderByOptions"
      />
    </div>

    <main>
      <div
        v-if="!canRead"
        class="border-warn-200 bg-warn-50 text-warn-800 rounded-lg border p-6"
      >
        {{ $t('users.noAccess') }}
      </div>

      <div
        v-else-if="loading"
        class="grid grid-cols-1 gap-8 md:grid-cols-3"
      >
        <div
          v-for="skeletonIndex in 6"
          class="border-line flex flex-col gap-3 rounded-md border p-4"
          :key="skeletonIndex"
        >
          <div class="flex items-center gap-3">
            <Skeleton
              class="rounded-full"
              height="3.5rem"
              width="3.5rem"
            />

            <div class="flex flex-1 flex-col gap-2">
              <Skeleton
                height="1.25rem"
                width="60%"
              />

              <Skeleton
                height="1rem"
                width="40%"
              />
            </div>
          </div>

          <Skeleton
            height="1px"
            width="100%"
          />

          <div class="flex justify-end gap-2">
            <Skeleton
              height="2.25rem"
              width="2.25rem"
            />

            <Skeleton
              height="2.25rem"
              width="2.25rem"
            />

            <Skeleton
              height="2.25rem"
              width="2.25rem"
            />
          </div>
        </div>
      </div>

      <div
        v-else-if="users.length"
        class="grid grid-cols-1 gap-8 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3"
      >
        <MUserCard
          v-for="user in users"
          :key="user.id"
          :canArchive="userStore.hasPermission('users.archive')"
          :canDelete="userStore.hasPermission('users.delete')"
          :canEdit="userStore.hasPermission('users.update')"
          :user
          @onArchiveUserRequest="(event: Event) => onArchiveUserRequest(event, user)"
          @onDeleteUserRequest="(event: Event) => onDeleteUserRequest(event, user)"
          @onEditUserRequest="onEditUserRequest(user)"
        />
      </div>

      <AEmptyState
        v-else
        :description="$t('users.empty.description')"
        :title="$t('users.empty.title')"
      >
        <template
          v-if="canCreate"
          #action
        >
          <Button
            class="celer-button-soft-primary"
            icon="pi pi-plus"
            :label="$t('users.add')"
            @click="onCreateUserRequest"
          />
        </template>
      </AEmptyState>
    </main>

    <aside>
      <ConfirmPopup
        class="max-w-70 p-2 text-center"
        group="archive"
        pt:footer="justify-between pt-2"
      />

      <ConfirmPopup
        class="max-w-70 p-2 text-center"
        group="delete"
        pt:footer="justify-between pt-2"
      />

      <MMainDialog
        v-model:visible="dialogVisible"
        isFooterless
        :title="editingUser ? 'users.form.editHeader' : 'users.form.createHeader'"
      >
        <MAddEditUserForm
          :key="editingUser?.id ?? 'new'"
          :user="editingUser"
          @onClose="dialogVisible = false"
          @onSuccess="onDialogSuccess"
        />
      </MMainDialog>
    </aside>
  </TheLayout>
</template>
