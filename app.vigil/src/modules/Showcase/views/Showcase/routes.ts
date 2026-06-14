import type { RouteRecordRaw } from 'vue-router';
import { superAdminGuard } from '../../guards';

const ShowcaseRoutes: RouteRecordRaw[] = [
  {
    path: '/showcase',
    name: 'showcase-en',
    component: () => import('./ShowcaseView.view.vue'),
    beforeEnter: superAdminGuard,
  },
  {
    path: '/vitrine',
    name: 'showcase-pt-BR',
    component: () => import('./ShowcaseView.view.vue'),
    beforeEnter: superAdminGuard,
  },
];

export default ShowcaseRoutes;
