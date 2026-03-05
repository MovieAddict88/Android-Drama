const { chromium } = require('playwright');
const path = require('path');

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();

  const filePath = 'file://' + path.resolve('scraper.html');
  await page.goto(filePath);

  // Wait for content to load
  await page.waitForTimeout(3000);

  console.log('Page title:', await page.title());

  // Take a screenshot of the initial state
  await page.screenshot({ path: 'scraper_initial.png' });

  // Test search
  await page.fill('#search-input', 'Love');
  await page.waitForTimeout(3000);
  await page.screenshot({ path: 'scraper_search.png' });

  // Test clicking a card
  const cards = await page.$$('.card');
  if (cards.length > 0) {
    const firstCardId = await cards[0].$('.id-label');
    const idText = await firstCardId.innerText();
    console.log('First card ID:', idText);

    await cards[0].click();
    await page.waitForTimeout(500);
    await page.screenshot({ path: 'scraper_clicked.png' });

    // Check if is-copied class is added
    const hasClass = await cards[0].evaluate(el => el.classList.contains('is-copied'));
    console.log('First card has is-copied class:', hasClass);
  }

  // Test URL extraction
  await page.fill('#url-input', 'https://m.mydramawave.com/series/MZS0CyDOtb/2Uaq4xlsza?');
  await page.waitForTimeout(1000);
  await page.screenshot({ path: 'scraper_extracted.png' });

  await browser.close();
})();
