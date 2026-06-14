import { createRouter, createWebHistory } from 'vue-router';
import attachAuthGuard from '@Helpers/attachAuthGuard.helper';
import routes from './routes';

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
});

// attachAuthGuard closes a dependency cycle back to @Libraries; this is safe only because of
// the import order documented in libraries/index.ts (@UserModule is evaluated before this runs).
attachAuthGuard(router);

export default router;
