import type { RouteRecordRaw } from 'vue-router';
import { AuthRoutes } from '@AuthModule';
import { FinanceRoutes, HomeRoutes } from '@HomeModule';
import { BrandRoutes, ComponentsRoutes, ShowcaseRoutes } from '@ShowcaseModule';
import { UsersRoutes } from '@UserModule';

export default <RouteRecordRaw[]>[
  ...AuthRoutes,
  ...HomeRoutes,
  ...FinanceRoutes,
  ...BrandRoutes,
  ...ComponentsRoutes,
  ...ShowcaseRoutes,
  ...UsersRoutes,
];
