<script setup lang="ts">
withDefaults(
  defineProps<{
    title?: string;
  }>(),
  {
    title: '',
  }
);
</script>

<template>
  <div class="bg-canvas flex min-h-screen w-full">
    <aside
      class="bg-primary-500 relative hidden overflow-hidden lg:flex lg:w-[44%] lg:flex-col lg:justify-between lg:p-12 xl:p-16"
    >
      <div class="text-ink-900 absolute inset-0 opacity-[0.07]">
        <ABrandPattern
          :rotate="-8"
          :tile="168"
        />
      </div>

      <div class="relative">
        <ABrandMark class="text-ink-900 h-14 w-auto" />
      </div>

      <div class="relative flex flex-col gap-6">
        <span class="text-ink-900/70 font-mono text-sm tracking-[0.2em] uppercase">&gt;_ app.vigil</span>

        <h2 class="text-ink-950 text-5xl leading-[1.04] font-bold tracking-tight xl:text-6xl">
          Built to<br />move fast.
        </h2>

        <p class="text-ink-900/80 max-w-sm font-mono text-sm leading-relaxed">
          An application starter — engineered for clean, fast delivery.
        </p>
      </div>

      <div class="text-ink-900/90 relative font-mono text-sm font-medium tracking-tight">battoni.dev</div>
    </aside>

    <main class="relative flex flex-1 flex-col">
      <div class="text-subtle pointer-events-none absolute inset-0 opacity-[0.03] lg:hidden">
        <ABrandPattern :tile="150" />
      </div>

      <div class="relative flex flex-1 items-center justify-center px-4 py-10">
        <div class="flex w-full max-w-[400px] flex-col">
          <div class="mb-8 flex justify-center">
            <ALogo
              class="w-14"
              variant="min"
            />
          </div>

          <div
            v-if="$slots.topActions"
            class="mb-4"
          >
            <slot name="topActions" />
          </div>

          <div class="border-line-strong bg-canvas w-full rounded-xl border">
            <div class="pt-5">
              <h1
                v-if="title"
                class="text-heading text-center text-xl font-medium"
              >
                {{ title }}
              </h1>
            </div>

            <Divider class="before:border-line-strong w-full" />

            <div class="flex w-full flex-col p-5">
              <slot />
            </div>
          </div>

          <div
            v-if="$slots.bottomActions"
            class="mt-4 px-2"
          >
            <slot name="bottomActions" />
          </div>
        </div>
      </div>

      <div class="relative flex justify-center pb-8">
        <ABrandSignature />
      </div>
    </main>
  </div>
</template>
