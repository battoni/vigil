<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import type { WalletTransactionItem } from './interfaces';
import type { FinanceActionType, WalletStatusTone, WalletTypeTone } from './types';

const { t } = useI18n();

const transactionToneByAction: Record<FinanceActionType, WalletTypeTone> = {
  deposit: 'success',
  withdraw: 'warn',
};

const actionModalVisible = ref(false);
const actionType = ref<FinanceActionType>('deposit');
const formAmount = ref('');
const formEntity = ref('');
const formStatusTone = ref<WalletStatusTone>('success');
const transactions = ref<WalletTransactionItem[]>([]);

const statusOptions = computed(() => [
  { label: t('finance.transactions.status.done'), value: 'success' as const },
  { label: t('finance.transactions.status.scheduled'), value: 'warning' as const },
  { label: t('finance.transactions.status.canceled'), value: 'error' as const },
]);
const walletCards = computed(() => [
  {
    id: 'total',
    description: t('finance.cards.total.description'),
    title: t('finance.cards.total.title'),
    value: 'R$ 100.000',
    variant: 'primary' as const,
  },
  {
    id: 'available',
    description: t('finance.cards.available.description'),
    title: t('finance.cards.available.title'),
    value: 'R$ 70.000',
    variant: 'info' as const,
  },
  {
    id: 'in-use',
    description: t('finance.cards.inUse.description'),
    title: t('finance.cards.inUse.title'),
    value: 'R$ 30.000',
    variant: 'warning' as const,
  },
]);

onMounted(buildInitialTransactions);

// HELPERS
function buildInitialTransactions() {
  transactions.value = [
    {
      id: 'finance-seed-1',
      amount: 'R$ 100.000',
      date: '10/04/2026',
      entity: 'Battoni Dev',
      status: t('finance.transactions.status.done'),
      statusTone: 'success' as const,
      type: t('finance.transactions.type.deposit'),
      typeTone: 'success' as const,
    },
    {
      id: 'finance-seed-2',
      amount: 'R$ 30.000',
      date: '11/04/2026',
      entity: 'Battoni Dev',
      status: t('finance.transactions.status.canceled'),
      statusTone: 'error' as const,
      type: t('finance.transactions.type.withdraw'),
      typeTone: 'warn' as const,
    },
    {
      id: 'finance-seed-3',
      amount: 'R$ 20.000',
      date: '12/04/2026',
      entity: 'Outra Empresa',
      status: t('finance.transactions.status.scheduled'),
      statusTone: 'warning' as const,
      type: t('finance.transactions.type.payment'),
      typeTone: 'info' as const,
    },
    {
      id: 'finance-seed-4',
      amount: 'R$ 10.000',
      date: '13/04/2026',
      entity: 'Empresa 2',
      status: t('finance.transactions.status.done'),
      statusTone: 'success' as const,
      type: t('finance.transactions.type.refund'),
      typeTone: 'danger' as const,
    },
    {
      id: 'finance-seed-5',
      amount: 'R$ 10.000',
      date: '13/04/2026',
      entity: 'Empresa 2',
      status: t('finance.transactions.status.done'),
      statusTone: 'success' as const,
      type: t('finance.transactions.type.refund'),
      typeTone: 'danger' as const,
    },
    {
      id: 'finance-seed-6',
      amount: 'R$ 10.000',
      date: '13/04/2026',
      entity: 'Empresa 2',
      status: t('finance.transactions.status.done'),
      statusTone: 'success' as const,
      type: t('finance.transactions.type.refund'),
      typeTone: 'danger' as const,
    },
    {
      id: 'finance-seed-7',
      amount: 'R$ 100.000',
      date: '10/04/2026',
      entity: 'Battoni Dev',
      status: t('finance.transactions.status.done'),
      statusTone: 'success' as const,
      type: t('finance.transactions.type.deposit'),
      typeTone: 'success' as const,
    },
    {
      id: 'finance-seed-8',
      amount: 'R$ 30.000',
      date: '11/04/2026',
      entity: 'Battoni Dev',
      status: t('finance.transactions.status.canceled'),
      statusTone: 'error' as const,
      type: t('finance.transactions.type.withdraw'),
      typeTone: 'warn' as const,
    },
    {
      id: 'finance-seed-9',
      amount: 'R$ 20.000',
      date: '12/04/2026',
      entity: 'Outra Empresa',
      status: t('finance.transactions.status.scheduled'),
      statusTone: 'warning' as const,
      type: t('finance.transactions.type.payment'),
      typeTone: 'info' as const,
    },
    {
      id: 'finance-seed-10',
      amount: 'R$ 10.000',
      date: '13/04/2026',
      entity: 'Empresa 2',
      status: t('finance.transactions.status.done'),
      statusTone: 'success' as const,
      type: t('finance.transactions.type.refund'),
      typeTone: 'danger' as const,
    },
    {
      id: 'finance-seed-11',
      amount: 'R$ 10.000',
      date: '13/04/2026',
      entity: 'Empresa 2',
      status: t('finance.transactions.status.done'),
      statusTone: 'success' as const,
      type: t('finance.transactions.type.refund'),
      typeTone: 'danger' as const,
    },
    {
      id: 'finance-seed-12',
      amount: 'R$ 10.000',
      date: '13/04/2026',
      entity: 'Empresa 2',
      status: t('finance.transactions.status.done'),
      statusTone: 'success' as const,
      type: t('finance.transactions.type.refund'),
      typeTone: 'danger' as const,
    },
  ];
}

function formatAmountToCurrency(rawAmount: string) {
  const normalized = rawAmount.replace(/\./g, '').replace(',', '.');
  const numericAmount = Number(normalized);
  if (Number.isNaN(numericAmount)) return rawAmount;

  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(numericAmount);
}

function getCurrentDate() {
  return new Intl.DateTimeFormat('pt-BR').format(new Date());
}

function getStatusLabel(statusTone: WalletStatusTone) {
  if (statusTone === 'success') return t('finance.transactions.status.done');
  if (statusTone === 'warning') return t('finance.transactions.status.scheduled');

  return t('finance.transactions.status.canceled');
}

function resetForm() {
  formAmount.value = '';
  formEntity.value = '';
  formStatusTone.value = 'success';
}

// EVENTS
function onOpenActionModal(nextActionType: FinanceActionType) {
  actionType.value = nextActionType;
  resetForm();
  actionModalVisible.value = true;
}

function onCloseActionModal() {
  actionModalVisible.value = false;
  resetForm();
}

function onSubmitAction() {
  const amount = formatAmountToCurrency(formAmount.value);
  const entity = formEntity.value.trim() || t('finance.modal.defaults.entity');
  const statusTone = formStatusTone.value;

  transactions.value.unshift({
    id: `finance-tx-${Date.now()}`,
    amount,
    date: getCurrentDate(),
    entity,
    status: getStatusLabel(statusTone),
    statusTone,
    type: t(`finance.transactions.type.${actionType.value}`),
    typeTone: transactionToneByAction[actionType.value],
  });

  onCloseActionModal();
}
</script>

<template>
  <TheLayout class="min-h-0 w-full">
    <template #pageHeader>
      <ThePageHeader title="finance.title">
        <template
          v-if="!actionModalVisible"
          #actions
        >
          <div class="flex flex-col items-center gap-3 sm:flex-row sm:items-center sm:gap-3">
            <Button
              class="celer-button-soft-primary w-30 shadow-none"
              icon="pi pi-minus-circle"
              iconPos="right"
              :label="t('finance.actions.withdraw')"
              @click="onOpenActionModal('withdraw')"
            />

            <Button
              class="celer-button-primary w-30"
              icon="pi pi-plus-circle"
              iconPos="right"
              :label="t('finance.actions.deposit')"
              @click="onOpenActionModal('deposit')"
            />
          </div>
        </template>
      </ThePageHeader>
    </template>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
      <MWalletCard
        v-for="{ id, description, title, value, variant } in walletCards"
        :key="id"
        :description
        :title
        :value
        :variant
      />
    </div>

    <div class="h-auto md:h-full md:min-h-0 md:flex-1 lg:h-auto">
      <OWalletTransactions :transactions />
    </div>

    <MMainDialog
      v-model:visible="actionModalVisible"
      isFooterless
      :title="`finance.modal.title.${actionType}`"
    >
      <!-- NOTE TEMPORARY FORM FOR DEMONSTRATION PURPOSES -->
      <form
        class="mx-auto flex w-full max-w-[500px] flex-col gap-4 pb-[calc(4rem+env(safe-area-inset-bottom,0))]"
        @submit.prevent="onSubmitAction"
      >
        <div class="flex flex-col gap-2">
          <label
            class="text-muted mb-2 block text-sm"
            for="finance-action-amount"
          >
            {{ t('finance.modal.fields.amount') }}
          </label>

          <InputText
            v-model="formAmount"
            id="finance-action-amount"
            :placeholder="t('finance.modal.placeholders.amount')"
          />
        </div>

        <div
          v-for="n in 15"
          class="flex flex-col gap-2"
          :key="n"
        >
          <label
            class="text-muted mb-2 block text-sm"
            for="finance-action-entity"
          >
            {{ t('finance.modal.fields.entity') }}
          </label>

          <InputText
            v-model="formEntity"
            id="finance-action-entity"
            :placeholder="t('finance.modal.placeholders.entity')"
          />
        </div>

        <div class="flex flex-col gap-2">
          <label
            class="text-muted mb-2 block text-sm"
            for="finance-action-status"
          >
            {{ t('finance.modal.fields.status') }}
          </label>

          <Select
            v-model="formStatusTone"
            id="finance-action-status"
            optionLabel="label"
            optionValue="value"
            :options="statusOptions"
          />
        </div>

        <div
          class="bg-canvas fixed right-0 bottom-0 left-0 z-10 h-[calc(4.75rem+env(safe-area-inset-bottom,0))] w-full"
        >
          <div
            class="bg-canvas fixed right-0 bottom-5 left-0 z-10 mx-auto flex max-w-[500px] shrink-0 justify-end gap-4"
          >
            <Button
              severity="secondary"
              type="button"
              variant="outlined"
              :label="t('common.actions.cancel')"
              @click="onCloseActionModal"
            />

            <Button
              type="submit"
              :label="t('common.actions.submit')"
            />
          </div>
        </div>
      </form>
    </MMainDialog>
  </TheLayout>
</template>
