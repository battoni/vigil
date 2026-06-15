<script setup lang="ts">
import { computed, ref } from 'vue';
import { yupResolver } from '@primevue/forms/resolvers/yup';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';
import * as yup from 'yup';
import type { Channel } from '../../../interfaces';
import type { ChannelType } from '../../../types';
import type { FormSubmitEvent } from '@primevue/forms';
import { CHANNEL_TYPE_OPTIONS } from '../../../constants';
import { CHANNEL_TYPE } from '../../../enums';
import { CreateChannelService, UpdateChannelService } from '../../../services';

const emit = defineEmits<{
  onClose: [];
  onSuccess: [channel: Channel];
}>();

const props = defineProps<{
  channel?: Channel | null;
}>();

const { t } = useI18n();
const toast = useToast();

const error = ref<string | null>(null);
const loading = ref(false);
const selectedType = ref<ChannelType>(props.channel?.type ?? CHANNEL_TYPE.SLACK);

const emailRecipients = ref<string>(((props.channel?.config?.recipients as string[]) ?? []).join(', '));
const fromEmail = ref<string>((props.channel?.config?.from as string) ?? '');
const resendApiKey = ref<string>((props.channel?.config?.resend_api_key as string) ?? '');
const waRecipients = ref<string>(((props.channel?.config?.recipients as string[]) ?? []).join(', '));
const waApiKey = ref<string>((props.channel?.config?.api_key as string) ?? '');
const waBaseUrl = ref<string>((props.channel?.config?.base_url as string) ?? '');
const waInstance = ref<string>((props.channel?.config?.instance as string) ?? '');
const webhookUrl = ref<string>((props.channel?.config?.webhook_url as string) ?? '');

const isEditMode = computed(() => !!props.channel);
const isEmail = computed(() => selectedType.value === CHANNEL_TYPE.EMAIL);
const isSlack = computed(() => selectedType.value === CHANNEL_TYPE.SLACK);
const isWhatsapp = computed(() => selectedType.value === CHANNEL_TYPE.WHATSAPP);
const resolver = computed(() => yupResolver(schema.value));

const typeOptions = computed(() => CHANNEL_TYPE_OPTIONS.map(({ labelKey, value }) => ({ label: t(labelKey), value })));

const initialValues = computed(() => ({ name: props.channel?.name ?? '' }));

const schema = computed(() => yup.object().shape({ name: yup.string().required('channels.form.nameRequired') }));

// HELPERS
function splitRecipients(value: string): string[] {
  return value
    .split(',')
    .map((entry) => entry.trim())
    .filter((entry) => entry.length > 0);
}

function buildConfig(): Record<string, unknown> {
  if (isSlack.value) {
    return { webhook_url: webhookUrl.value };
  }

  if (isEmail.value) {
    return { recipients: splitRecipients(emailRecipients.value), resend_api_key: resendApiKey.value, from: fromEmail.value };
  }

  return {
    base_url: waBaseUrl.value,
    instance: waInstance.value,
    api_key: waApiKey.value,
    recipients: splitRecipients(waRecipients.value),
  };
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

  loading.value = true;
  error.value = null;

  const promise = isEditMode.value
    ? UpdateChannelService(props.channel!.id, { name: event.values.name, config: buildConfig() })
    : CreateChannelService({ name: event.values.name, type: selectedType.value, config: buildConfig(), is_active: true });

  promise
    .then(({ data }) => {
      emit('onSuccess', data);

      toast.add({
        severity: 'success',
        summary: t(isEditMode.value ? 'channels.form.updated' : 'channels.form.created', { name: data.name }),
        life: 5000,
      });
    })
    .catch(() => {
      error.value = t('errors.unexpected');
      toast.add({ severity: 'error', summary: t('errors.somethingWentWrong'), life: 10000 });
    })
    .finally(() => (loading.value = false));
}

function onTypeChange(type: ChannelType) {
  selectedType.value = type;
}
</script>

<template>
  <Form
    id="channel-form"
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
            {{ $t('channels.form.name') }}
          </label>

          <InputText
            fluid
            inputId="name"
            name="name"
            :placeholder="$t('channels.form.namePlaceholder')"
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
        {{ $t('channels.form.type') }}
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

    <div
      v-if="isSlack"
      class="flex flex-col gap-2"
    >
      <label
        class="text-muted mb-2 block text-sm"
        for="webhook_url"
      >
        {{ $t('channels.form.webhookUrl') }}
      </label>

      <InputText
        v-model="webhookUrl"
        fluid
        inputId="webhook_url"
        placeholder="https://hooks.slack.com/services/..."
      />
    </div>

    <div
      v-if="isEmail"
      class="flex flex-col gap-4"
    >
      <div class="flex flex-col gap-2">
        <label
          class="text-muted mb-2 block text-sm"
          for="recipients"
        >
          {{ $t('channels.form.recipients') }}
        </label>

        <InputText
          v-model="emailRecipients"
          fluid
          inputId="recipients"
          :placeholder="$t('channels.form.recipientsPlaceholder')"
        />
      </div>

      <div class="flex flex-col gap-2">
        <label
          class="text-muted mb-2 block text-sm"
          for="from"
        >
          {{ $t('channels.form.from') }}
        </label>

        <InputText
          v-model="fromEmail"
          fluid
          inputId="from"
          placeholder="alerts@vigil.example.com"
        />
      </div>

      <div class="flex flex-col gap-2">
        <label
          class="text-muted mb-2 block text-sm"
          for="resend_api_key"
        >
          {{ $t('channels.form.resendApiKey') }}
        </label>

        <InputText
          v-model="resendApiKey"
          fluid
          inputId="resend_api_key"
        />
      </div>
    </div>

    <div
      v-if="isWhatsapp"
      class="flex flex-col gap-4"
    >
      <div class="flex flex-col gap-2">
        <label
          class="text-muted mb-2 block text-sm"
          for="base_url"
        >
          {{ $t('channels.form.baseUrl') }}
        </label>

        <InputText
          v-model="waBaseUrl"
          fluid
          inputId="base_url"
        />
      </div>

      <div class="flex flex-col gap-2">
        <label
          class="text-muted mb-2 block text-sm"
          for="instance"
        >
          {{ $t('channels.form.instance') }}
        </label>

        <InputText
          v-model="waInstance"
          fluid
          inputId="instance"
        />
      </div>

      <div class="flex flex-col gap-2">
        <label
          class="text-muted mb-2 block text-sm"
          for="api_key"
        >
          {{ $t('channels.form.apiKey') }}
        </label>

        <InputText
          v-model="waApiKey"
          fluid
          inputId="api_key"
        />
      </div>

      <div class="flex flex-col gap-2">
        <label
          class="text-muted mb-2 block text-sm"
          for="wa_recipients"
        >
          {{ $t('channels.form.recipients') }}
        </label>

        <InputText
          v-model="waRecipients"
          fluid
          inputId="wa_recipients"
          :placeholder="$t('channels.form.recipientsPlaceholder')"
        />
      </div>
    </div>

    <AFormError :error />
  </Form>

  <div class="bg-canvas sticky bottom-0 z-10 mx-auto flex w-full max-w-[500px] shrink-0 justify-end gap-2 py-4">
    <Button
      severity="secondary"
      :label="$t('channels.form.cancel')"
      @click="emit('onClose')"
    />

    <Button
      form="channel-form"
      type="submit"
      :label="isEditMode ? $t('channels.form.edit') : $t('channels.form.create')"
      :loading
    />
  </div>
</template>
