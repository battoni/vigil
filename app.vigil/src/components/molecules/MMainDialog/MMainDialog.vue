<script setup lang="ts">
import { computed, useTemplateRef } from 'vue';
import type { ButtonProps } from 'primevue/button';

type ActionButtonProps = Pick<ButtonProps, 'severity' | 'variant'> & { label: string };

const emit = defineEmits<{
  onClose: [];
  onSubmit: [];
}>();

withDefaults(
  defineProps<{
    blockScroll?: boolean;
    closable?: boolean;
    closeOnEscape?: boolean;
    contentClass?: string;
    dismissableMask?: boolean;
    draggable?: boolean;
    footerClasses?: string;
    headerClasses?: string;
    isFooterless?: boolean;
    isHeadless?: boolean;
    modal?: boolean;
    position?: 'bottom' | 'center' | 'left' | 'right' | 'top';
    title?: string;
    cancelButtonProps?: ActionButtonProps;
    submitButtonProps?: ActionButtonProps;
  }>(),
  {
    blockScroll: true,
    closable: true,
    closeOnEscape: false,
    contentClass: 'flex min-h-0 flex-col gap-4',
    dismissableMask: true,
    draggable: false,
    footerClasses: 'flex justify-end gap-4',
    headerClasses: 'flex items-center justify-center',
    modal: false,
    position: 'bottom',
    cancelButtonProps: () => ({ variant: 'outlined', severity: 'secondary', label: 'common.actions.cancel' }),
    submitButtonProps: () => ({ severity: 'primary', label: 'common.actions.submit' }),
  }
);

const visibleModel = defineModel<boolean>('visible', { default: false });

const contentWrapper = useTemplateRef<HTMLElement>('contentWrapper');

const dialogClass = computed(() => ['main-dialog-root w-full border-none shadow-none']);

// EVENTS
function onDialogShow() {
  const scrollEl = contentWrapper.value?.closest('.p-dialog-content') as HTMLElement | null;
  if (scrollEl) scrollEl.scrollTop = 0;
}
</script>

<template>
  <Dialog
    v-model:visible="visibleModel"
    appendTo="self"
    pt:headerActions="absolute right-2.5 top-3.5"
    :blockScroll
    :class="[dialogClass, 'h-full max-h-full']"
    :closable
    :closeOnEscape
    :dismissableMask
    :draggable
    :modal
    :position
    :pt:footer="footerClasses"
    :pt:header="headerClasses"
    :showHeader="!isHeadless"
    @show="onDialogShow"
  >
    <template
      v-if="!isHeadless"
      #header
    >
      <div
        v-if="title && !$slots.header"
        class="flex items-center justify-center"
      >
        <h2 class="text-heading text-xl font-semibold">
          {{ $t(title) }}
        </h2>
      </div>

      <slot
        v-if="$slots.header"
        name="header"
      >
      </slot>
    </template>

    <div
      ref="contentWrapper"
      :class="['main-dialog-content-wrapper', contentClass]"
    >
      <slot />
    </div>

    <template
      v-if="!isFooterless"
      #footer
    >
      <template v-if="!$slots.footer">
        <div class="sticky bottom-0 z-10 mx-auto flex w-full max-w-[500px] shrink-0 justify-end gap-2 py-4">
          <Button
            v-bind="cancelButtonProps"
            :label="$t(cancelButtonProps.label)"
            @click="emit('onClose')"
          />

          <Button
            v-bind="submitButtonProps"
            :label="$t(submitButtonProps.label)"
            @click="emit('onSubmit')"
          />
        </div>
      </template>

      <slot
        v-else
        name="footer"
      />
    </template>
  </Dialog>
</template>

<style>
.p-dialog-bottom .main-dialog-root {
  margin-bottom: 0 !important;
}

.p-dialog-bottom .main-dialog-root.p-dialog-enter-active {
  animation: celer-dialog-enter-bottom 0.5s cubic-bezier(0.32, 0.72, 0, 1);
}

.p-dialog-bottom .main-dialog-root.p-dialog-leave-active {
  animation: celer-dialog-leave-bottom 0.5s cubic-bezier(0.32, 0.72, 0, 1);
}

.main-dialog-root .main-dialog-content-wrapper {
  min-height: 0;
}

main:has(.main-dialog-root) .main-scroll {
  overflow: hidden !important;
}

.main-dialog-root .p-dialog-content {
  scrollbar-color: var(--color-primary-700) transparent;
  scrollbar-width: thin;
}

.main-dialog-root .p-dialog-content::-webkit-scrollbar {
  width: 6px;
}

.main-dialog-root .p-dialog-content::-webkit-scrollbar-track {
  background: transparent;
}

.main-dialog-root .p-dialog-content::-webkit-scrollbar-thumb {
  background-color: var(--color-primary-700);
  border-radius: 3px;
}

.main-dialog-root .p-dialog-content::-webkit-scrollbar-thumb:hover {
  background-color: var(--color-primary-800);
}

@keyframes celer-dialog-enter-bottom {
  from {
    transform: translate3d(0, 100%, 0);
  }
}

@keyframes celer-dialog-leave-bottom {
  to {
    transform: translate3d(0, 100%, 0);
  }
}
</style>
