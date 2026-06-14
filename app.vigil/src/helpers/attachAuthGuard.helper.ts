import type { NavigationGuardNext, RouteLocationNormalized, Router } from 'vue-router';
import { useGlobalAbortController } from '@Composables';
import { useUserStore } from '@UserModule';

const HOME_NAME = 'home';
const LOGIN_PATH = '/entrar';

function isPublicRoute(to: RouteLocationNormalized): boolean {
  return !!(to.meta as { isPublic?: boolean }).isPublic;
}

export default function attachAuthGuard(router: Router): void {
  const { cancelAllRequests, createNewController } = useGlobalAbortController();

  router.beforeEach((to, from, next: NavigationGuardNext) => {
    const isGoingToDifferentRoute = to.name !== from.name;

    if (isGoingToDifferentRoute) {
      cancelAllRequests();
      createNewController();
    }

    const userStore = useUserStore();

    if (isPublicRoute(to)) {
      // Authenticated users have no business on auth/public pages — send them home.
      if (userStore.user) {
        next({ name: HOME_NAME });
        return;
      }

      next();
      return;
    }

    if (userStore.user) {
      next();
      return;
    }

    // No prior session in this browser — skip the /auth/me probe and the 401 it would trigger.
    if (!userStore.hasSessionHint()) {
      next({ path: LOGIN_PATH });
      return;
    }

    return userStore
      .fetchMe()
      .then(() => next())
      .catch(() => next({ path: LOGIN_PATH }));
  });
}
