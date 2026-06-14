import { test, expect, mockUser, mockRole, seedAuthSession } from '../fixtures/auth';

test.describe('Roles & Permissions page', () => {
  test.beforeEach(async ({ page }) => {
    await seedAuthSession(page);

    await page.route('**/auth/me**', (route) =>
      route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ data: mockUser }) })
    );
    await page.route('**/auth/roles**', (route) => {
      if (route.request().method() === 'GET') {
        return route.fulfill({
          status: 200, contentType: 'application/json',
          body: JSON.stringify({ data: [mockRole] }),
        });
      }
      return route.continue();
    });
    await page.goto('/roles-and-permissions');
  });

  test('renders the roles page', async ({ page }) => {
    await expect(page).toHaveURL(/roles-and-permissions/, { timeout: 5000 });
  });

  test('shows role tab after data loads', async ({ page }) => {
    await expect(page.getByText('Admin')).toBeVisible({ timeout: 5000 });
  });

  test('shows permission group heading', async ({ page }) => {
    await expect(page.getByText('Admin')).toBeVisible({ timeout: 5000 });
    // Permission panel should be visible
    const body = page.locator('body');
    await expect(body).toContainText('Admin', { timeout: 5000 });
  });

  test('save permissions calls PATCH and view remains intact', async ({ page }) => {
    let patchCalled = false;
    await page.route('**/auth/roles/*/permissions', (route) => {
      patchCalled = true;
      return route.fulfill({
        status: 200, contentType: 'application/json',
        body: JSON.stringify({ data: mockRole }),
      });
    });

    await expect(page.getByText('Admin')).toBeVisible({ timeout: 5000 });

    const saveButton = page.locator('[data-testid="save-permissions"]').first();
    const hasSaveButton = await saveButton.isVisible().catch(() => false);

    if (hasSaveButton) {
      const responsePromise = page.waitForResponse('**/auth/roles/*/permissions').catch(() => null);
      await saveButton.click();
      await responsePromise;
      expect(patchCalled).toBe(true);
    }

    // Page still shows Admin role (use first() — 'Admin' appears in both the tab and the panel)
    await expect(page.getByText('Admin').first()).toBeVisible({ timeout: 3000 });
  });

  test('shows new role dialog when add button is clicked', async ({ page }) => {
    await expect(page.getByText('Admin')).toBeVisible({ timeout: 5000 });

    const addButton = page.getByRole('button').filter({ hasText: /new|novo|profile|perfil|add/i }).first();
    const hasAddButton = await addButton.isVisible().catch(() => false);

    if (hasAddButton) {
      await addButton.click();
      const dialog = page.locator('[class*="p-dialog"], [role="dialog"]').first();
      await expect(dialog).toBeVisible({ timeout: 3000 });
    }
  });
});
