import type { RouteRecordRaw } from 'vue-router';
import { ForgotPasswordRoutes, LoginRoutes, RolesAndPermissionsRoutes, SignUpRoutes, TermsRoutes } from './views';

export default <RouteRecordRaw[]>[
  ...ForgotPasswordRoutes,
  ...LoginRoutes,
  ...SignUpRoutes,
  ...TermsRoutes,
  ...RolesAndPermissionsRoutes,
];
