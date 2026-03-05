const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();

  // Navigate to the scraper.html
  await page.goto('file://' + process.cwd() + '/scraper.html');

  // Wait for initial load
  console.log('Waiting for initial load...');
  await page.waitForSelector('.card');
  const initialCardCount = await page.locator('.card').count();
  console.log(`Initial card count: ${initialCardCount}`);

  // Check if "Load More" button is visible
  const loadMoreBtn = page.locator('#load-more-btn');
  const isVisible = await loadMoreBtn.isVisible();
  console.log(`Load More button visible: ${isVisible}`);

  if (isVisible) {
    console.log('Clicking Load More...');
    await loadMoreBtn.click();

    // Wait for more cards to appear
    try {
      await page.waitForFunction((initialCount) => {
        return document.querySelectorAll('.card').length > initialCount;
      }, initialCardCount, { timeout: 10000 });

      const newCardCount = await page.locator('.card').count();
      console.log(`New card count: ${newCardCount}`);

      if (newCardCount > initialCardCount) {
        console.log('Pagination Success!');
      } else {
        console.log('Pagination Failed: Card count did not increase.');
      }
    } catch (e) {
      console.log('Pagination Failed: Timeout waiting for more cards.');
      // Check for errors in the page
      const statusText = await page.locator('#status').textContent();
      console.log('Status text:', statusText);
    }
  } else {
    console.log('Load More button not visible. Cannot test pagination.');
  }

  await browser.close();
})();
