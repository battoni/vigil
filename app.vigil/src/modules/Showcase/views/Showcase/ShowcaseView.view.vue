<script setup lang="ts">
import { computed, ref } from 'vue';
import type { Option } from '@Interfaces';

const orderByOptions: Option[] = [
  { label: 'Name A–Z', value: 'name_asc' },
  { label: 'Name Z–A', value: 'name_desc' },
  { label: 'Newest first', value: 'date_desc' },
  { label: 'Oldest first', value: 'date_asc' },
];

const longDialogVisible = ref(false);
const orderBy = ref('name_asc');
const search = ref('');
const shortDialogVisible = ref(false);

const isAnyDialogOpen = computed(() => longDialogVisible.value || shortDialogVisible.value);

// EVENTS
function onLongFormSuccess() {
  longDialogVisible.value = false;
}

function onShortFormSuccess() {
  shortDialogVisible.value = false;
}
</script>

<template>
  <TheLayout>
    <template #pageHeader>
      <ThePageHeader title="Showcase">
        <template
          v-if="!isAnyDialogOpen"
          #actions
        >
          <div class="flex items-center gap-3">
            <Button
              class="celer-button-soft-primary shadow-none"
              icon="pi pi-download"
              label="Export"
              @click="shortDialogVisible = true"
            />

            <Button
              class="celer-button-primary shadow-none"
              icon="pi pi-plus"
              label="Add Item"
              @click="longDialogVisible = true"
            />
          </div>
        </template>
      </ThePageHeader>
    </template>

    <TheFilters>
      <InputGroup class="sm:w-fit">
        <InputGroupAddon class="border-none shadow">
          <i class="pi pi-search" />
        </InputGroupAddon>

        <InputText
          v-model="search"
          class="border-none shadow"
          placeholder="Search…"
        />
      </InputGroup>

      <MOrderBy
        v-model:orderBy="orderBy"
        :options="orderByOptions"
      />
    </TheFilters>

    <aside>
      <MMainDialog
        v-model:visible="shortDialogVisible"
        isFooterless
        title="Export"
      >
        <MShowcaseShortForm
          v-if="shortDialogVisible"
          @onClose="shortDialogVisible = false"
          @onSuccess="onShortFormSuccess"
        />
      </MMainDialog>

      <MMainDialog
        v-model:visible="longDialogVisible"
        isFooterless
        title="Add Item"
      >
        <MShowcaseLongForm
          v-if="longDialogVisible"
          @onClose="longDialogVisible = false"
          @onSuccess="onLongFormSuccess"
        />
      </MMainDialog>
    </aside>
  </TheLayout>
</template>
