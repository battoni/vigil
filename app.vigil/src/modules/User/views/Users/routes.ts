import type { RouteRecordRaw } from 'vue-router';

const UsersRoutes: RouteRecordRaw[] = [
  {
    path: '/users',
    name: 'users-en',
    component: () => import('./UsersView.view.vue'),
  },
  {
    path: '/usuarios',
    name: 'users-pt-BR',
    component: () => import('./UsersView.view.vue'),
  },
  {
    path: '/usuários',
    redirect: '/usuarios',
  },
];

export default UsersRoutes;
