import {test, expect} from '@playwright/test';
import {checkPage} from './helpers.js';

test('Administrator reading and editing UI — accessibility across screen sizes',async({page},testInfo)=>{
  await page.goto('/staff/login');
  await page.getByLabel('Email address',{exact:true}).fill('admin@browser.example.test');
  await page.getByLabel('Password',{exact:true}).fill('BrowserOnlyPassword123!');
  await page.getByRole('button',{name:'Sign in',exact:true}).click();
  await expect(page).toHaveURL(/\/staff$/);
  const views=[
    {name:'320-mobile',width:320,height:720},
    {name:'390-mobile',width:390,height:844},
    {name:'820-tablet',width:820,height:1180},
    {name:'1440-desktop',width:1440,height:900},
  ];
  const blockers=[];
  for(const v of views){
    await page.setViewportSize({width:v.width,height:v.height});
    const pages=[
      {name:'dashboard',path:'/staff'},
      {name:'article-library',path:'/staff/articles'},
      {name:'article-editor',path:'/staff/articles/create'},
      {name:'launch-readiness',path:'/staff/launch-readiness'},
    ];
    for(const route of pages){
      const resp=await page.goto(route.path,{waitUntil:'domcontentloaded',timeout:30000});
      expect(resp?.status(),route.path).toBe(200);
      await expect(page.locator('main')).toBeVisible();
      const result=await checkPage(page,testInfo,`staff-${v.name}-${route.name}`);
      blockers.push(...result.blockers.map(issue=>({route:route.path,viewport:v.name,issue:issue.id,impact:issue.impact,nodes:issue.totalElements})));
    }
  }
  expect(blockers,'Staff serious WCAG violations').toEqual([]);
});
test('Staff mobile menu and accessible search',async({page})=>{
  await page.setViewportSize({width:390,height:844});
  await page.goto('/staff/login');
  await page.getByLabel('Email address',{exact:true}).fill('admin@browser.example.test');
  await page.getByLabel('Password',{exact:true}).fill('BrowserOnlyPassword123!');
  await page.getByRole('button',{name:'Sign in',exact:true}).click();
  const menu=page.locator('.staff-mobile-nav');
  await expect(menu.locator(':scope > summary')).toBeVisible();
  await menu.locator('summary').click();
  await expect(menu).toHaveAttribute('open');
  await expect(menu.getByRole('link',{name:'Articles',exact:true})).toBeVisible();
  await menu.getByRole('link',{name:'Articles',exact:true}).click();
  await expect(page).toHaveURL(/\/staff\/articles/);
  await page.locator('#workspace-search').fill('Safari');
  await page.getByRole('button',{name:'Search',exact:true}).first().click();
  await expect(page).toHaveURL(/\/staff\/articles\?q=Safari/);
});

test('Local preview HTML meta matches noindex header',async({page})=>{
  const response=await page.goto('/');
  expect(response?.headers()['x-robots-tag']).toContain('noindex');
  await expect(page.locator('meta[name="robots"]')).toHaveAttribute('content',/noindex/);
});
