<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { yupResolver } from '@primevue/forms/resolvers/yup';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import * as yup from 'yup';
import type { FormSubmitEvent } from '@primevue/forms';
import { RequestEmailOtpService, VerifyLoginCodeService } from '@AuthModule';

const route = useRoute();
const { push } = useRouter();
const { t } = useI18n();
const toast = useToast();

const initialValues = {
  code: '',
};

const resolver = yupResolver(
  yup.object().shape({
    code: yup.string().trim().length(4, t('auth.flow.login.codeInvalid')).required(t('auth.flow.login.codeRequired')),
  })
);

const isLoading = ref(false);
const isResendTimerVisible = ref(false);
const resendCooldownSeconds = ref(60);

let resendCooldownInterval: ReturnType<typeof setInterval> | null = null;

onBeforeUnmount(clearResendCooldownInterval);
onMounted(startResendCooldown);

// HELPERS
function clearResendCooldownInterval() {
  if (!resendCooldownInterval) return;

  clearInterval(resendCooldownInterval);
  resendCooldownInterval = null;
}

function formatResendCooldown(seconds: number): string {
  const normalizedSeconds = Math.max(seconds, 0);
  const minutes = String(Math.floor(normalizedSeconds / 60)).padStart(2, '0');
  const remainingSeconds = String(normalizedSeconds % 60).padStart(2, '0');

  return `${minutes}:${remainingSeconds}`;
}

function startResendCooldown() {
  clearResendCooldownInterval();
  resendCooldownSeconds.value = 60;

  resendCooldownInterval = setInterval(() => {
    if (resendCooldownSeconds.value <= 1) {
      resendCooldownSeconds.value = 0;
      clearResendCooldownInterval();
      return;
    }

    resendCooldownSeconds.value -= 1;
  }, 1000);
}

// EVENTS
function onResendCodeClick() {
  if (resendCooldownSeconds.value > 0) {
    isResendTimerVisible.value = true;

    toast.add({
      severity: 'error',
      summary: t('auth.flow.login.resendCodeWaitTitle'),
      detail: t('auth.flow.login.resendCodeWaitDetail'),
      life: 5000,
    });
    return;
  }

  const phone = String(route.query.phone ?? '').trim();

  if (!phone) {
    toast.add({
      severity: 'error',
      summary: t('errors.somethingWentWrong'),
      detail: t('auth.flow.login.resendCodeMissingPhone'),
      life: 10000,
    });
    return;
  }

  RequestEmailOtpService({ phone })
    .then(() => {
      toast.add({
        severity: 'success',
        summary: t('auth.flow.login.resendCodeSuccess'),
        life: 3000,
      });

      isResendTimerVisible.value = false;
      startResendCooldown();
    })
    .catch(() =>
      toast.add({
        severity: 'error',
        summary: t('errors.somethingWentWrong'),
        detail: t('errors.refreshAndContactAdmin'),
        life: 10000,
      })
    );
}

function onSubmit({ valid, values }: FormSubmitEvent) {
  if (!valid) return;

  isLoading.value = true;

  const code = String(values.code ?? '').trim();

  VerifyLoginCodeService({ code })
    .then(() => push({ name: 'auth.onboarding' }))
    .catch(() =>
      toast.add({
        severity: 'error',
        summary: t('errors.somethingWentWrong'),
        detail: t('errors.refreshAndContactAdmin'),
        life: 10000,
      })
    )
    .finally(() => (isLoading.value = false));
}
</script>

<template>
  <Form
    v-slot="$form"
    class="flex w-full flex-col gap-4"
    :initialValues
    :resolver
    @submit="onSubmit"
  >
    <FormField
      v-slot="{ props: codeFieldProps }"
      name="code"
    >
      <div class="flex flex-col gap-2">
        <label
          class="text-muted mb-2 block text-sm"
          for="loginOtpCode"
        >
          {{ t('auth.flow.login.enterCode') }}:
        </label>
        <!-- TODO-ID-44 :: Fix InputOpt Validation -->
        <InputOtp
          v-bind="codeFieldProps"
          integerOnly
          id="loginOtpCode"
          class="flex w-full justify-between"
        />

        <Message
          v-if="$form.code?.invalid"
          v-bind="$form.code"
          class="w-full text-sm"
          severity="error"
          size="small"
          variant="simple"
        >
          {{ $form.code?.error?.message }}
        </Message>
      </div>
    </FormField>

    <FormField>
      <Button
        class="w-full"
        type="submit"
        :label="t('auth.flow.continue')"
        :loading="isLoading"
      />
    </FormField>

    <div class="flex items-center justify-center gap-2">
      <Button
        text
        class="celer-button-text-secondary text-sm"
        :label="t('auth.flow.login.resendCode')"
        @click="onResendCodeClick"
      />

      <span
        v-if="isResendTimerVisible && resendCooldownSeconds > 0"
        class="text-muted text-sm"
      >
        {{ formatResendCooldown(resendCooldownSeconds) }}
      </span>
    </div>
  </Form>
</template>
