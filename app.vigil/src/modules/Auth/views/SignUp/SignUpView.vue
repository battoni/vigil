<script setup lang="ts">
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
  <AuthFlowLayout :title="t('auth.flow.signUp.title')">
    <MSignUpUser />
  </AuthFlowLayout>
</template>
