<script setup lang="ts">
import { ref } from 'vue';
import { yupResolver } from '@primevue/forms/resolvers/yup';
import { useToast } from 'primevue/usetoast';
import * as yup from 'yup';
import type { FormSubmitEvent } from '@primevue/forms';

type LongFormData = {
  address: string;
  bio: string;
  city: string;
  company: string;
  country: string;
  email: string;
  firstName: string;
  jobTitle: string;
  lastName: string;
  linkedIn: string;
  notes: string;
  phone: string;
  postalCode: string;
  state: string;
  website: string;
};

const emit = defineEmits<{
  onClose: [];
  onSuccess: [data: LongFormData];
}>();

const toast = useToast();

const loading = ref(false);

const initialValues: LongFormData = {
  address: '',
  bio: '',
  city: '',
  company: '',
  country: '',
  email: '',
  firstName: '',
  jobTitle: '',
  lastName: '',
  linkedIn: '',
  notes: '',
  phone: '',
  postalCode: '',
  state: '',
  website: '',
};

const schema = yup.object().shape({
  address: yup.string().required('Address is required'),
  bio: yup.string().optional(),
  city: yup.string().required('City is required'),
  company: yup.string().required('Company is required'),
  country: yup.string().required('Country is required'),
  email: yup.string().email('Enter a valid email').required('Email is required'),
  firstName: yup.string().required('First name is required'),
  jobTitle: yup.string().required('Job title is required'),
  lastName: yup.string().required('Last name is required'),
  linkedIn: yup.string().optional(),
  notes: yup.string().optional(),
  phone: yup.string().required('Phone is required'),
  postalCode: yup.string().required('Postal code is required'),
  state: yup.string().optional(),
  website: yup.string().url('Enter a valid URL').optional(),
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

  const payload: LongFormData = {
    address: String(event.values?.address ?? ''),
    bio: String(event.values?.bio ?? ''),
    city: String(event.values?.city ?? ''),
    company: String(event.values?.company ?? ''),
    country: String(event.values?.country ?? ''),
    email: String(event.values?.email ?? ''),
    firstName: String(event.values?.firstName ?? ''),
    jobTitle: String(event.values?.jobTitle ?? ''),
    lastName: String(event.values?.lastName ?? ''),
    linkedIn: String(event.values?.linkedIn ?? ''),
    notes: String(event.values?.notes ?? ''),
    phone: String(event.values?.phone ?? ''),
    postalCode: String(event.values?.postalCode ?? ''),
    state: String(event.values?.state ?? ''),
    website: String(event.values?.website ?? ''),
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
    id="showcase-long-form"
    class="mx-auto flex w-full max-w-[500px] flex-col gap-4 pb-[calc(5rem+env(safe-area-inset-bottom,0px))]"
    :initialValues
    :resolver
    @submit="onSubmit"
  >
    <FormField name="firstName">
      <template #default="{ error: fieldError }">
        <div class="flex flex-col gap-2">
          <label
            for="firstName"
            :class="['mb-2 block text-sm', fieldError ? 'text-danger-700' : 'text-muted']"
          >
            First name
          </label>

          <InputText
            fluid
            inputId="firstName"
            name="firstName"
            placeholder="John"
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

    <FormField name="lastName">
      <template #default="{ error: fieldError }">
        <div class="flex flex-col gap-2">
          <label
            for="lastName"
            :class="['mb-2 block text-sm', fieldError ? 'text-danger-700' : 'text-muted']"
          >
            Last name
          </label>

          <InputText
            fluid
            inputId="lastName"
            name="lastName"
            placeholder="Doe"
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

    <FormField name="phone">
      <template #default="{ error: fieldError }">
        <div class="flex flex-col gap-2">
          <label
            for="phone"
            :class="['mb-2 block text-sm', fieldError ? 'text-danger-700' : 'text-muted']"
          >
            Phone
          </label>

          <InputText
            fluid
            inputId="phone"
            name="phone"
            placeholder="+1 234 567 8900"
            type="tel"
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

    <FormField name="company">
      <template #default="{ error: fieldError }">
        <div class="flex flex-col gap-2">
          <label
            for="company"
            :class="['mb-2 block text-sm', fieldError ? 'text-danger-700' : 'text-muted']"
          >
            Company
          </label>

          <InputText
            fluid
            inputId="company"
            name="company"
            placeholder="Acme Corp"
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

    <FormField name="jobTitle">
      <template #default="{ error: fieldError }">
        <div class="flex flex-col gap-2">
          <label
            for="jobTitle"
            :class="['mb-2 block text-sm', fieldError ? 'text-danger-700' : 'text-muted']"
          >
            Job title
          </label>

          <InputText
            fluid
            inputId="jobTitle"
            name="jobTitle"
            placeholder="Product Manager"
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

    <FormField name="website">
      <template #default="{ error: fieldError }">
        <div class="flex flex-col gap-2">
          <label
            for="website"
            :class="['mb-2 block text-sm', fieldError ? 'text-danger-700' : 'text-muted']"
          >
            Website
          </label>

          <InputText
            fluid
            inputId="website"
            name="website"
            placeholder="https://example.com"
            type="url"
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

    <FormField name="address">
      <template #default="{ error: fieldError }">
        <div class="flex flex-col gap-2">
          <label
            for="address"
            :class="['mb-2 block text-sm', fieldError ? 'text-danger-700' : 'text-muted']"
          >
            Address
          </label>

          <InputText
            fluid
            inputId="address"
            name="address"
            placeholder="123 Main Street"
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

    <FormField name="city">
      <template #default="{ error: fieldError }">
        <div class="flex flex-col gap-2">
          <label
            for="city"
            :class="['mb-2 block text-sm', fieldError ? 'text-danger-700' : 'text-muted']"
          >
            City
          </label>

          <InputText
            fluid
            inputId="city"
            name="city"
            placeholder="New York"
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

    <FormField name="state">
      <template #default="{ error: fieldError }">
        <div class="flex flex-col gap-2">
          <label
            for="state"
            :class="['mb-2 block text-sm', fieldError ? 'text-danger-700' : 'text-muted']"
          >
            State
          </label>

          <InputText
            fluid
            inputId="state"
            name="state"
            placeholder="NY"
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

    <FormField name="country">
      <template #default="{ error: fieldError }">
        <div class="flex flex-col gap-2">
          <label
            for="country"
            :class="['mb-2 block text-sm', fieldError ? 'text-danger-700' : 'text-muted']"
          >
            Country
          </label>

          <InputText
            fluid
            inputId="country"
            name="country"
            placeholder="United States"
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

    <FormField name="postalCode">
      <template #default="{ error: fieldError }">
        <div class="flex flex-col gap-2">
          <label
            for="postalCode"
            :class="['mb-2 block text-sm', fieldError ? 'text-danger-700' : 'text-muted']"
          >
            Postal code
          </label>

          <InputText
            fluid
            inputId="postalCode"
            name="postalCode"
            placeholder="10001"
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

    <FormField name="bio">
      <template #default="{ error: fieldError }">
        <div class="flex flex-col gap-2">
          <label
            for="bio"
            :class="['mb-2 block text-sm', fieldError ? 'text-danger-700' : 'text-muted']"
          >
            Bio
          </label>

          <Textarea
            autoResize
            fluid
            inputId="bio"
            name="bio"
            placeholder="A brief description about yourself…"
            rows="4"
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

    <FormField name="linkedIn">
      <template #default="{ error: fieldError }">
        <div class="flex flex-col gap-2">
          <label
            for="linkedIn"
            :class="['mb-2 block text-sm', fieldError ? 'text-danger-700' : 'text-muted']"
          >
            LinkedIn
          </label>

          <InputText
            fluid
            inputId="linkedIn"
            name="linkedIn"
            placeholder="linkedin.com/in/username"
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
      form="showcase-long-form"
      label="Save"
      type="submit"
      :loading
    />
  </div>

  <div class="bg-canvas absolute bottom-0 z-10 h-5 w-full" />
</template>
