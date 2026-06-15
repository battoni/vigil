import type { RouteRecordRaw } from 'vue-router';

const ChannelsRoutes: RouteRecordRaw[] = [
  {
    path: '/channels',
    name: 'channels-en',
    component: () => import('./ChannelsView.view.vue'),
  },
  {
    path: '/canais',
    name: 'channels-pt-BR',
    component: () => import('./ChannelsView.view.vue'),
  },
];

export default ChannelsRoutes;
