<script setup lang="ts">
import { ref } from 'vue';
import { yupResolver } from '@primevue/forms/resolvers/yup';
import { useToast } from 'primevue/usetoast';
import * as yup from 'yup';
import type { FormSubmitEvent } from '@primevue/forms';

type ShortFormData = {
  email: string;
  notes: string;
  title: string;
};

const emit = defineEmits<{
  onClose: [];
  onSuccess: [data: ShortFormData];
}>();

const toast = useToast();

const loading = ref(false);

const initialValues: ShortFormData = {
  email: '',
  notes: '',
  title: '',
};

const schema = yup.object().shape({
  email: yup.string().email('Enter a valid email').required('Email is required'),
  notes: yup.string().optional(),
  title: yup.string().required('Title is required'),
});

const resolver = yupResolver(schema);

// HELPERS
function getErrorMessage(fieldError: unknown): string {
  if (!fieldError) return '';
  if (typeof fieldError === 'string') return fieldError;
  if (
    fieldError &&
    typeof fieldError === 'object' &&
    'message' in fieldError &&
    typeof (fieldError as { message: unknown }).message === 'string'
  ) {
    return (fieldError as { message: string }).message;
  }
  return String(fieldError);
}

// EVENTS
function onSubmit(event: FormSubmitEvent) {
  if (!event.valid) return;

  loading.value = true;

  const payload: ShortFormData = {
    email: String(event.values?.email ?? ''),
    notes: String(event.values?.notes ?? ''),
    title: String(event.values?.title ?? ''),
  };

  Promise.resolve(payload)
    .then((data) => {
      emit('onSuccess', data);
      toast.add({ severity: 'success', summary: 'Item created', life: 3000 });
    })
    .catch(() => toast.add({ severity: 'error', summary: 'Something went wrong', life: 5000 }))
    .finally(() => (loading.value = false));
}
</script>

<template>
  <Form
    id="showcase-short-form"
    class="mx-auto flex w-full max-w-[500px] flex-col gap-4"
    :initialValues
    :resolver
    @submit="onSubmit"
  >
    <FormField name="title">
      <template #default="{ error: fieldError }">
        <div class="flex flex-col gap-2">
          <label
            for="title"
            :class="['mb-2 block text-sm', fieldError ? 'text-danger-700' : 'text-muted']"
          >
            Title
          </label>

          <InputText
            fluid
            inputId="title"
            name="title"
            placeholder="Enter a title"
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

    <FormField name="email">
      <template #default="{ error: fieldError }">
        <div class="flex flex-col gap-2">
          <label
            for="email"
            :class="['mb-2 block text-sm', fieldError ? 'text-danger-700' : 'text-muted']"
          >
            Email
          </label>

          <InputText
            fluid
            inputId="email"
            name="email"
            placeholder="you@example.com"
            type="email"
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

    <FormField name="notes">
      <template #default="{ error: fieldError }">
        <div class="flex flex-col gap-2">
          <label
            for="notes"
            :class="['mb-2 block text-sm', fieldError ? 'text-danger-700' : 'text-muted']"
          >
            Notes
          </label>

          <Textarea
            autoResize
            fluid
            inputId="notes"
            name="notes"
            placeholder="Optional notes…"
            rows="3"
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
  </Form>

  <div class="bg-canvas sticky bottom-0 z-10 mx-auto flex w-full max-w-[500px] shrink-0 justify-end gap-2 py-4">
    <Button
      label="Cancel"
      severity="secondary"
      @click="emit('onClose')"
    />

    <Button
      form="showcase-short-form"
      label="Save"
      type="submit"
      :loading
    />
  </div>

  <div class="bg-canvas absolute bottom-0 z-10 h-5 w-full" />
</template>
