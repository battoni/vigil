import { test as base, expect } from '@playwright/test';
import type { Page } from '@playwright/test';

// The app sets this localStorage hint on login; the route guard skips the /auth/me probe when it
// is absent. Mocked-session tests must seed it so they simulate a properly logged-in user — see
// SESSION_HINT_KEY in src/modules/User/store.ts. Call BEFORE page.goto so it lands before app boot.
export async function seedAuthSession(page: Page): Promise<void> {
  await page.addInitScript(() => localStorage.setItem('app.vigil:session-hint', '1'));
}

// Mock API user returned by GET /auth/me
export const mockUser = {
  id: 1,
  name: 'Alice',
  last_name: 'Smith',
  username: 'alice',
  role: 'Admin',
  role_slug: 'administrator',
  status: 'active',
  permissions: [
    'users.read', 'users.create', 'users.update', 'users.archive', 'users.delete',
  ],
};

export const mockRole = {
  id: 'role-1',
  name: 'Admin',
  slug: 'admin',
  description: '',
  users: [],
  permissionGroups: [
    {
      id: 'group-users',
      nameKey: 'permissions.users',
      icon: 'pi pi-user',
      permissions: [
        { key: 'users.read', labelKey: 'permissions.users.read', value: true },
        { key: 'users.create', labelKey: 'permissions.users.create', value: false },
      ],
    },
  ],
};

// Fixture that intercepts GET /auth/me so protected routes render as authenticated.
export const test = base.extend<{ authenticated: void }>({
  authenticated: [
    async ({ page }, use) => {
      await seedAuthSession(page);

      // Intercept all API calls for the test session
      await page.route('**/auth/me**', (route) =>
        route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ data: mockUser }) })
      );
      await page.route('**/auth/users**', (route) =>
        route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ data: [mockUser] }) })
      );
      await page.route('**/auth/roles**', (route) =>
        route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ data: [mockRole] }) })
      );
      await use();
    },
    { auto: false },
  ],
});

export { expect };
