/**
 * Cineflix Food System - Visual Regression Testing Configuration
 * Tool: Playwright + Pixelmatch
 * Standards: WCAG 2.1 AA Compliance
 */

const { test, expect } = require('@playwright/test');

test.describe('Food Management System - Visual Regression', () => {

  // Viewports for responsive testing
  const viewports = [
    { name: 'Mobile', width: 375, height: 667 },
    { name: 'Tablet', width: 768, height: 1024 },
    { name: 'Desktop', width: 1440, height: 900 }
  ];

  for (const viewport of viewports) {
    test(`Visual consistency check on ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await page.goto('http://localhost/CINEFLIX/food.php');

      // 1. Full page snapshot
      await expect(page).toHaveScreenshot(`food-page-${viewport.name}.png`, {
        maxDiffPixelRatio: 0.01 // 1% threshold
      });

      // 2. Component isolation: Food Card
      const foodCard = page.locator('.fs-food-card').first();
      await expect(foodCard).toHaveScreenshot(`food-card-${viewport.name}.png`);

      // 3. Interactive state: Hover
      if (viewport.name === 'Desktop') {
        await foodCard.hover();
        await expect(foodCard).toHaveScreenshot(`food-card-hover.png`);
      }

      // 4. Modal validation
      await foodCard.click();
      const modal = page.locator('.fs-modal-content');
      await expect(modal).toBeVisible();
      await expect(modal).toHaveScreenshot(`food-detail-modal-${viewport.name}.png`);
    });
  }

  test('Accessibility Compliance (WCAG 2.1 AA)', async ({ page }) => {
    await page.goto('http://localhost/CINEFLIX/food.php');
    
    // Check for focus indicators
    await page.keyboard.press('Tab');
    const focused = await page.evaluate(() => document.activeElement.classList.contains('fs-btn'));
    expect(focused).toBeTruthy();

    // Check for image alt tags
    const images = await page.locator('img');
    const count = await images.count();
    for (let i = 0; i < count; i++) {
      const alt = await images.nth(i).getAttribute('alt');
      expect(alt).not.toBeNull();
    }
  });

});
