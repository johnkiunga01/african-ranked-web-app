import { test, expect } from '@playwright/test';
import { checkPage } from './helpers.js';

// The disposable local app loads the QA branch and mirrors its current UI/CSS.
// This validates fixes before the separately audited Railway deployment changes.
for (const viewport of [
  {name:'320-mobile',width:320,height:720},
  {name:'390-mobile',width:390,height:844},
  {name:'820-tablet',width:820,height:1180},
  {name:'1440-desktop',width:1440,height:900},
]) {
  test(`Local candidate WCAG: ${viewport.name}`,async({page},testInfo)=>{
    await page.setViewportSize({width:viewport.width,height:viewport.height});
    const blockers=[];
    for (const route of [
      {name:'homepage',path:'/'},
      {name:'rankings',path:'/rankings'},
      {name:'travel',path:'/categories/travel'},
      {name:'article',path:'/articles/top-10-safari-destinations-africa-2026'},
    ]) {
      const response=await page.goto(route.path,{waitUntil:'domcontentloaded',timeout:30000});
      expect(response?.status(),route.path).toBe(200);
      const result=await checkPage(page,testInfo,`candidate-${viewport.name}-${route.name}`);
      blockers.push(...result.blockers.map(v=>({page:route.path,rule:v.id,nodes:v.totalElements})));
    }
    expect(blockers,`Candidate severe WCAG issues on ${viewport.name}`).toEqual([]);
  });
}
