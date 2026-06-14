<script setup lang="ts">
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
  <AuthFlowLayout :title="t('auth.flow.login.phoneTitle')">
    <MLoginPhone />

    <template #bottomActions>
      <RouterLink :to="{ name: 'auth.support' }">
        <p class="text-muted text-center text-base">{{ t('auth.flow.needHelp') }}</p>
      </RouterLink>
    </template>
  </AuthFlowLayout>
</template>
