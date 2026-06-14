<script setup lang="ts">
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
  <AuthFlowLayout :title="t('auth.flow.login.passwordTitle')">
    <MLoginPasswordCredentials />

    <template #bottomActions>
      <RouterLink :to="{ name: 'auth.resetPassword' }">
        <p class="text-muted text-center text-base">{{ t('auth.flow.needHelp') }}</p>
      </RouterLink>
    </template>
  </AuthFlowLayout>
</template>
