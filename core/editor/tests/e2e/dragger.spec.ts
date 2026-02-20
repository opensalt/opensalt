import { test, expect } from './fixtures';

test.describe('Side Panel - Drag and Toggle', () => {
  test('drag panel to the right', async ({ page, lastFrameworkId }) => {
    await page.goto(`http://web.salt-default/cftree/doc/${lastFrameworkId}`);
    await page.waitForLoadState('networkidle');

    // Get initial widths
    const leftWidthBefore = await page.locator('#treeSideLeft').evaluate(el => el.offsetWidth);
    const rightWidthBefore = await page.locator('#treeSideRight').evaluate(el => el.offsetWidth);

    // Drag the dragbar to the right
    const dragbar = page.locator('#dragbar');
    const dragbarBox = await dragbar.boundingBox();
    await dragbar.dragTo(
      dragbarBox.x + 200,
      dragbarBox.y
    );

    // Get new widths
    const leftWidthAfter = await page.locator('#treeSideLeft').evaluate(el => el.offsetWidth);
    const rightWidthAfter = await page.locator('#treeSideRight').evaluate(el => el.offsetWidth);

    // Verify left side is larger after dragging right
    expect(leftWidthAfter).toBeGreaterThan(leftWidthBefore);
  });

  test('drag panel to the left', async ({ page, lastFrameworkId }) => {
    await page.goto(`http://web.salt-default/cftree/doc/${lastFrameworkId}`);
    await page.waitForLoadState('networkidle');

    // Get initial widths
    const leftWidthBefore = await page.locator('#treeSideLeft').evaluate(el => el.offsetWidth);
    const rightWidthBefore = await page.locator('#treeSideRight').evaluate(el => el.offsetWidth);

    // Drag the dragbar to the left
    const dragbar = page.locator('#dragbar');
    const dragbarBox = await dragbar.boundingBox();
    await dragbar.dragTo(
      dragbarBox.x - 200,
      dragbarBox.y
    );

    // Get new widths
    const leftWidthAfter = await page.locator('#treeSideLeft').evaluate(el => el.offsetWidth);
    const rightWidthAfter = await page.locator('#treeSideRight').evaluate(el => el.offsetWidth);

    // Verify right side is larger after dragging left
    expect(rightWidthAfter).toBeGreaterThan(rightWidthBefore);
  });

  test('toggle right window', async ({ page, lastFrameworkId }) => {
    await page.goto(`http://web.salt-default/cftree/doc/${lastFrameworkId}`);
    await page.waitForLoadState('networkidle');

    // Click toggle right button
    await page.click('#toggleRight');

    // Verify left panel takes full width
    const leftWidth = await page.locator('#treeSideLeft').evaluate(el => el.offsetWidth);
    const treeViewWidth = await page.locator('#treeView').evaluate(el => el.offsetWidth);

    expect(leftWidth).toBe(treeViewWidth);

    // Verify right panel is hidden
    await expect(page.locator('#treeSideRight')).not.toBeVisible();
  });

  test('toggle left window', async ({ page, lastFrameworkId }) => {
    await page.goto(`http://web.salt-default/cftree/doc/${lastFrameworkId}`);
    await page.waitForLoadState('networkidle');

    // Click toggle left button
    await page.click('#toggleLeft');

    // Verify right panel takes full width
    const rightWidth = await page.locator('#treeSideRight').evaluate(el => el.offsetWidth);
    const treeViewWidth = await page.locator('#treeView').evaluate(el => el.offsetWidth);

    expect(rightWidth).toBe(treeViewWidth);

    // Verify left panel is hidden
    await expect(page.locator('#treeSideLeft')).not.toBeVisible();
  });
});
