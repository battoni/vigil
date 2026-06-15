<script setup lang="ts">
import { computed, ref } from 'vue';
import { yupResolver } from '@primevue/forms/resolvers/yup';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';
import * as yup from 'yup';
import type { Monitor } from '../../../interfaces';
import type { MonitorType } from '../../../types';
import type { FormSubmitEvent } from '@primevue/forms';
import { MONITOR_INTERVAL_OPTIONS, MONITOR_TYPE_OPTIONS } from '../../../constants';
import { MONITOR_TYPE } from '../../../enums';
import { CreateMonitorService, UpdateMonitorService } from '../../../services';

const emit = defineEmits<{
  onClose: [];
  onSuccess: [monitor: Monitor];
}>();

const props = defineProps<{
  monitor?: Monitor | null;
  projectId: string;
}>();

const { t } = useI18n();
const toast = useToast();

const error = ref<string | null>(null);
const loading = ref(false);
const selectedType = ref<MonitorType>(props.monitor?.type ?? MONITOR_TYPE.HTTP);
const expectedStatus = ref<number | null>((props.monitor?.config?.expected_status as number) ?? null);
const keyword = ref<string>((props.monitor?.config?.keyword as string) ?? '');
const sslWarnDays = ref<number | null>((props.monitor?.config?.ssl_warn_days as number) ?? null);

const isEditMode = computed(() => !!props.monitor);
const resolver = computed(() => yupResolver(schema.value));
const showKeywordConfig = computed(() => selectedType.value === MONITOR_TYPE.HTTP || selectedType.value === MONITOR_TYPE.KEYWORD);
const showSslConfig = computed(() => selectedType.value === MONITOR_TYPE.SSL);

const intervalOptions = computed(() =>
  MONITOR_INTERVAL_OPTIONS.map((seconds) => ({ label: `${seconds / 60}m`, value: seconds }))
);

const typeOptions = computed(() =>
  MONITOR_TYPE_OPTIONS.map(({ labelKey, value }) => ({ label: t(labelKey), value }))
);

const initialValues = computed(() => ({
  interval_seconds: props.monitor?.intervalSeconds ?? 60,
  name: props.monitor?.name ?? '',
  target: props.monitor?.target ?? '',
}));

const schema = computed(() =>
  yup.object().shape({
    interval_seconds: yup.number().required('monitors.form.intervalRequired').min(60, 'monitors.form.intervalMin'),
    name: yup.string().required('monitors.form.nameRequired'),
    target: yup.string().required('monitors.form.targetRequired'),
  })
);

// HELPERS
function buildConfig(): Record<string, unknown> {
  const config: Record<string, unknown> = {};

  if (showKeywordConfig.value) {
    if (expectedStatus.value !== null) {
      config.expected_status = Number(expectedStatus.value);
    }

    if (keyword.value) {
      config.keyword = keyword.value;
    }
  }

  if (showSslConfig.value && sslWarnDays.value !== null) {
    config.ssl_warn_days = Number(sslWarnDays.value);
  }

  return config;
}

function getErrorMessage(fieldError: unknown): string {
  if (!fieldError) {
    return '';
  }

  if (typeof fieldError === 'string') {
    return t(fieldError);
  }

  const message = (fieldError as { message?: string }).message;

  return message ? t(message) : '';
}

// EVENTS
function onSubmit(event: FormSubmitEvent) {
  if (!event.valid) {
    return;
  }

  const values = event.values;
  loading.value = true;
  error.value = null;

  const promise = isEditMode.value
    ? UpdateMonitorService(props.monitor!.id, {
        config: buildConfig(),
        interval_seconds: Number(values.interval_seconds),
        name: values.name,
        target: values.target,
      })
    : CreateMonitorService({
        config: buildConfig(),
        interval_seconds: Number(values.interval_seconds),
        name: values.name,
        project_id: Number(props.projectId),
        target: values.target,
        type: selectedType.value,
      });

  promise
    .then(({ data }) => {
      emit('onSuccess', data);

      toast.add({
        severity: 'success',
        summary: t(isEditMode.value ? 'monitors.form.updated' : 'monitors.form.created', { name: data.name }),
        life: 5000,
      });
    })
    .catch(() => {
      error.value = t('errors.unexpected');

      toast.add({
        severity: 'error',
        summary: t('errors.somethingWentWrong'),
        detail: t('errors.refreshAndContactAdmin'),
        life: 10000,
      });
    })
    .finally(() => (loading.value = false));
}

function onTypeChange(type: MonitorType) {
  selectedType.value = type;
}
</script>

<template>
  <Form
    id="monitor-form"
    class="mx-auto flex w-full max-w-[500px] flex-col gap-4"
    :initialValues
    :resolver
    @submit="onSubmit"
  >
    <FormField name="name">
      <template #default="{ error: fieldError }">
        <div class="flex flex-col gap-2">
          <label
            for="name"
            :class="['mb-2 block text-sm', fieldError ? 'text-danger-700' : 'text-muted']"
          >
            {{ $t('monitors.form.name') }}
          </label>

          <InputText
            fluid
            inputId="name"
            name="name"
            :placeholder="$t('monitors.form.namePlaceholder')"
          />

          <small
            v-if="fieldError"
            class="text-danger-700 mt-1 block text-sm"
          >
            {{ getErrorMessage(fieldError) }}
          </small>
        </div>
      </template>
    </FormField>

    <div class="flex flex-col gap-2">
      <label
        class="text-muted mb-2 block text-sm"
        for="type"
      >
        {{ $t('monitors.form.type') }}
      </label>

      <Select
        fluid
        inputId="type"
        optionLabel="label"
        optionValue="value"
        :disabled="isEditMode"
        :modelValue="selectedType"
        :options="typeOptions"
        @update:modelValue="onTypeChange"
      />
    </div>

    <FormField name="target">
      <template #default="{ error: fieldError }">
        <div class="flex flex-col gap-2">
          <label
            for="target"
            :class="['mb-2 block text-sm', fieldError ? 'text-danger-700' : 'text-muted']"
          >
            {{ $t('monitors.form.target') }}
          </label>

          <InputText
            fluid
            inputId="target"
            name="target"
            :placeholder="$t('monitors.form.targetPlaceholder')"
          />

          <small
            v-if="fieldError"
            class="text-danger-700 mt-1 block text-sm"
          >
            {{ getErrorMessage(fieldError) }}
          </small>
        </div>
      </template>
    </FormField>

    <div class="flex flex-col gap-2">
      <label
        class="text-muted mb-2 block text-sm"
        for="interval_seconds"
      >
        {{ $t('monitors.form.interval') }}
      </label>

      <Select
        fluid
        inputId="interval_seconds"
        name="interval_seconds"
        optionLabel="label"
        optionValue="value"
        :options="intervalOptions"
      />
    </div>

    <div
      v-if="showKeywordConfig"
      class="flex flex-col gap-4"
    >
      <div class="flex flex-col gap-2">
        <label
          class="text-muted mb-2 block text-sm"
          for="expected_status"
        >
          {{ $t('monitors.form.expectedStatus') }}
        </label>

        <InputNumber
          v-model="expectedStatus"
          fluid
          inputId="expected_status"
          :max="599"
          :min="100"
          :useGrouping="false"
        />
      </div>

      <div class="flex flex-col gap-2">
        <label
          class="text-muted mb-2 block text-sm"
          for="keyword"
        >
          {{ $t('monitors.form.keyword') }}
        </label>

        <InputText
          v-model="keyword"
          fluid
          inputId="keyword"
          :placeholder="$t('monitors.form.keywordPlaceholder')"
        />
      </div>
    </div>

    <div
      v-if="showSslConfig"
      class="flex flex-col gap-2"
    >
      <label
        class="text-muted mb-2 block text-sm"
        for="ssl_warn_days"
      >
        {{ $t('monitors.form.sslWarnDays') }}
      </label>

      <InputNumber
        v-model="sslWarnDays"
        fluid
        inputId="ssl_warn_days"
        :max="365"
        :min="1"
        :useGrouping="false"
      />
    </div>

    <AFormError :error />
  </Form>

  <div class="bg-canvas sticky bottom-0 z-10 mx-auto flex w-full max-w-[500px] shrink-0 justify-end gap-2 py-4">
    <Button
      severity="secondary"
      :label="$t('monitors.form.cancel')"
      @click="emit('onClose')"
    />

    <Button
      form="monitor-form"
      type="submit"
      :label="isEditMode ? $t('monitors.form.edit') : $t('monitors.form.create')"
      :loading
    />
  </div>
</template>
