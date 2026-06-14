<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useToast } from 'primevue/usetoast';

type StatusTone = 'error' | 'success' | 'warning';
type TypeTone = 'danger' | 'info' | 'success' | 'warn';

interface ShowcaseTx extends Record<string, unknown> {
  id: string;
  amount: string;
  date: string;
  entity: string;
  status: string;
  statusTone: StatusTone;
  type: string;
  typeTone: TypeTone;
}

interface ShowcaseDataset {
  key: string;
  label: string;
  items: Record<string, unknown>[];
  title: { valueKey: string };
  amount: { valueKey: string; valueFormatter?: (raw: unknown) => string };
  badge: { valueKey: string; toneKey: string };
  badgeIconMap: Record<string, string>;
  status: { valueKey: string; toneKey: string };
  statusIconMap: Record<string, string>;
  subtitle: { valueKey: string };
}

const toast = useToast();

const kanbanStatusBg: Record<StatusTone, string> = {
  error: 'bg-danger-50',
  success: 'bg-success-50',
  warning: 'bg-warn-50',
};

const kanbanStatusBorder: Record<StatusTone, string> = {
  error: 'border-danger-200',
  success: 'border-success-200',
  warning: 'border-warn-200',
};

const kanbanStatusText: Record<StatusTone, string> = {
  error: 'text-danger-700',
  success: 'text-success-700',
  warning: 'text-warn-700',
};

const multiSelectOptions = [
  { label: 'Design System', value: 'design-system' },
  { label: 'Theme Tokens', value: 'theme-tokens' },
  { label: 'UX Writing', value: 'ux-writing' },
];

const sampleTransactions = [
  {
    id: 'showcase-1',
    amount: 'R$ 5.000',
    date: '05/05/2026',
    entity: 'Battoni Dev',
    status: 'Done',
    statusTone: 'success' as const,
    type: 'Deposit',
    typeTone: 'success' as const,
  },
  {
    id: 'showcase-2',
    amount: 'R$ 1.250',
    date: '04/05/2026',
    entity: 'Acme Ltda',
    status: 'Scheduled',
    statusTone: 'warning' as const,
    type: 'Payment',
    typeTone: 'info' as const,
  },
  {
    id: 'showcase-3',
    amount: 'R$ 700',
    date: '03/05/2026',
    entity: 'Beta Corp',
    status: 'Canceled',
    statusTone: 'error' as const,
    type: 'Refund',
    typeTone: 'danger' as const,
  },
];

const selectOptions = [
  { label: 'Design', value: 'design' },
  { label: 'Engineering', value: 'engineering' },
  { label: 'Product', value: 'product' },
];

const showcaseItems: ShowcaseTx[] = [
  {
    id: 'sc-1',
    amount: 'R$ 100.000',
    date: '10/04/2026',
    entity: 'Battoni Dev',
    status: 'Done',
    statusTone: 'success',
    type: 'Deposit',
    typeTone: 'success',
  },
  {
    id: 'sc-2',
    amount: 'R$ 30.000',
    date: '10/04/2026',
    entity: 'Battoni Dev',
    status: 'Canceled',
    statusTone: 'error',
    type: 'Withdraw',
    typeTone: 'warn',
  },
  {
    id: 'sc-3',
    amount: 'R$ 20.000',
    date: '12/04/2026',
    entity: 'Outra Empresa',
    status: 'Scheduled',
    statusTone: 'warning',
    type: 'Payment',
    typeTone: 'info',
  },
  {
    id: 'sc-4',
    amount: 'R$ 10.000',
    date: '12/04/2026',
    entity: 'Empresa 2',
    status: 'Done',
    statusTone: 'success',
    type: 'Refund',
    typeTone: 'danger',
  },
  {
    id: 'sc-5',
    amount: 'R$ 50.000',
    date: '15/04/2026',
    entity: 'Acme Corp',
    status: 'Done',
    statusTone: 'success',
    type: 'Deposit',
    typeTone: 'success',
  },
  {
    id: 'sc-6',
    amount: 'R$ 5.000',
    date: '15/04/2026',
    entity: 'Beta Ltda',
    status: 'Scheduled',
    statusTone: 'warning',
    type: 'Payment',
    typeTone: 'info',
  },
];

const txStatusClass: Record<StatusTone, string> = {
  error: 'celer-tag-soft-danger',
  success: 'celer-tag-soft-success',
  warning: 'celer-tag-soft-warn',
};

const txStatusIcon: Record<StatusTone, string> = {
  error: 'pi pi-ban',
  success: 'pi pi-check-circle',
  warning: 'pi pi-clock',
};

const txTypeClass: Record<TypeTone, string> = {
  danger: 'celer-tag-soft-danger',
  info: 'celer-tag-soft-info',
  success: 'celer-tag-soft-success',
  warn: 'celer-tag-soft-warn',
};

const txTypeIcon: Record<string, string> = {
  Deposit: 'pi pi-arrow-down-left',
  Payment: 'pi pi-credit-card',
  Refund: 'pi pi-replay',
  Withdraw: 'pi pi-arrow-up-right',
};

const dateGroups: { date: string; items: ShowcaseTx[] }[] = [
  { date: '10/04/2026', items: showcaseItems.slice(0, 2) },
  { date: '12/04/2026', items: showcaseItems.slice(2, 4) },
  { date: '15/04/2026', items: showcaseItems.slice(4, 6) },
];

const kanbanColumns: { label: string; statusTone: StatusTone; items: ShowcaseTx[] }[] = [
  { label: 'Done', statusTone: 'success', items: showcaseItems.filter((item) => item.statusTone === 'success') },
  { label: 'Scheduled', statusTone: 'warning', items: showcaseItems.filter((item) => item.statusTone === 'warning') },
  { label: 'Canceled', statusTone: 'error', items: showcaseItems.filter((item) => item.statusTone === 'error') },
];

const crudItems = [
  { id: 'c1', name: 'Marketing Plan', owner: 'Alice', status: 'Active', statusTone: 'success', durationMinutes: 12 },
  { id: 'c2', name: 'Q3 Budget', owner: 'Bob', status: 'Draft', statusTone: 'warning', durationMinutes: 8 },
  { id: 'c3', name: 'Onboarding Guide', owner: 'Carol', status: 'Archived', statusTone: 'error', durationMinutes: 20 },
];

// Three interchangeable datasets prove the OList* family renders any shape on demand.
const financialDataset: ShowcaseDataset = {
  key: 'financial',
  label: 'Financial',
  items: showcaseItems,
  title: { valueKey: 'entity' },
  amount: { valueKey: 'amount' },
  badge: { valueKey: 'type', toneKey: 'typeTone' },
  badgeIconMap: txTypeIcon,
  status: { valueKey: 'status', toneKey: 'statusTone' },
  statusIconMap: txStatusIcon,
  subtitle: { valueKey: 'date' },
};

const tasksDataset: ShowcaseDataset = {
  key: 'tasks',
  label: 'Tasks',
  items: [
    {
      id: 't1',
      name: 'Design landing page',
      durationMinutes: 45,
      priority: 'High',
      priorityTone: 'danger',
      state: 'Doing',
      stateTone: 'warning',
      assignee: 'Alice',
    },
    {
      id: 't2',
      name: 'Write API docs',
      durationMinutes: 30,
      priority: 'Medium',
      priorityTone: 'warn',
      state: 'Todo',
      stateTone: 'info',
      assignee: 'Bob',
    },
    {
      id: 't3',
      name: 'Fix login bug',
      durationMinutes: 15,
      priority: 'High',
      priorityTone: 'danger',
      state: 'Done',
      stateTone: 'success',
      assignee: 'Carol',
    },
    {
      id: 't4',
      name: 'Review PRs',
      durationMinutes: 20,
      priority: 'Low',
      priorityTone: 'info',
      state: 'Done',
      stateTone: 'success',
      assignee: 'Dave',
    },
  ],
  title: { valueKey: 'name' },
  amount: { valueKey: 'durationMinutes', valueFormatter: formatMinutes },
  badge: { valueKey: 'priority', toneKey: 'priorityTone' },
  badgeIconMap: { High: 'pi pi-flag-fill', Medium: 'pi pi-flag', Low: 'pi pi-minus' },
  status: { valueKey: 'state', toneKey: 'stateTone' },
  statusIconMap: { success: 'pi pi-check-circle', warning: 'pi pi-clock', info: 'pi pi-hourglass' },
  subtitle: { valueKey: 'assignee' },
};

const inventoryDataset: ShowcaseDataset = {
  key: 'inventory',
  label: 'Inventory',
  items: [
    {
      id: 'i1',
      name: 'Couché 150g',
      quantity: 1200,
      category: 'Paper',
      categoryTone: 'primary',
      stock: 'In stock',
      stockTone: 'success',
      sku: 'PPR-150',
    },
    {
      id: 'i2',
      name: 'Vinyl Banner',
      quantity: 80,
      category: 'Vinyl',
      categoryTone: 'info',
      stock: 'Low',
      stockTone: 'warning',
      sku: 'VNL-010',
    },
    {
      id: 'i3',
      name: 'Black Toner',
      quantity: 0,
      category: 'Ink',
      categoryTone: 'warn',
      stock: 'Out',
      stockTone: 'error',
      sku: 'INK-BK',
    },
    {
      id: 'i4',
      name: 'A4 Sulfite',
      quantity: 5000,
      category: 'Paper',
      categoryTone: 'primary',
      stock: 'In stock',
      stockTone: 'success',
      sku: 'PPR-A4',
    },
  ],
  title: { valueKey: 'name' },
  amount: { valueKey: 'quantity', valueFormatter: formatSheets },
  badge: { valueKey: 'category', toneKey: 'categoryTone' },
  badgeIconMap: { Paper: 'pi pi-file', Vinyl: 'pi pi-image', Ink: 'pi pi-palette' },
  status: { valueKey: 'stock', toneKey: 'stockTone' },
  statusIconMap: { success: 'pi pi-check-circle', warning: 'pi pi-exclamation-triangle', error: 'pi pi-times-circle' },
  subtitle: { valueKey: 'sku' },
};

const showcaseDatasets = [financialDataset, tasksDataset, inventoryDataset];
const datasetOptions = showcaseDatasets.map((dataset) => ({ label: dataset.label, value: dataset.key }));

const dialogVisible = ref(false);
const inputValue = ref('Battoni Dev');
const multiSelectValue = ref<string[]>(['design-system']);
const passwordValue = ref('password123');
const selectValue = ref('design');
const showcaseTab = ref('overview');
const textareaValue = ref('This is a sample textarea field.');

const datasetKeyByDemo = reactive({
  divided: 'financial',
  cardGrid: 'financial',
  accentStrip: 'financial',
  statement: 'financial',
  feed: 'financial',
});

// HELPERS
function datasetFor(demo: keyof typeof datasetKeyByDemo): ShowcaseDataset {
  return showcaseDatasets.find((dataset) => dataset.key === datasetKeyByDemo[demo]) ?? financialDataset;
}

function formatMinutes(raw: unknown): string {
  return `${raw} min`;
}

function formatSheets(raw: unknown): string {
  return `${raw} folhas`;
}

// EVENTS
function onCrudDelete(item: Record<string, unknown>) {
  toast.add({ severity: 'error', summary: 'Delete', detail: `Delete ${item.name}`, life: 3000 });
}

function onCrudEdit(item: Record<string, unknown>) {
  toast.add({ severity: 'info', summary: 'Edit', detail: `Edit ${item.name}`, life: 3000 });
}

function onCrudHistory(item: Record<string, unknown>) {
  toast.add({ severity: 'info', summary: 'History', detail: `History for ${item.name}`, life: 3000 });
}

function onTabEdit() {
  toast.add({
    severity: 'info',
    summary: 'Edit action',
    detail: 'Tab actions slot triggered.',
    life: 3000,
  });
}

function showToast(severity: 'error' | 'info' | 'success' | 'warn') {
  toast.add({
    severity,
    summary: `Toast ${severity}`,
    detail: 'This is a sample toast from the component showcase.',
    life: 3500,
  });
}
</script>

<template>
  <TheLayout>
    <template #pageHeader>
      <ThePageHeader title="Components" />
    </template>

    <div class="flex flex-col gap-8">
      <section class="border-line bg-canvas flex flex-col gap-4 rounded-lg border p-6">
        <h2 class="text-heading text-lg font-semibold">Typography</h2>

        <div class="flex flex-col gap-3">
          <h1 class="text-heading text-3xl font-semibold">Heading 1</h1>

          <h2 class="text-heading text-2xl font-semibold">Heading 2</h2>

          <h3 class="text-heading text-xl font-semibold">Heading 3</h3>

          <h4 class="text-heading text-lg font-semibold">Heading 4</h4>

          <p class="text-heading text-base">Base body text for default reading content.</p>

          <p class="text-muted text-sm">Form label / secondary text role.</p>

          <p class="text-muted text-sm">Muted supporting text role.</p>
        </div>
      </section>

      <section class="border-line bg-canvas flex flex-col gap-4 rounded-lg border p-6">
        <h2 class="text-heading text-lg font-semibold">Buttons</h2>

        <div class="flex flex-wrap items-center gap-3">
          <Button label="Primary" />

          <Button
            label="Secondary"
            severity="secondary"
          />

          <Button
            label="Success"
            severity="success"
          />

          <Button
            label="Warning"
            severity="warn"
          />

          <Button
            label="Danger"
            severity="danger"
          />

          <Button
            label="Info"
            severity="info"
          />

          <Button
            label="Outlined"
            variant="outlined"
          />

          <Button
            text
            label="Text"
          />

          <Button
            icon="pi pi-plus"
            label="With Icon"
          />

          <Button
            disabled
            label="Disabled"
          />
        </div>
      </section>

      <section class="border-line bg-canvas flex flex-col gap-4 rounded-lg border p-6">
        <h2 class="text-heading text-lg font-semibold">Tabs (view mode)</h2>

        <OTabView v-model="showcaseTab">
          <OTabPanel
            title="Overview"
            value="overview"
          >
            <template #actions>
              <Button
                text
                icon="pi pi-pencil"
                size="small"
                @click="onTabEdit"
              />
            </template>

            <p class="text-muted text-sm">Overview tab content rendered from the default slot.</p>
          </OTabPanel>

          <OTabPanel
            icon="pi pi-bolt"
            title="Activity"
            value="activity"
          >
            <p class="text-muted text-sm">Activity tab content rendered from the default slot.</p>
          </OTabPanel>
        </OTabView>
      </section>

      <section class="border-line bg-canvas flex flex-col gap-4 rounded-lg border p-6">
        <h2 class="text-heading text-lg font-semibold">Form Inputs</h2>

        <form class="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <div class="flex flex-col gap-2">
            <label
              class="text-muted mb-2 block text-sm"
              for="showcase-inputtext"
            >
              InputText
            </label>

            <InputText
              v-model="inputValue"
              id="showcase-inputtext"
            />
          </div>

          <div class="flex flex-col gap-2">
            <label
              class="text-muted mb-2 block text-sm"
              for="showcase-password"
            >
              Password
            </label>

            <Password
              v-model="passwordValue"
              toggleMask
              id="showcase-password"
            />
          </div>

          <div class="flex flex-col gap-2">
            <label
              class="text-muted mb-2 block text-sm"
              for="showcase-select"
            >
              Select
            </label>

            <Select
              v-model="selectValue"
              id="showcase-select"
              optionLabel="label"
              optionValue="value"
              :options="selectOptions"
            />
          </div>

          <div class="flex flex-col gap-2">
            <label
              class="text-muted mb-2 block text-sm"
              for="showcase-multiselect"
            >
              MultiSelect
            </label>

            <MultiSelect
              v-model="multiSelectValue"
              id="showcase-multiselect"
              display="chip"
              optionLabel="label"
              optionValue="value"
              :options="multiSelectOptions"
            />
          </div>

          <div class="flex flex-col gap-2 lg:col-span-2">
            <label
              class="text-muted mb-2 block text-sm"
              for="showcase-textarea"
            >
              Textarea
            </label>

            <Textarea
              v-model="textareaValue"
              id="showcase-textarea"
              rows="4"
            />
          </div>
        </form>
      </section>

      <section class="border-line bg-canvas flex flex-col gap-4 rounded-lg border p-6">
        <h2 class="text-heading text-lg font-semibold">Form Validation</h2>

        <div class="flex flex-col gap-4">
          <AFormError error="This field is required." />

          <AFormError error="An unexpected error happened: timeout while contacting API gateway." />
        </div>
      </section>

      <section class="border-line bg-canvas flex flex-col gap-4 rounded-lg border p-6">
        <h2 class="text-heading text-lg font-semibold">Cards</h2>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
          <MWalletCard
            description="Sample primary card description."
            title="Primary"
            value="R$ 18.000"
            variant="primary"
          />

          <MWalletCard
            description="Sample info card description."
            title="Info"
            value="R$ 14.500"
            variant="info"
          />

          <MWalletCard
            description="Sample success card description."
            title="Success"
            value="R$ 9.200"
            variant="success"
          />

          <MWalletCard
            description="Sample warning card description."
            title="Warning"
            value="R$ 4.300"
            variant="warning"
          />
        </div>
      </section>

      <section class="border-line bg-canvas flex flex-col gap-4 rounded-lg border p-6">
        <h2 class="text-heading text-lg font-semibold">DataTable</h2>

        <OWalletTransactions :transactions="sampleTransactions" />
      </section>

      <section class="border-line bg-canvas flex flex-col gap-4 rounded-lg border p-6">
        <h2 class="text-heading text-lg font-semibold">Dialog</h2>

        <div class="flex items-center gap-3">
          <Button
            label="Open Dialog"
            @click="dialogVisible = true"
          />
        </div>

        <aside>
          <MMainDialog
            v-model:visible="dialogVisible"
            isFooterless
          >
            <div class="flex flex-col gap-4">
              <p class="text-muted text-sm">This is a simple showcase dialog with static content.</p>

              <div class="flex justify-end">
                <Button
                  label="Close"
                  severity="secondary"
                  variant="outlined"
                  @click="dialogVisible = false"
                />
              </div>
            </div>
          </MMainDialog>
        </aside>
      </section>

      <section class="border-line bg-canvas flex flex-col gap-4 rounded-lg border p-6">
        <h2 class="text-heading text-lg font-semibold">Toast</h2>

        <div class="flex flex-wrap items-center gap-3">
          <Button
            label="Success Toast"
            severity="success"
            @click="showToast('success')"
          />

          <Button
            label="Info Toast"
            severity="info"
            @click="showToast('info')"
          />

          <Button
            label="Warn Toast"
            severity="warn"
            @click="showToast('warn')"
          />

          <Button
            label="Error Toast"
            severity="danger"
            @click="showToast('error')"
          />
        </div>
      </section>

      <section class="border-line bg-canvas flex flex-col gap-4 rounded-lg border p-6">
        <h2 class="text-heading text-lg font-semibold">Messages</h2>

        <div class="flex flex-col gap-3">
          <Message severity="success">Success message sample.</Message>

          <Message severity="info">Info message sample.</Message>

          <Message severity="warn">Warning message sample.</Message>

          <Message severity="error">Error message sample.</Message>
        </div>
      </section>

      <section class="border-line bg-canvas flex flex-col gap-4 rounded-lg border p-6">
        <h2 class="text-heading text-lg font-semibold">Tags and Badges</h2>

        <div class="flex flex-wrap items-center gap-3">
          <Tag
            severity="success"
            value="Success"
          />

          <Tag
            severity="info"
            value="Info"
          />

          <Tag
            severity="warn"
            value="Warn"
          />

          <Tag
            severity="danger"
            value="Danger"
          />

          <Badge
            severity="success"
            value="7"
          />

          <Badge
            severity="info"
            value="12"
          />

          <Badge
            severity="warn"
            value="3"
          />

          <Badge
            severity="danger"
            value="1"
          />
        </div>
      </section>

      <section class="border-line bg-canvas flex flex-col gap-4 rounded-lg border p-6">
        <h2 class="text-heading text-lg font-semibold">Managed CRUD list (non-financial)</h2>

        <OListDivided
          canDelete
          canEdit
          showDetails
          :amount="{ valueKey: 'durationMinutes', valueFormatter: formatMinutes }"
          :items="crudItems"
          :status="{ valueKey: 'status', toneKey: 'statusTone' }"
          :title="{ valueKey: 'name' }"
          @onDelete="onCrudDelete"
          @onEdit="onCrudEdit"
        >
          <template #actions="{ item }">
            <Button
              rounded
              text
              class="h-7 w-7 shrink-0"
              icon="pi pi-history"
              severity="secondary"
              @click="onCrudHistory(item)"
            />
          </template>

          <template #details="{ item }">
            <div class="flex flex-col gap-2">
              <p class="text-muted text-sm">Owner: {{ item.owner }}</p>

              <p class="text-muted text-sm">Duration: {{ item.durationMinutes }} min</p>
            </div>
          </template>
        </OListDivided>
      </section>

      <section class="border-line bg-canvas flex flex-col gap-4 rounded-lg border p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <h2 class="text-heading text-lg font-semibold">#1 · Divided List</h2>

          <SelectButton
            v-model="datasetKeyByDemo.divided"
            optionLabel="label"
            optionValue="value"
            :allowEmpty="false"
            :options="datasetOptions"
          />
        </div>

        <OListDivided
          showDetails
          :amount="datasetFor('divided').amount"
          :badge="datasetFor('divided').badge"
          :items="datasetFor('divided').items"
          :status="datasetFor('divided').status"
          :statusIconMap="datasetFor('divided').statusIconMap"
          :subtitle="datasetFor('divided').subtitle"
          :title="datasetFor('divided').title"
        />
      </section>

      <section class="border-line bg-canvas flex flex-col gap-4 rounded-lg border p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <h2 class="text-heading text-lg font-semibold">#2 · Card Grid</h2>

          <SelectButton
            v-model="datasetKeyByDemo.cardGrid"
            optionLabel="label"
            optionValue="value"
            :allowEmpty="false"
            :options="datasetOptions"
          />
        </div>

        <OListCardGrid
          showDetails
          :amount="datasetFor('cardGrid').amount"
          :badge="datasetFor('cardGrid').badge"
          :items="datasetFor('cardGrid').items"
          :status="datasetFor('cardGrid').status"
          :statusIconMap="datasetFor('cardGrid').statusIconMap"
          :subtitle="datasetFor('cardGrid').subtitle"
          :title="datasetFor('cardGrid').title"
        />
      </section>

      <section class="border-line bg-canvas flex flex-col gap-4 rounded-lg border p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <h2 class="text-heading text-lg font-semibold">#3 · Left Accent Strip</h2>

          <SelectButton
            v-model="datasetKeyByDemo.accentStrip"
            optionLabel="label"
            optionValue="value"
            :allowEmpty="false"
            :options="datasetOptions"
          />
        </div>

        <OListAccentStrip
          showDetails
          :amount="datasetFor('accentStrip').amount"
          :badge="datasetFor('accentStrip').badge"
          :items="datasetFor('accentStrip').items"
          :status="datasetFor('accentStrip').status"
          :statusIconMap="datasetFor('accentStrip').statusIconMap"
          :subtitle="datasetFor('accentStrip').subtitle"
          :title="datasetFor('accentStrip').title"
        />
      </section>

      <section class="border-line bg-canvas flex flex-col gap-4 rounded-lg border p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <h2 class="text-heading text-lg font-semibold">#4 · Bank Statement</h2>

          <SelectButton
            v-model="datasetKeyByDemo.statement"
            optionLabel="label"
            optionValue="value"
            :allowEmpty="false"
            :options="datasetOptions"
          />
        </div>

        <OListStatement
          showDetails
          :amount="datasetFor('statement').amount"
          :badge="datasetFor('statement').badge"
          :items="datasetFor('statement').items"
          :status="datasetFor('statement').status"
          :statusIconMap="datasetFor('statement').statusIconMap"
          :subtitle="datasetFor('statement').subtitle"
          :title="datasetFor('statement').title"
        />
      </section>

      <section class="border-line bg-canvas flex flex-col gap-4 rounded-lg border p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <h2 class="text-heading text-lg font-semibold">#6 · Activity Feed</h2>

          <SelectButton
            v-model="datasetKeyByDemo.feed"
            optionLabel="label"
            optionValue="value"
            :allowEmpty="false"
            :options="datasetOptions"
          />
        </div>

        <OListFeed
          showDetails
          :amount="datasetFor('feed').amount"
          :badge="datasetFor('feed').badge"
          :badgeIconMap="datasetFor('feed').badgeIconMap"
          :items="datasetFor('feed').items"
          :status="datasetFor('feed').status"
          :statusIconMap="datasetFor('feed').statusIconMap"
          :subtitle="datasetFor('feed').subtitle"
          :title="datasetFor('feed').title"
        />
      </section>

      <section class="flex flex-col gap-6">
        <div class="flex items-center gap-3">
          <h2 class="text-heading text-lg font-semibold">In Development</h2>

          <Tag
            severity="warn"
            value="Coming soon"
          />
        </div>

        <div class="flex flex-col gap-8">
          <div class="flex flex-col gap-3">
            <p class="text-muted text-xs font-semibold tracking-wide uppercase">#5 · Grouped by date</p>

            <div class="flex flex-col gap-5">
              <div
                v-for="group in dateGroups"
                class="flex flex-col gap-2"
                :key="group.date"
              >
                <p class="text-muted text-xs font-semibold tracking-wider uppercase">{{ group.date }}</p>

                <div class="border-line bg-canvas dark:bg-panel overflow-hidden rounded-xl border">
                  <div
                    v-for="item in group.items"
                    class="border-line flex items-center justify-between gap-4 border-b px-4 py-3 last:border-b-0"
                    :key="item.id"
                  >
                    <div class="flex min-w-0 items-center gap-3">
                      <Tag
                        rounded
                        :class="txTypeClass[item.typeTone]"
                        :value="item.type"
                      />

                      <span class="text-heading truncate text-sm">{{ item.entity }}</span>
                    </div>

                    <div class="flex shrink-0 items-center gap-3">
                      <span class="text-heading text-sm font-semibold">{{ item.amount }}</span>

                      <Tag
                        rounded
                        :class="txStatusClass[item.statusTone]"
                        :value="item.status"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="flex flex-col gap-3">
            <p class="text-muted text-xs font-semibold tracking-wide uppercase">#7 · Label-value grid</p>

            <div class="border-line bg-canvas dark:bg-panel overflow-hidden rounded-xl border">
              <div
                v-for="item in showcaseItems"
                class="border-line grid grid-cols-2 gap-3 border-b px-4 py-3 last:border-b-0 sm:grid-cols-5"
                :key="item.id"
              >
                <div class="flex flex-col gap-1">
                  <span class="text-subtle text-xs">Type</span>

                  <Tag
                    rounded
                    :class="txTypeClass[item.typeTone]"
                    :value="item.type"
                  />
                </div>

                <div class="flex flex-col gap-1">
                  <span class="text-subtle text-xs">Entity</span>

                  <span class="text-heading text-sm font-medium">{{ item.entity }}</span>
                </div>

                <div class="flex flex-col gap-1">
                  <span class="text-subtle text-xs">Amount</span>

                  <span class="text-heading text-sm font-semibold">{{ item.amount }}</span>
                </div>

                <div class="flex flex-col gap-1">
                  <span class="text-subtle text-xs">Date</span>

                  <span class="text-muted text-sm">{{ item.date }}</span>
                </div>

                <div class="flex flex-col gap-1">
                  <span class="text-subtle text-xs">Status</span>

                  <Tag
                    rounded
                    :class="txStatusClass[item.statusTone]"
                    :icon="txStatusIcon[item.statusTone]"
                    :value="item.status"
                  />
                </div>
              </div>
            </div>
          </div>

          <div class="flex flex-col gap-3">
            <p class="text-muted text-xs font-semibold tracking-wide uppercase">#8 · Amount hero</p>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
              <div
                v-for="item in showcaseItems"
                class="border-line bg-canvas dark:bg-panel flex flex-col items-center gap-2 rounded-xl border p-4 text-center"
                :key="item.id"
              >
                <Tag
                  rounded
                  :class="txTypeClass[item.typeTone]"
                  :value="item.type"
                />

                <p class="text-heading text-2xl font-bold">{{ item.amount }}</p>

                <p class="text-heading text-sm font-medium">{{ item.entity }}</p>

                <div class="flex items-center gap-2">
                  <span class="text-subtle text-xs">{{ item.date }}</span>

                  <Tag
                    rounded
                    :class="txStatusClass[item.statusTone]"
                    :value="item.status"
                  />
                </div>
              </div>
            </div>
          </div>

          <div class="flex flex-col gap-3">
            <p class="text-muted text-xs font-semibold tracking-wide uppercase">#9 · Responsive grid table</p>

            <div class="border-line bg-canvas dark:bg-panel overflow-hidden rounded-xl border">
              <div class="border-line bg-canvas dark:bg-panel hidden grid-cols-5 border-b px-4 py-2 sm:grid">
                <span class="text-heading text-xs font-semibold">Type</span>

                <span class="text-heading text-xs font-semibold">Entity</span>

                <span class="text-heading text-xs font-semibold">Amount</span>

                <span class="text-heading text-xs font-semibold">Date</span>

                <span class="text-heading text-xs font-semibold">Status</span>
              </div>

              <div
                v-for="item in showcaseItems"
                class="border-line border-b last:border-b-0"
                :key="item.id"
              >
                <div class="flex items-center justify-between gap-3 px-4 py-3 sm:hidden">
                  <div class="flex min-w-0 flex-col gap-1.5">
                    <Tag
                      rounded
                      :class="txTypeClass[item.typeTone]"
                      :value="item.type"
                    />

                    <span class="text-heading truncate text-sm">{{ item.entity }}</span>

                    <span class="text-subtle text-xs">{{ item.date }}</span>
                  </div>

                  <div class="flex shrink-0 flex-col items-end gap-1.5">
                    <span class="text-heading font-semibold">{{ item.amount }}</span>

                    <Tag
                      rounded
                      :class="txStatusClass[item.statusTone]"
                      :value="item.status"
                    />
                  </div>
                </div>

                <div class="hidden grid-cols-5 items-center gap-3 px-4 py-3 sm:grid">
                  <Tag
                    rounded
                    :class="txTypeClass[item.typeTone]"
                    :value="item.type"
                  />

                  <span class="text-heading text-sm">{{ item.entity }}</span>

                  <span class="text-heading text-sm font-semibold">{{ item.amount }}</span>

                  <span class="text-muted text-sm">{{ item.date }}</span>

                  <Tag
                    rounded
                    :class="txStatusClass[item.statusTone]"
                    :icon="txStatusIcon[item.statusTone]"
                    :value="item.status"
                  />
                </div>
              </div>
            </div>
          </div>

          <div class="flex flex-col gap-3">
            <p class="text-muted text-xs font-semibold tracking-wide uppercase">#10 · Kanban by status</p>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
              <div
                v-for="column in kanbanColumns"
                class="flex flex-col overflow-hidden rounded-xl border"
                :key="column.statusTone"
                :class="kanbanStatusBorder[column.statusTone]"
              >
                <div
                  class="flex items-center justify-between px-4 py-3"
                  :class="kanbanStatusBg[column.statusTone]"
                >
                  <span
                    class="text-sm font-semibold"
                    :class="kanbanStatusText[column.statusTone]"
                  >
                    {{ column.label }}
                  </span>

                  <span
                    class="text-xs font-medium"
                    :class="kanbanStatusText[column.statusTone]"
                  >
                    {{ column.items.length }}
                  </span>
                </div>

                <div class="divide-line bg-canvas dark:bg-panel flex flex-col divide-y">
                  <div
                    v-for="item in column.items"
                    class="flex flex-col gap-2 px-4 py-3"
                    :key="item.id"
                  >
                    <div class="flex items-start justify-between gap-2">
                      <Tag
                        rounded
                        :class="txTypeClass[item.typeTone]"
                        :value="item.type"
                      />

                      <span class="text-heading text-sm font-semibold">{{ item.amount }}</span>
                    </div>

                    <div>
                      <p class="text-heading text-sm font-medium">{{ item.entity }}</p>

                      <p class="text-muted text-xs">{{ item.date }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </TheLayout>
</template>
