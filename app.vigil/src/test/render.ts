// renderWithPlugins — Testing Library counterpart to mountWithPlugins.
//
// Use this for integration specs (.integration.spec.ts) where you query by
// role/label/text rather than CSS. Unit specs continue to use mountWithPlugins.
//
// Usage:
//   const pinia = createPinia();
//   setActivePinia(pinia);
//   useUserStore().setUserAndPermissions(user, permissions);
//   const { getByRole } = renderWithPlugins(MyView, { pinia });

import type { Component } from 'vue';
import { render } from '@testing-library/vue';
import { createPinia, setActivePinia } from 'pinia';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';
import type { Pinia } from 'pinia';
import i18n from '@Libraries/i18n';
import ThemePreset from '@Libraries/primevue/theme';

// Stubs for auto-imported components that aren't registered in Vitest.
// Layout components are never the subject of integration tests.
const layoutStubs = {
  TheLayout: { template: '<div data-testid="layout"><slot name="pageHeader" /><slot /></div>' },
  ThePageHeader: { template: '<div data-testid="page-header"><slot name="actions" /></div>' },
  TheCenteredLayout: { template: '<div data-testid="centered-layout"><slot /></div>' },
  AuthFlowLayout: { template: '<div data-testid="auth-flow-layout"><slot /><slot name="bottomActions" /></div>' },
  // Shared atoms auto-imported in production via unplugin-vue-components
  AFormError: { props: ['error'], template: '<div v-if="error" class="a-form-error-stub">{{ error }}</div>' },
};

type RenderWithPluginsOptions = Parameters<typeof render>[1] & {
  pinia?: Pinia;
};

export function renderWithPlugins(component: Component, options: RenderWithPluginsOptions = {}) {
  const { pinia: externalPinia, ...rest } = options;

  // If a pre-configured pinia is passed, activate it so store state is readable
  // before the component mounts. Otherwise create a fresh one.
  const pinia = externalPinia ?? createPinia();
  setActivePinia(pinia);

  // Cast to any[] to avoid Vue plugin tuple vs array type mismatch
  const globalPlugins: any[] = [
    i18n,
    pinia,
    [PrimeVue, { theme: { preset: ThemePreset } }],
    ToastService,
    ConfirmationService,
  ];

  return render(component, {
    ...rest,
    global: {
      ...rest.global,
      plugins: [...globalPlugins, ...(rest.global?.plugins ?? [])],
      directives: {
        tooltip: () => {},
        ...rest.global?.directives,
      },
      stubs: {
        ...layoutStubs,
        ...rest.global?.stubs,
      },
    },
  });
}
