import { test, expect, mockUser, seedAuthSession } from '../fixtures/auth';

// The dashboard treats a user with `monitors.update` (or no permissions) as able
// to manage monitors — see canManage in MonitorsView.
const monitorsUser = { ...mockUser, permissions: ['monitors.update'] };

const mockProject = { id: '1', name: 'System', slug: 'system' };

const mockMonitor = {
  id: '1',
  projectId: '1',
  name: 'Homepage',
  type: 'http',
  target: 'https://example.com',
  config: {},
  intervalSeconds: 60,
  timeoutMs: 10000,
  confirmationThreshold: 2,
  recoveryThreshold: 1,
  status: 'up',
  consecutiveFailures: 0,
  consecutiveSuccesses: 5,
  lastCheckedAt: '2026-06-15T10:00:00+00:00',
};

// NOTE: the create-dialog flow (add button → MMainDialog → POST) is covered by the
// MonitorsView integration spec and MAddEditMonitorForm unit spec. This e2e validates
// the end-to-end critical path: authenticated routing + project-scoped data load.
test.describe('Monitors page', () => {
  test.beforeEach(async ({ page }) => {
    await seedAuthSession(page);

    await page.route('**/auth/me**', (route) =>
      route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ data: monitorsUser }) })
    );
    await page.route('**/projects**', (route) =>
      route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ data: [mockProject] }) })
    );
    await page.route('**/monitors**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ data: [mockMonitor] }),
        });
      }

      return route.continue();
    });

    await page.goto('/monitors');
  });

  test('renders the monitors page', async ({ page }) => {
    await expect(page).toHaveURL(/monitors/, { timeout: 5000 });
  });

  test('displays the active project monitors after data loads', async ({ page }) => {
    await expect(page.getByText('Homepage')).toBeVisible({ timeout: 5000 });
  });

  test('shows the monitor target on the card', async ({ page }) => {
    await expect(page.getByText('https://example.com')).toBeVisible({ timeout: 5000 });
  });
});
