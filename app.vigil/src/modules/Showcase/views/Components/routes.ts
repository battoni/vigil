import type { RouteRecordRaw } from 'vue-router';
import { superAdminGuard } from '../../guards';

const ComponentsRoutes: RouteRecordRaw[] = [
  {
    path: '/components',
    name: 'components-en',
    component: () => import('./ComponentsView.view.vue'),
    beforeEnter: superAdminGuard,
  },
  {
    path: '/componentes',
    name: 'components-pt-BR',
    component: () => import('./ComponentsView.view.vue'),
    beforeEnter: superAdminGuard,
  },
];

export default ComponentsRoutes;
