import type { RouteRecordRaw } from 'vue-router';

const MonitorsRoutes: RouteRecordRaw[] = [
  {
    path: '/monitors',
    name: 'monitors-en',
    component: () => import('./MonitorsView.view.vue'),
  },
  {
    path: '/monitores',
    name: 'monitors-pt-BR',
    component: () => import('./MonitorsView.view.vue'),
  },
];

export default MonitorsRoutes;
