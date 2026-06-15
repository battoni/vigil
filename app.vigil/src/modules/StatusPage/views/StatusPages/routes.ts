import type { RouteRecordRaw } from 'vue-router';

const StatusPagesRoutes: RouteRecordRaw[] = [
  {
    path: '/status-pages',
    name: 'status-pages-en',
    component: () => import('./StatusPagesView.view.vue'),
  },
  {
    path: '/paginas-de-status',
    name: 'status-pages-pt-BR',
    component: () => import('./StatusPagesView.view.vue'),
  },
];

export default StatusPagesRoutes;
