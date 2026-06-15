import type { RouteRecordRaw } from 'vue-router';

const PublicStatusRoutes: RouteRecordRaw[] = [
  {
    path: '/status/:slug',
    name: 'public-status-en',
    component: () => import('./PublicStatusView.view.vue'),
    meta: { allowAnonymous: true },
  },
  {
    path: '/status-publico/:slug',
    name: 'public-status-pt-BR',
    component: () => import('./PublicStatusView.view.vue'),
    meta: { allowAnonymous: true },
  },
];

export default PublicStatusRoutes;
