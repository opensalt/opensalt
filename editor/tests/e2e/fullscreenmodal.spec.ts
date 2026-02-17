import { test, expect } from './fixtures';

test.describe('Full Screen Modal - Edit Document', () => {
  test('see edit full screen modal', async ({ page, lastFrameworkId }) => {
    // Login as super user
    const { LoginPage } = await import('./fixtures');
    const loginPage = new LoginPage(page);
    await loginPage.loginAsRole('super_user');

    await page.goto(`http://web.salt-default/cftree/doc/${lastFrameworkId}`);
    await page.waitForLoadState('networkidle');

    // Click Edit button
    await page.click('[data-bs-target="#editDocModal"]');
    await page.waitForSelector('#editDocModal');

    // Get modal width
    const modalWidth = await page.locator('#editDocModal .modal-dialog.modal-xl').evaluate(el => el.offsetWidth);

    // Get body width
    const bodyWidth = await page.evaluate(() => document.body.offsetWidth);

    // Verify modal is close to full screen (at least 98% of body width)
    const expectedMinWidth = bodyWidth * 0.98;
    expect(modalWidth).toBeGreaterThan(expectedMinWidth);
  });
});
