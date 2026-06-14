<script setup lang="ts">
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
  <AuthFlowLayout :title="t('auth.flow.login.emailTitle')">
    <template #topActions>
      <RouterLink to="/entrar">
        <Button
          link
          class="celer-button-link-secondary"
          icon="pi pi-arrow-left"
          :label="t('auth.flow.back')"
        />
      </RouterLink>
    </template>

    <MLoginEmail />

    <template #bottomActions>
      <RouterLink :to="{ name: 'auth.support' }">
        <p class="text-muted text-center text-base">{{ t('auth.flow.needHelp') }}</p>
      </RouterLink>
    </template>
  </AuthFlowLayout>
</template>
