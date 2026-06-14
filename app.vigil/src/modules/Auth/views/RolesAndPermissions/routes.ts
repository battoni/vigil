import type { RouteRecordRaw } from 'vue-router';

const RolesAndPermissionsRoutes: RouteRecordRaw[] = [
  {
    path: '/roles-and-permissions',
    name: 'roles-and-permissions-en',
    component: () => import('./RolesAndPermissionsView.view.vue'),
  },
  {
    path: '/permissoes-e-perfis',
    name: 'roles-and-permissions-pt-BR',
    component: () => import('./RolesAndPermissionsView.view.vue'),
  },
  {
    path: '/permissões-e-perfis',
    redirect: '/permissoes-e-perfis',
  },
];

export default RolesAndPermissionsRoutes;
