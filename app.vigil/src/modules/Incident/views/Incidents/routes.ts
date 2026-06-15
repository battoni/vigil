import type { RouteRecordRaw } from 'vue-router';

const IncidentsRoutes: RouteRecordRaw[] = [
  {
    path: '/incidents',
    name: 'incidents-en',
    component: () => import('./IncidentsView.view.vue'),
  },
  {
    path: '/incidentes',
    name: 'incidents-pt-BR',
    component: () => import('./IncidentsView.view.vue'),
  },
];

export default IncidentsRoutes;
