import { test, expect } from './fixtures';

const editorUrl = (frameworkIdentifier: string) =>
  `http://web.salt-default/editor/${frameworkIdentifier}`;

test.describe('Document Comments', () => {
  test('see comments section as anonymous user', async ({ page, lastFrameworkIdentifier }) => {
    await page.goto(editorUrl(lastFrameworkIdentifier));
    await page.waitForLoadState('networkidle');

    await expect(page.locator('.comment-module')).toBeVisible();
    await expect(page.locator('.login-prompt')).toContainText('Log in');
  });

  test('do not see comments form as anonymous user', async ({ page, lastFrameworkIdentifier }) => {
    await page.goto(editorUrl(lastFrameworkIdentifier));
    await page.waitForLoadState('networkidle');

    await expect(page.locator('.comment-textarea')).toBeDisabled();
    await expect(page.locator('.submit-btn')).toBeDisabled();
  });

  test('see comments section as authenticated user', async ({ page, loginPage, lastFrameworkIdentifier }) => {
    await loginPage.loginAsRole('Editor');
    await page.goto(editorUrl(lastFrameworkIdentifier));
    await page.waitForLoadState('networkidle');

    await expect(page.locator('.comment-textarea')).toBeEnabled();
  });

  test('comment as authenticated user', async ({ page, loginPage, lastFrameworkIdentifier }) => {
    await loginPage.loginAsRole('Editor');
    await page.goto(editorUrl(lastFrameworkIdentifier));
    await page.waitForLoadState('networkidle');

    const commentText = `acceptance doc comment ${lastFrameworkIdentifier}`;

    await page.fill('.comment-textarea', commentText);
    await page.click('.submit-btn');
    await page.waitForSelector('.comment-body', { timeout: 5000 });

    await expect(page.locator('.comment-body', { hasText: commentText })).toBeVisible();
  });
});
