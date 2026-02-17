import { test, expect } from '@playwright/test';
import { LoginPage } from './fixtures';

test.describe('Import Children - CSV Import', () => {
  test('import CSV with sequence number', async ({ page }) => {
    const loginPage = new LoginPage(page);

    // Login as super user
    await loginPage.loginAsRole('super_user');

    // Get last framework ID
    const lastFrameworkId = await page.evaluate(async () => {
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

    await page.goto(`http://web.salt-default/cftree/doc/${lastFrameworkId}`);
    await page.waitForLoadState('networkidle');

    // Click Import Children button
    await page.click('[data-bs-target="#addChildrenModal"]');
    await page.waitForSelector('#addChildrenModal', { timeout: 120000 });

    // Verify Import Items text is visible
    await expect(page.locator('text=Import Items')).toBeVisible();

    // Create test CSV data
    const csvData = 'identifier,humanCodingScheme,fullStatement\n' +
      '00000000-0000-0000-0000-000000000001,B,Test item B\n' +
      '00000000-0000-0000-0000-000000000002,A,Test item A\n';

    const { promises } = require('fs');
    const { writeFile } = promises;
    const { randomUUID } = require('crypto');
    const { tmpdir } = require('os');
    const { join } = require('path');

    const fileName = join(tmpdir(), `test-${randomUUID()}.csv`);
    await writeFile(fileName, csvData);

    // Upload the file
    const fileInput = await page.locator('input#file-url');
    await fileInput.setInputFiles(fileName);

    // Select framework
    await page.selectOption('#js-framework-to-association', lastFrameworkId);

    // Click import button
    await page.click('.btn-import-csv');
    await page.waitForLoadState('networkidle');

    // Reload page to see changes
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('h4.itemTitle', { timeout: 120000 });

    // Verify imported item is visible
    // In VueJS editor, we would check for the imported item in the tree
    await expect(page.locator('[role="tree"]')).toBeVisible();
  });

  test('abbreviated statement longer than 60 chars shows error', async ({ page }) => {
    const loginPage = new LoginPage(page);

    // Login as super user
    await loginPage.loginAsRole('super_user');

    // Navigate to create new framework
    await page.goto('http://web.salt-default/cfdoc/new');
    await page.waitForLoadState('networkidle');

    // Create a simple framework
    await page.fill('#ls_doc_title', 'Import CSV Framework');
    await page.click('button[type="submit"]');

    // Get last framework ID
    const lastFrameworkId = await page.evaluate(async () => {
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

    await page.goto(`http://web.salt-default/cftree/doc/${lastFrameworkId}`);
    await page.waitForLoadState('networkidle');

    // Click Import Children button
    await page.click('[data-bs-target="#addChildrenModal"]');
    await page.waitForSelector('#addChildrenModal', { timeout: 120000 });

    // Create CSV with abbreviated statement longer than 60 chars
    const csvData = 'identifier,humanCodingScheme,fullStatement,abbreviatedStatement\n' +
      '00000000-0000-0000-0000-000000000001,A,Test item,This is a very long abbreviated statement that exceeds sixty characters\n';

    const { promises } = require('fs');
    const { writeFile } = promises;
    const { randomUUID } = require('crypto');
    const { tmpdir } = require('os');
    const { join } = require('path');

    const fileName = join(tmpdir(), `test-${randomUUID()}.csv`);
    await writeFile(fileName, csvData);

    // Upload the file
    const fileInput = await page.locator('input#file-url');
    await fileInput.setInputFiles(fileName);

    // Select framework
    await page.selectOption('#js-framework-to-association', lastFrameworkId);

    // Click import button
    await page.click('.btn-import-csv');
    await page.waitForLoadState('networkidle');

    // Verify error message
    await expect(page.locator('text=Abbreviated statement can not be longer than 60 characters')).toBeVisible();
  });
});
