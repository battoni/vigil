<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';
import type { Channel } from '../../interfaces';
import { CHANNEL_TYPE_ICON } from '../../constants';
import { DeleteChannelService } from '../../services';
import { useChannelStore } from '../../store';

const channelStore = useChannelStore();

const confirm = useConfirm();
const { t } = useI18n();
const toast = useToast();

const { channels, loading } = storeToRefs(channelStore);

const dialogVisible = ref(false);
const editingChannel = ref<Channel | null>(null);

onMounted(onComponentMount);

// HELPERS
function channelIcon(channel: Channel): string {
  return CHANNEL_TYPE_ICON[channel.type];
}

function notifyError() {
  toast.add({ severity: 'error', summary: t('errors.somethingWentWrong'), life: 5000 });
}

// EVENTS
function onComponentMount() {
  channelStore.fetchChannels().catch(() => notifyError());
}

function onCreateRequest() {
  editingChannel.value = null;
  dialogVisible.value = true;
}

function onEditRequest(channel: Channel) {
  editingChannel.value = channel;
  dialogVisible.value = true;
}

function onDialogSuccess(channel: Channel) {
  dialogVisible.value = false;
  editingChannel.value = null;

  const exists = channels.value.some((current) => current.id === channel.id);

  if (exists) {
    channelStore.replaceChannel(channel);
    return;
  }

  channelStore.addChannel(channel);
}

function onDeleteRequest(event: Event, channel: Channel) {
  confirm.require({
    accept: onDeleteAccept,
    acceptProps: { label: t('channels.delete'), class: 'celer-button-danger' },
    group: 'delete-channel',
    message: t('channels.deleteConfirm', { name: channel.name }),
    rejectProps: { label: t('common.actions.cancel'), class: 'celer-button-secondary' },
    target: event.currentTarget as HTMLElement,
  });

  function onDeleteAccept() {
    DeleteChannelService(channel.id)
      .then(({ data }) => {
        channelStore.removeChannel(data.id);
        toast.add({ severity: 'success', summary: t('channels.deleted', { name: channel.name }), life: 5000 });
      })
      .catch(() => notifyError());
  }
}
</script>

<template>
  <TheLayout>
    <template #pageHeader>
      <ThePageHeader title="channels.title">
        <template
          v-if="!dialogVisible"
          #actions
        >
          <Button
            class="celer-button-soft-primary w-fit shadow"
            data-testid="add-channel"
            icon="pi pi-plus"
            :label="$t('channels.add')"
            @click="onCreateRequest"
          />
        </template>
      </ThePageHeader>
    </template>

    <main>
      <DataTable
        data-testid="channels-table"
        :loading
        :value="channels"
      >
        <template #empty>
          <span class="text-subtle text-sm">{{ $t('channels.empty') }}</span>
        </template>

        <Column :header="$t('channels.type.label')">
          <template #body="{ data }">
            <span class="flex items-center gap-2">
              <i :class="channelIcon(data)" />

              {{ $t(`channels.type.${data.type}`) }}
            </span>
          </template>
        </Column>

        <Column
          field="name"
          :header="$t('channels.name')"
        />

        <Column :header="$t('channels.active')">
          <template #body="{ data }">
            <Tag
              rounded
              :severity="data.isActive ? 'success' : 'secondary'"
              :value="data.isActive ? $t('channels.statusActive') : $t('channels.statusInactive')"
            />
          </template>
        </Column>

        <Column :header="$t('channels.actions')">
          <template #body="{ data }">
            <span class="flex justify-end gap-2">
              <Button
                rounded
                text
                icon="pi pi-pencil"
                severity="secondary"
                @click="onEditRequest(data)"
              />

              <Button
                rounded
                text
                icon="pi pi-trash"
                severity="danger"
                @click="onDeleteRequest($event, data)"
              />
            </span>
          </template>
        </Column>
      </DataTable>
    </main>

    <aside>
      <ConfirmPopup
        class="max-w-70 p-2 text-center"
        group="delete-channel"
        pt:footer="justify-between pt-2"
      />

      <MMainDialog
        v-model:visible="dialogVisible"
        isFooterless
        :title="editingChannel ? 'channels.form.editHeader' : 'channels.form.createHeader'"
      >
        <MAddEditChannelForm
          :key="editingChannel?.id ?? 'new'"
          :channel="editingChannel"
          @onClose="dialogVisible = false"
          @onSuccess="onDialogSuccess"
        />
      </MMainDialog>
    </aside>
  </TheLayout>
</template>
