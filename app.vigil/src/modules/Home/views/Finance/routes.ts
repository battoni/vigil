import type { RouteRecordRaw } from 'vue-router';

export default <RouteRecordRaw[]>[
  {
    path: '/finance',
    name: 'finance',
    component: () => import('./FinanceView.view.vue'),
  },
];
