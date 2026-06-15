import type { RouteRecordRaw } from 'vue-router';
import { AuthRoutes } from '@AuthModule';
import { ChannelsRoutes } from '@ChannelModule';
import { FinanceRoutes, HomeRoutes } from '@HomeModule';
import { IncidentsRoutes } from '@IncidentModule';
import { MonitorDetailRoutes, MonitorsRoutes } from '@MonitorModule';
import { BrandRoutes, ComponentsRoutes, ShowcaseRoutes } from '@ShowcaseModule';
import { StatusPagesRoutes } from '@StatusPageModule';
import { UsersRoutes } from '@UserModule';

export default <RouteRecordRaw[]>[
  ...AuthRoutes,
  ...HomeRoutes,
  ...FinanceRoutes,
  ...BrandRoutes,
  ...ComponentsRoutes,
  ...ShowcaseRoutes,
  ...UsersRoutes,
  ...MonitorsRoutes,
  ...MonitorDetailRoutes,
  ...IncidentsRoutes,
  ...ChannelsRoutes,
  ...StatusPagesRoutes,
];
