<script setup lang="ts">
import { ref } from 'vue';
import { yupResolver } from '@primevue/forms/resolvers/yup';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import * as yup from 'yup';
import type { FormSubmitEvent } from '@primevue/forms';
import { LoginRequestService } from '@AuthModule';
import { useUserStore } from '@UserModule';

const { setUserAndPermissions } = useUserStore();
const { push } = useRouter();
const { t } = useI18n();
const toast = useToast();

const initialValues = {
  username: '',
  password: '',
};

const resolver = yupResolver(
  yup.object().shape({
    username: yup.string().trim().required('auth.login.usernameRequired'),
    password: yup.string().trim().required('auth.login.passwordRequired'),
  })
);

const apiError = ref<string | null>(null);
const isLoading = ref(false);

// EVENTS
function onSubmit({ valid, values }: FormSubmitEvent) {
  if (!valid) return;

  isLoading.value = true;
  apiError.value = null;

  const username = String(values.username ?? '').trim();
  const password = String(values.password ?? '').trim();

  LoginRequestService({ username, password })
    .then(({ data }) => {
      setUserAndPermissions(data, data.permissions ?? []);
      toast.add({
        severity: 'success',
        summary: t('auth.login.welcome', { name: data.name }),
        life: 5000,
      });
      push({ name: 'home' });
    })
    .catch(
      (err: { response?: { status?: number; data?: { message?: string; errors?: Record<string, string[]> } } }) => {
        const status = err?.response?.status;
        const data = err?.response?.data;
        const messageKey = data?.message ?? (data?.errors && Object.values(data.errors)[0]?.[0]);
        const isValidationError = status === 422 && messageKey;

        if (isValidationError) {
          apiError.value = t(messageKey);
          toast.add({
            severity: 'error',
            summary: t(messageKey),
            life: 10000,
          });
          return;
        }

        apiError.value = null;
        toast.add({
          severity: 'error',
          summary: t('errors.somethingWentWrong'),
          detail: t('errors.refreshAndContactAdmin'),
          life: 10000,
        });
      }
    )
    .finally(() => (isLoading.value = false));
}
</script>

<template>
  <Form
    class="flex w-full flex-col gap-4"
    :initialValues
    :resolver
    @submit="onSubmit"
  >
    <FormField name="username">
      <IftaLabel>
        <InputText
          fluid
          id="username"
          name="username"
          :placeholder="$t('auth.login.usernamePlaceholder')"
        />

        <label for="username">{{ $t('auth.login.username') }}</label>
      </IftaLabel>
    </FormField>

    <FormField name="password">
      <IftaLabel>
        <Password
          fluid
          toggleMask
          name="password"
          :feedback="false"
          :placeholder="$t('auth.login.passwordPlaceholder')"
          :promptLabel="$t('common.password.promptLabel')"
        />

        <label for="password">{{ $t('auth.login.password') }}</label>
      </IftaLabel>
    </FormField>

    <AFormError :error="apiError" />

    <FormField>
      <Button
        class="w-full"
        type="submit"
        :label="$t('auth.login.submit')"
        :loading="isLoading"
      />
    </FormField>

    <RouterLink to="/esqueci-minha-senha">
      <p class="text-muted text-center text-base">{{ $t('auth.login.forgotPassword') }}</p>
    </RouterLink>
  </Form>
</template>
