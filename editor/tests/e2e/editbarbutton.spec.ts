import { test, expect } from '@playwright/test';
import { LoginPage } from './fixtures';

test.describe('Edit Bar - Alphabetical List', () => {
  test('see alphabetical list button', async ({ page }) => {
    const loginPage = new LoginPage(page);

    // Login as super user
    await loginPage.loginAsRole('super_user');

    // Get last item ID
    const lastItemId = await page.evaluate(async () => {
      const response = await fetch('http://web.salt-default/ims/case/v1p0/CFDocuments?sort=updatedAt&orderBy=DESC&limit=1');
      const json = await response.json();
      const docs = json.CFDocuments || [];
      if (docs.length === 0) return '';

      const identifier = docs[0].identifier;
      const uriResponse = await fetch(`http://web.salt-default/uri/${identifier}/${identifier}`);
      const uriText = await uriResponse.text();
      const match = uriText.match(/\/cftree\/doc\/(\d+)/);
      return match ? match[1] : '';
    });

    await page.goto(`http://web.salt-default/cftree/item/${lastItemId}`);
    await page.waitForLoadState('networkidle');

    // Click Edit button
    await page.click('[data-bs-target="#editItemModal"]');
    await page.waitForSelector('#editItemModal', { timeout: 120000 });
    await page.waitForSelector('#ls_item', { timeout: 120000 });

    // Check for alphabetical sort button
    await expect(page.locator('.fa.fa-sort-alpha-asc')).toBeVisible();
  });
});
