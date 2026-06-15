<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useProjectStore } from '../../../store';

const projectStore = useProjectStore();

const hasProjects = computed(() => projectStore.projects.length > 0);

const projectOptions = computed(() =>
  projectStore.projects.map((project) => ({ label: project.name, value: project.id }))
);

onMounted(onComponentMount);

// EVENTS
function onComponentMount() {
  if (projectStore.projects.length === 0) {
    projectStore.fetchProjects();
  }
}

function onProjectChange(id: string) {
  projectStore.setActiveProject(id);
}
</script>

<template>
  <Select
    v-if="hasProjects"
    class="w-48"
    optionLabel="label"
    optionValue="value"
    :modelValue="projectStore.activeProjectId"
    :options="projectOptions"
    :placeholder="$t('projects.switcher.placeholder')"
    @update:modelValue="onProjectChange"
  />
</template>
