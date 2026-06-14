import type { RouteRecordRaw } from 'vue-router';
import { superAdminGuard } from '../../guards';

const BrandRoutes: RouteRecordRaw[] = [
  {
    path: '/brand',
    name: 'brand-en',
    component: () => import('./BrandView.view.vue'),
    beforeEnter: superAdminGuard,
  },
  {
    path: '/marca',
    name: 'brand-pt-BR',
    component: () => import('./BrandView.view.vue'),
    beforeEnter: superAdminGuard,
  },
];

export default BrandRoutes;
