<script setup lang="ts">
import { ref } from 'vue';
import { yupResolver } from '@primevue/forms/resolvers/yup';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import * as yup from 'yup';
import type { FormSubmitEvent } from '@primevue/forms';
import { RegisterUserService } from '@AuthModule';

const { push } = useRouter();
const { t } = useI18n();
const toast = useToast();

const initialValues = {
  birthDate: '',
  cpf: '',
  firstName: '',
  lastName: '',
  password: '',
  passwordConfirmation: '',
  termsAccepted: false,
};

const resolver = yupResolver(
  yup.object().shape({
    birthDate: yup.string().trim().required(t('auth.flow.signUp.birthDateRequired')),
    cpf: yup.string().trim().required(t('auth.flow.signUp.cpfRequired')),
    firstName: yup.string().trim().required(t('auth.flow.signUp.nameRequired')),
    lastName: yup.string().trim().required(t('auth.flow.signUp.lastNameRequired')),
    password: yup
      .string()
      .trim()
      .required(t('auth.login.passwordRequired'))
      .min(8, t('auth.flow.setPassword.minLength')),
    passwordConfirmation: yup
      .string()
      .trim()
      .required(t('auth.flow.setPassword.confirmRequired'))
      .test('match', t('auth.flow.setPassword.mismatch'), function matchPasswordConfirmation(value) {
        return value === this.parent.password;
      }),
    termsAccepted: yup.boolean().oneOf([true], t('auth.flow.signUp.termsRequired')),
  })
);

const isLoading = ref(false);

// EVENTS
function onSubmit({ valid, values }: FormSubmitEvent) {
  if (!valid) return;

  isLoading.value = true;

  RegisterUserService({
    birthDate: String(values.birthDate ?? ''),
    cpf: String(values.cpf ?? ''),
    firstName: String(values.firstName ?? ''),
    lastName: String(values.lastName ?? ''),
    password: String(values.password ?? '').trim(),
    termsAccepted: Boolean(values.termsAccepted),
  })
    .then(() => push({ name: 'home' }))
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
    <FormField name="firstName">
      <div class="flex flex-col gap-2">
        <label
          class="text-muted mb-2 block text-sm"
          for="firstName"
        >
          {{ t('auth.flow.signUp.name') }}*
        </label>

        <InputText
          fluid
          id="firstName"
          name="firstName"
          :invalid="$form.firstName?.invalid"
          :placeholder="t('auth.flow.signUp.namePlaceholder')"
        />

        <Message
          v-if="$form.firstName?.invalid"
          v-bind="$form.firstName"
          class="w-full pl-3 text-sm"
          severity="error"
          size="small"
          variant="simple"
        >
          {{ $form.firstName?.error?.message }}
        </Message>
      </div>
    </FormField>

    <FormField name="lastName">
      <div class="flex flex-col gap-2">
        <label
          class="text-muted mb-2 block text-sm"
          for="lastName"
        >
          {{ t('auth.flow.signUp.lastName') }}*
        </label>

        <InputText
          fluid
          id="lastName"
          name="lastName"
          :invalid="$form.lastName?.invalid"
          :placeholder="t('auth.flow.signUp.lastNamePlaceholder')"
        />

        <Message
          v-if="$form.lastName?.invalid"
          v-bind="$form.lastName"
          class="w-full pl-3 text-sm"
          severity="error"
          size="small"
          variant="simple"
        >
          {{ $form.lastName?.error?.message }}
        </Message>
      </div>
    </FormField>

    <FormField name="cpf">
      <div class="flex flex-col gap-2">
        <label
          class="text-muted mb-2 block text-sm"
          for="cpf"
        >
          {{ t('auth.flow.signUp.cpf') }}*
        </label>

        <InputMask
          fluid
          id="cpf"
          mask="999.999.999-99"
          name="cpf"
          :invalid="$form.cpf?.invalid"
          :placeholder="t('auth.flow.signUp.cpfPlaceholder')"
        />

        <Message
          v-if="$form.cpf?.invalid"
          v-bind="$form.cpf"
          class="w-full pl-3 text-sm"
          severity="error"
          size="small"
          variant="simple"
        >
          {{ $form.cpf?.error?.message }}
        </Message>

        <p class="text-muted text-sm">
          {{ t('auth.flow.signUp.cpfDescription') }}
        </p>
      </div>
    </FormField>

    <FormField name="birthDate">
      <div class="flex flex-col gap-2">
        <label
          class="text-muted mb-2 block text-sm"
          for="birthDate"
        >
          {{ t('auth.flow.signUp.birthDate') }}*
        </label>

        <div class="relative">
          <i
            :class="[
              'pi pi-calendar pointer-events-none absolute top-1/2 left-3 -translate-y-1/2',
              $form.birthDate?.invalid ? 'text-danger-500' : 'text-subtle',
            ]"
          />

          <InputMask
            fluid
            id="birthDate"
            class="pl-10"
            mask="99/99/9999"
            name="birthDate"
            :invalid="$form.birthDate?.invalid"
            :placeholder="t('auth.flow.signUp.birthDatePlaceholder')"
          />
        </div>

        <Message
          v-if="$form.birthDate?.invalid"
          v-bind="$form.birthDate"
          class="w-full pl-3 text-sm"
          severity="error"
          size="small"
          variant="simple"
        >
          {{ $form.birthDate?.error?.message }}
        </Message>
      </div>
    </FormField>

    <FormField name="password">
      <div class="flex flex-col gap-2">
        <label
          class="text-muted mb-2 block text-sm"
          for="signUpPassword"
        >
          {{ t('auth.login.password') }}
        </label>

        <Password
          fluid
          toggleMask
          autocomplete="new-password"
          inputId="signUpPassword"
          name="password"
          :feedback="true"
          :invalid="$form.password?.invalid"
          :placeholder="t('auth.login.passwordPlaceholder')"
          :promptLabel="$t('common.password.promptLabel')"
        />

        <Message
          v-if="$form.password?.invalid"
          v-bind="$form.password"
          class="w-full pl-3 text-sm"
          severity="error"
          size="small"
          variant="simple"
        >
          {{ $form.password?.error?.message }}
        </Message>
      </div>
    </FormField>

    <FormField name="passwordConfirmation">
      <div class="flex flex-col gap-2">
        <label
          class="text-muted mb-2 block text-sm"
          for="signUpPasswordConfirmation"
        >
          {{ t('auth.flow.setPassword.confirmLabel') }}
        </label>

        <Password
          fluid
          toggleMask
          autocomplete="new-password"
          inputId="signUpPasswordConfirmation"
          name="passwordConfirmation"
          :feedback="false"
          :invalid="$form.passwordConfirmation?.invalid"
          :placeholder="t('auth.flow.setPassword.confirmPlaceholder')"
          :promptLabel="$t('common.password.promptLabel')"
        />

        <Message
          v-if="$form.passwordConfirmation?.invalid"
          v-bind="$form.passwordConfirmation"
          class="w-full pl-3 text-sm"
          severity="error"
          size="small"
          variant="simple"
        >
          {{ $form.passwordConfirmation?.error?.message }}
        </Message>
      </div>
    </FormField>

    <FormField name="termsAccepted">
      <div class="flex items-start gap-2">
        <Checkbox
          binary
          inputId="termsAccepted"
          name="termsAccepted"
          :invalid="$form.termsAccepted?.invalid"
        />

        <label
          class="text-muted text-sm"
          for="termsAccepted"
        >
          {{ t('auth.flow.signUp.iAcceptThe') }}

          <RouterLink
            class="text-primary font-semibold"
            target="_blank"
            to="/termos"
          >
            {{ t('auth.flow.signUp.termsAndConditionsOfUse') }}.
          </RouterLink>
        </label>
      </div>

      <Message
        v-if="$form.termsAccepted?.invalid"
        v-bind="$form.termsAccepted"
        class="w-full pl-3 text-sm"
        severity="error"
        size="small"
        variant="simple"
      >
        {{ $form.termsAccepted?.error?.message }}
      </Message>
    </FormField>

    <FormField>
      <Button
        class="w-full"
        type="submit"
        :label="t('auth.flow.continue')"
        :loading="isLoading"
      />
    </FormField>
  </Form>
</template>
