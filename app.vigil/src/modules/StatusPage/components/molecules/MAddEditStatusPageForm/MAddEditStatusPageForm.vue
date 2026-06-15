<script setup lang="ts">
import { computed, ref } from 'vue';
import { yupResolver } from '@primevue/forms/resolvers/yup';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';
import * as yup from 'yup';
import type { StatusPage } from '../../../interfaces';
import type { FormSubmitEvent } from '@primevue/forms';
import { CreateStatusPageService, UpdateStatusPageService } from '../../../services';

const emit = defineEmits<{
  onClose: [];
  onSuccess: [statusPage: StatusPage];
}>();

const props = defineProps<{
  statusPage?: StatusPage | null;
}>();

const { t } = useI18n();
const toast = useToast();

const error = ref<string | null>(null);
const loading = ref(false);
const isPublic = ref<boolean>(props.statusPage?.isPublic ?? true);
const customDomain = ref<string>(props.statusPage?.customDomain ?? '');
const headline = ref<string>((props.statusPage?.branding?.headline as string) ?? '');

const isEditMode = computed(() => !!props.statusPage);
const resolver = computed(() => yupResolver(schema.value));

const initialValues = computed(() => ({ name: props.statusPage?.name ?? '' }));

const schema = computed(() => yup.object().shape({ name: yup.string().required('statusPages.form.nameRequired') }));

// HELPERS
function buildPayload(name: string) {
  return {
    name,
    custom_domain: customDomain.value || null,
    branding: { headline: headline.value },
    is_public: isPublic.value,
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

  const payload = buildPayload(event.values.name);
  const promise = isEditMode.value
    ? UpdateStatusPageService(props.statusPage!.id, payload)
    : CreateStatusPageService(payload);

  promise
    .then(({ data }) => {
      emit('onSuccess', data);

      toast.add({
        severity: 'success',
        summary: t(isEditMode.value ? 'statusPages.form.updated' : 'statusPages.form.created', { name: data.name }),
        life: 5000,
      });
    })
    .catch(() => {
      error.value = t('errors.unexpected');
      toast.add({ severity: 'error', summary: t('errors.somethingWentWrong'), life: 10000 });
    })
    .finally(() => (loading.value = false));
}
</script>

<template>
  <Form
    id="status-page-form"
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
            {{ $t('statusPages.form.name') }}
          </label>

          <InputText
            fluid
            inputId="name"
            name="name"
            :placeholder="$t('statusPages.form.namePlaceholder')"
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
        for="custom_domain"
      >
        {{ $t('statusPages.form.customDomain') }}
      </label>

      <InputText
        v-model="customDomain"
        fluid
        inputId="custom_domain"
        placeholder="status.example.com"
      />
    </div>

    <div class="flex flex-col gap-2">
      <label
        class="text-muted mb-2 block text-sm"
        for="headline"
      >
        {{ $t('statusPages.form.headline') }}
      </label>

      <InputText
        v-model="headline"
        fluid
        inputId="headline"
        :placeholder="$t('statusPages.form.headlinePlaceholder')"
      />
    </div>

    <div class="flex items-center gap-3">
      <ToggleSwitch
        v-model="isPublic"
        inputId="is_public"
      />

      <label
        class="text-muted text-sm"
        for="is_public"
      >
        {{ $t('statusPages.form.isPublic') }}
      </label>
    </div>

    <AFormError :error />
  </Form>

  <div class="bg-canvas sticky bottom-0 z-10 mx-auto flex w-full max-w-[500px] shrink-0 justify-end gap-2 py-4">
    <Button
      severity="secondary"
      :label="$t('statusPages.form.cancel')"
      @click="emit('onClose')"
    />

    <Button
      form="status-page-form"
      type="submit"
      :label="isEditMode ? $t('statusPages.form.edit') : $t('statusPages.form.create')"
      :loading
    />
  </div>
</template>
