import { mkdir, writeFile } from 'node:fs/promises';
import axe from 'axe-core';
import { expect } from '@playwright/test';

function slug(value) {
  return value.replace(/[^a-zA-Z0-9-]+/g,'-').replace(/^-|-$/g,'').toLowerCase();
}
export async function checkPage(page, testInfo, label, { screenshot = true } = {}) {
  const target = slug(label);
  await mkdir('artifacts/qa', { recursive:true });
  await page.locator('main').first().waitFor({state:'visible',timeout:10000});
  // Respect system and author reduced-motion settings. Never force scrolling.
  await page.emulateMedia({reducedMotion:'reduce'});
  await page.evaluate(() => document.fonts.ready).catch(() => {});
  await page.locator('img').evaluateAll(images => {
    images.forEach(image => { image.loading = 'eager'; });
  });
  await page.waitForLoadState('networkidle',{timeout:4500}).catch(() => {});
  if (screenshot) await page.screenshot({path:`artifacts/qa/${target}.png`,animations:'disabled',fullPage:true,timeout:15000});
  const horizontal = await page.evaluate(() => ({
    viewport: innerWidth,
    document: document.documentElement.scrollWidth,
    overflow: Math.max(0,document.documentElement.scrollWidth-innerWidth),
  }));
  // Playwright's evaluation runs in an isolated world and is not blocked by the app CSP.
  await page.evaluate(axe.source);
  const results = await page.evaluate(async() => {
    return await window.axe.run(document,{runOnly:{type:'tag',values:['wcag2a','wcag2aa','wcag21a','wcag21aa']},resultTypes:['violations','incomplete']});
  });
  const brokenImages = await page.locator('img').evaluateAll(images => images.filter(img => img.complete && img.naturalWidth === 0).map(img => img.currentSrc || img.src).slice(0,10));
  const violations = results.violations.map(({id,impact,description,help,helpUrl,nodes}) => ({
    id,impact,description,help,helpUrl,
    elements:nodes.slice(0,8).map(n=>({target:n.target,summary:n.failureSummary})),
    totalElements:nodes.length,
  }));
  await writeFile(`artifacts/qa/${target}.json`,JSON.stringify({
    label, url:page.url(), viewport:horizontal, brokenImages, violations,
    manualReviewNeeded:results.incomplete.map(v=>({id:v.id,impact:v.impact,nodeCount:v.nodes.length})),
  },null,2));
  console.log(`AR_AXE_QA ${JSON.stringify({label,violations:violations.map(v=>({id:v.id,impact:v.impact,nodes:v.totalElements})),brokenImages:brokenImages.length,overflow:horizontal.overflow})}`);
  expect(horizontal.overflow,`Horizontal viewport overflow: ${label}`).toBeLessThanOrEqual(1);
  const blockers=violations.filter(v=>['critical','serious'].includes(v.impact));
  return {horizontal, violations, blockers, brokenImages};
}
