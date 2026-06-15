import type { RouteRecordRaw } from 'vue-router';

const MonitorDetailRoutes: RouteRecordRaw[] = [
  {
    path: '/monitors/:id',
    name: 'monitor-detail-en',
    component: () => import('./MonitorDetailView.view.vue'),
  },
  {
    path: '/monitores/:id',
    name: 'monitor-detail-pt-BR',
    component: () => import('./MonitorDetailView.view.vue'),
  },
];

export default MonitorDetailRoutes;
