import { test, expect } from './fixtures';

test.describe('CF Document - Create/Import Buttons', () => {
  test('editor can see create and import buttons', async ({ page, loginPage }) => {
    await loginPage.loginAsRole('Editor');
    await page.goto('http://web.salt-default/cfdoc');

    // Click main menu dropdown
    await page.click('header a.dropdown-toggle svg[aria-label="Main Menu"]');

    // Check for Add framework and Import framework buttons
    await expect(page.locator('text=Add framework')).toBeVisible();
    await expect(page.locator('text=Import framework')).toBeVisible();
  });

  test('user cannot see create and import buttons', async ({ page, loginPage }) => {
    await loginPage.loginAsRole('User');
    await page.goto('http://web.salt-default/cfdoc');

    // Click main menu dropdown
    await page.click('header a.dropdown-toggle svg[aria-label="Main Menu"]');

    // These buttons should not be visible for regular users
    await expect(page.locator('text=Add framework')).not.toBeVisible();
    await expect(page.locator('text=Import framework')).not.toBeVisible();
  });
});
