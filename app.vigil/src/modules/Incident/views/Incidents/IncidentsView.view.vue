<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';
import type { Incident } from '../../interfaces';
import { AcknowledgeIncidentService } from '../../services';
import { useIncidentStore } from '../../store';

const incidentStore = useIncidentStore();

const { t } = useI18n();
const toast = useToast();

const { incidents, loading } = storeToRefs(incidentStore);

const onlyOpen = ref(false);

const filterOptions = computed(() => [
  { label: t('incidents.all'), value: false },
  { label: t('incidents.open'), value: true },
]);

watch(onlyOpen, onFilterChange);

onMounted(onComponentMount);

// HELPERS
function loadIncidents() {
  incidentStore.fetchIncidents({ open: onlyOpen.value || undefined }).catch(() => notifyError());
}

function durationLabel(incident: Incident): string {
  if (incident.durationSeconds === null) {
    return t('incidents.ongoing');
  }

  const minutes = Math.round(incident.durationSeconds / 60);

  return `${minutes}m`;
}

function notifyError() {
  toast.add({ severity: 'error', summary: t('errors.somethingWentWrong'), life: 5000 });
}

// EVENTS
function onComponentMount() {
  loadIncidents();
}

function onFilterChange() {
  loadIncidents();
}

function onAcknowledge(incident: Incident) {
  AcknowledgeIncidentService(incident.id)
    .then(({ data }) => {
      incidentStore.replaceIncident(data);
      toast.add({ severity: 'success', summary: t('incidents.acknowledgedSuccess'), life: 5000 });
    })
    .catch(() => notifyError());
}
</script>

<template>
  <TheLayout>
    <template #pageHeader>
      <ThePageHeader title="incidents.title">
        <template #actions>
          <SelectButton
            v-model="onlyOpen"
            optionLabel="label"
            optionValue="value"
            :allowEmpty="false"
            :options="filterOptions"
          />
        </template>
      </ThePageHeader>
    </template>

    <main>
      <DataTable
        data-testid="incidents-table"
        :loading
        :value="incidents"
      >
        <template #empty>
          <span class="text-subtle text-sm">{{ $t('incidents.empty') }}</span>
        </template>

        <Column
          field="monitorName"
          :header="$t('incidents.monitor')"
        />

        <Column :header="$t('incidents.status')">
          <template #body="{ data }">
            <Tag
              rounded
              :severity="data.isOpen ? 'danger' : 'success'"
              :value="data.isOpen ? $t('incidents.statusOpen') : $t('incidents.statusResolved')"
            />
          </template>
        </Column>

        <Column
          field="startedAt"
          :header="$t('incidents.startedAt')"
        />

        <Column
          field="cause"
          :header="$t('incidents.cause')"
        />

        <Column :header="$t('incidents.duration')">
          <template #body="{ data }">
            {{ durationLabel(data) }}
          </template>
        </Column>

        <Column :header="$t('incidents.acknowledge')">
          <template #body="{ data }">
            <Tag
              v-if="data.acknowledgedAt"
              :value="$t('incidents.acknowledged')"
            />

            <Button
              v-else
              size="small"
              :label="$t('incidents.acknowledge')"
              @click="onAcknowledge(data)"
            />
          </template>
        </Column>
      </DataTable>
    </main>
  </TheLayout>
</template>
