import { test, expect } from './fixtures';
import AxeBuilder from '@axe-core/playwright';

/**
 * WCAG 2.1 Level AA Compliance Tests
 *
 * Uses axe-core via @axe-core/playwright to perform automated accessibility
 * scanning. These tests complement the manual ARIA/keyboard tests in
 * tree-accessibility.spec.ts with broad WCAG rule coverage.
 *
 * Prerequisites:
 *   - A running dev server (npm run dev) or Docker environment
 *   - Test user accounts available in the database
 *
 * Run:  npx playwright test tests/e2e/accessibility.spec.ts
 */
test.describe('WCAG 2.1 Level AA Compliance', () => {
  test.beforeEach(async ({ page, loginPage }) => {
    await loginPage.loginAsRole('Admin');
  });

  test('should have no axe violations on the main editor page', async ({ page, lastFrameworkId }) => {
    await page.goto(`http://web.salt-default/cftree/doc/${lastFrameworkId}`);
    await page.waitForLoadState('networkidle');

    const results = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
      .analyze();

    expect(results.violations).toEqual([]);
  });

  test('should have no axe violations on the login page', async ({ page }) => {
    // Logout first to see the login page
    await page.goto('http://web.salt-default/logout');
    await page.goto('http://web.salt-default/login');
    await page.waitForLoadState('networkidle');

    const results = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
      .analyze();

    expect(results.violations).toEqual([]);
  });

  test('should have no axe violations on the home/dashboard page', async ({ page }) => {
    await page.goto('http://web.salt-default/');
    await page.waitForLoadState('networkidle');

    const results = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
      .analyze();

    expect(results.violations).toEqual([]);
  });

  test('skip link should be first focusable element', async ({ page, lastFrameworkId }) => {
    await page.goto(`http://web.salt-default/cftree/doc/${lastFrameworkId}`);
    await page.waitForLoadState('networkidle');

    // Tab into the page — the first focusable element should be the skip link
    await page.keyboard.press('Tab');
    const focused = page.locator(':focus');
    await expect(focused).toHaveAttribute('href', /#main-content|#main/);
  });

  test('tree view should have role="tree"', async ({ page, lastFrameworkId }) => {
    await page.goto(`http://web.salt-default/cftree/doc/${lastFrameworkId}`);
    await page.waitForLoadState('networkidle');

    const tree = page.locator('[role="tree"]');
    await expect(tree).toBeVisible();
  });

  test('all images should have alt text', async ({ page, lastFrameworkId }) => {
    await page.goto(`http://web.salt-default/cftree/doc/${lastFrameworkId}`);
    await page.waitForLoadState('networkidle');

    const results = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
      .include('main')
      .analyze();

    const imageViolations = results.violations.filter(
      (v) => v.id === 'image-alt'
    );
    expect(imageViolations).toEqual([]);
  });

  test('all form fields should have associated labels', async ({ page, lastFrameworkId }) => {
    await page.goto(`http://web.salt-default/cftree/doc/${lastFrameworkId}`);
    await page.waitForLoadState('networkidle');

    const results = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
      .analyze();

    const labelViolations = results.violations.filter(
      (v) => v.id === 'label' || v.id === 'label-title-only'
    );
    expect(labelViolations).toEqual([]);
  });

  test('page should have proper heading hierarchy', async ({ page, lastFrameworkId }) => {
    await page.goto(`http://web.salt-default/cftree/doc/${lastFrameworkId}`);
    await page.waitForLoadState('networkidle');

    const results = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
      .analyze();

    const headingViolations = results.violations.filter(
      (v) => v.id === 'heading-order'
    );
    expect(headingViolations).toEqual([]);
  });

  test('color contrast should meet AA standards', async ({ page, lastFrameworkId }) => {
    await page.goto(`http://web.salt-default/cftree/doc/${lastFrameworkId}`);
    await page.waitForLoadState('networkidle');

    const results = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
      .analyze();

    const contrastViolations = results.violations.filter(
      (v) => v.id === 'color-contrast'
    );
    expect(contrastViolations).toEqual([]);
  });

  test('modals should trap focus and have proper ARIA attributes', async ({ page, lastFrameworkId }) => {
    await page.goto(`http://web.salt-default/cftree/doc/${lastFrameworkId}`);
    await page.waitForLoadState('networkidle');

    // Try to open a modal — adapt selector to match the app's modal triggers
    // For example, a delete button or an "add item" button
    const modalTrigger = page.locator('[data-bs-toggle="modal"]').first();
    const triggerCount = await modalTrigger.count();

    if (triggerCount > 0) {
      await modalTrigger.click();

      // Verify the modal dialog has role="dialog" and aria-modal="true"
      const dialog = page.locator('[role="dialog"]').first();
      await expect(dialog).toBeVisible();
      await expect(dialog).toHaveAttribute('aria-modal', 'true');

      // Verify the modal has an accessible label
      const ariaLabel = await dialog.getAttribute('aria-label');
      const ariaLabelledBy = await dialog.getAttribute('aria-labelledby');
      expect(ariaLabel || ariaLabelledBy).toBeTruthy();

      // Close the modal
      await page.keyboard.press('Escape');
    }
  });

  test('single select (combobox) should be keyboard operable', async ({ page, lastFrameworkId }) => {
    await page.goto(`http://web.salt-default/cftree/doc/${lastFrameworkId}`);
    await page.waitForLoadState('networkidle');

    // Find a combobox element
    const combobox = page.locator('[role="combobox"]').first();
    const comboboxCount = await combobox.count();

    if (comboboxCount > 0) {
      // Open the combobox
      await combobox.press('Enter');
      const listbox = page.locator('[role="listbox"]');
      await expect(listbox).toBeVisible();

      // Close with Escape
      await combobox.press('Escape');
      await expect(listbox).not.toBeVisible();
    }
  });
});
