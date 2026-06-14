<script setup lang="ts">
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
  <RouterLink
    class="absolute top-4 left-4"
    :to="{ name: 'auth.entrar' }"
  >
    <Button
      link
      class="celer-button-link-secondary"
      icon="pi pi-arrow-left"
      :label="$t('auth.flow.back')"
    />
  </RouterLink>

  <TheCenteredLayout>
    <div class="flex max-w-[500px] flex-col items-center justify-center gap-4">
      <div class="m-auto w-full max-w-[100px]">
        <ALogo variant="min" />
      </div>

      <h1 class="text-heading text-center text-lg font-medium">
        {{ t('auth.flow.terms.title') }}
      </h1>

      <p class="text-muted text-center text-sm">
        {{ t('auth.flow.terms.description') }}
      </p>

      <p class="text-muted text-center text-sm">
        {{ t('auth.flow.terms.warning') }}
      </p>
    </div>
  </TheCenteredLayout>
</template>
