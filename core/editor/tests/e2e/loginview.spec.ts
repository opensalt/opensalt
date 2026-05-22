import { test, expect } from '@playwright/test';

test.describe('Login View', () => {
  test('verify login page elements', async ({ page }) => {
    await page.goto('http://web.salt-default/login');

    // Check for username field
    await expect(page.locator('#username')).toBeVisible();

    // Verify admin help table is not visible
    await expect(page.locator('.js-help-table-admin-users')).not.toBeVisible();

    // Verify password reset message is not visible
    await expect(page.locator('text=If you forget your password, please contact your organization admin, listed here:')).not.toBeVisible();
  });
});
