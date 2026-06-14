import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createMemoryHistory, createRouter } from 'vue-router';
import type * as UserModule from '@UserModule';
import type { Pinia } from 'pinia';
import type { Router } from 'vue-router';
import i18n from '@Libraries/i18n';
import ThemePreset from '@Libraries/primevue/theme';

const { getMeService } = vi.hoisted(() => ({ getMeService: vi.fn() }));

vi.mock('@UserModule', async (importActual) => {
  const actual = await importActual<typeof UserModule>();

  return { ...actual, GetMeService: getMeService };
});

const { useUserStore } = await import('@UserModule');
const { default: TheRouteGuard } = await import('./TheRouteGuard.vue');

const SESSION_HINT_KEY = 'app.vigil:session-hint';

const mockUser = {
  id: 1,
  name: 'Alice',
  last_name: 'Smith',
  username: 'alice',
  role: 'Admin',
  role_slug: 'admin',
  permissions: ['users.read'],
};

const blank = { template: '<div />' };

function buildRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { name: 'home', path: '/', component: blank },
      { name: 'login', path: '/entrar', component: blank, meta: { isPublic: true } },
      { name: 'protected', path: '/protected', component: blank },
    ],
  });
}

function mountGuard(router: Router, pinia: Pinia) {
  return mount(TheRouteGuard, {
    slots: { default: '<div class="protected-content">secret</div>' },
    global: {
      plugins: [i18n, pinia, [PrimeVue, { theme: { preset: ThemePreset } }], ToastService, ConfirmationService, router],
      directives: { tooltip: () => {} },
    },
  });
}

beforeEach(() => {
  localStorage.clear();
  getMeService.mockReset();
  getMeService.mockResolvedValue({ data: null });
});

describe('TheRouteGuard — boot session probe', () => {
  it('skips the /auth/me probe on a public route with no session hint', async () => {
    const router = buildRouter();
    router.push('/entrar');
    await router.isReady();

    const pinia = createPinia();
    setActivePinia(pinia);

    const wrapper = mountGuard(router, pinia);
    await flushPromises();

    expect(getMeService).not.toHaveBeenCalled();
    expect(wrapper.find('.protected-content').exists()).toBe(true);
  });

  it('skips the probe and redirects to login on a protected route with no session hint', async () => {
    const router = buildRouter();
    router.push('/protected');
    await router.isReady();

    const pinia = createPinia();
    setActivePinia(pinia);

    mountGuard(router, pinia);
    await flushPromises();

    expect(getMeService).not.toHaveBeenCalled();
    expect(router.currentRoute.value.name).toBe('login');
  });

  it('still probes /auth/me on a protected route when a session hint exists', async () => {
    const router = buildRouter();
    router.push('/protected');
    await router.isReady();

    const pinia = createPinia();
    setActivePinia(pinia);
    localStorage.setItem(SESSION_HINT_KEY, '1');

    mountGuard(router, pinia);
    await flushPromises();

    expect(getMeService).toHaveBeenCalledOnce();
  });

  it('renders immediately for an already authenticated user without probing', async () => {
    const router = buildRouter();
    router.push('/protected');
    await router.isReady();

    const pinia = createPinia();
    setActivePinia(pinia);
    useUserStore().setUserAndPermissions(mockUser as never, ['users.read']);

    const wrapper = mountGuard(router, pinia);
    await flushPromises();

    expect(getMeService).not.toHaveBeenCalled();
    expect(wrapper.find('.protected-content').exists()).toBe(true);
  });
});
