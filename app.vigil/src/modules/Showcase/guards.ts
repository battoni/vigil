import { ROLES } from '@AuthModule';
import { useUserStore } from '@UserModule';

function superAdminGuard() {
  const userStore = useUserStore();
  if (userStore.user?.role_slug === ROLES.SUPERADMIN) return true;

  return { path: '/' };
}

export { superAdminGuard };
