import { test, expect } from './fixtures';

test.describe('Document Comments', () => {
  test('see comments section as anonymous user', async ({ page, lastFrameworkId }) => {
    await page.goto(`http://web.salt-default/cftree/doc/${lastFrameworkId}`);
    await page.waitForLoadState('networkidle');

    // Check for comments section
    await expect(page.locator('.jquery-comments')).toBeVisible();
    await expect(page.locator('text=To comment please login first')).toBeVisible();
  });

  test('do not see comments form as anonymous user', async ({ page, lastFrameworkId }) => {
    await page.goto(`http://web.salt-default/cftree/doc/${lastFrameworkId}`);
    await page.waitForLoadState('networkidle');

    // Comments form should not be visible for anonymous users
    await expect(page.locator('.jquery-comments .commenting-field')).not.toBeVisible();
    await expect(page.locator('text=To comment please login first')).toBeVisible();
  });

  test('see comments section as authenticated user', async ({ page, loginPage, lastFrameworkId }) => {
    await loginPage.loginAsRole('Editor');
    await page.goto(`http://web.salt-default/cftree/doc/${lastFrameworkId}`);
    await page.waitForLoadState('networkidle');

    // Commenting field should be visible for authenticated users
    await expect(page.locator('.commenting-field')).toBeVisible();
  });

  test('comment as authenticated user', async ({ page, loginPage, lastFrameworkId }) => {
    await loginPage.loginAsRole('Editor');
    await page.goto(`http://web.salt-default/cftree/doc/${lastFrameworkId}`);
    await page.waitForLoadState('networkidle');

    const commentText = `acceptance doc comment ${lastFrameworkId}`;

    // Create a comment
    await page.click('.jquery-comments .commenting-field .textarea-wrapper .textarea');
    await page.fill('.textarea', commentText);
    await page.click('.jquery-comments .commenting-field .textarea-wrapper .control-row .send');
    await page.waitForSelector('.comment-wrapper .wrapper .content', { timeout: 2000 });

    // Verify comment is visible
    await expect(page.locator(`text=${commentText}`)).toBeVisible();
  });
});
