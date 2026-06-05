import { test, expect } from '@playwright/test'

const BASE = process.env.BASE_URL

test.describe('FoldedNews smoke', () => {
  test.skip(!BASE, 'Set BASE_URL to run smoke tests against a live site.')

  test('homepage loads with main content and a skip link', async ({ page }) => {
    const response = await page.goto('/')
    expect(response?.status() ?? 200).toBeLessThan(400)
    await expect(page.locator('main#main')).toBeVisible()
    await expect(page.locator('a[href="#main"]')).toHaveCount(1) // accessibility skip link
  })

  test('no public local upload URLs in homepage markup', async ({ page }) => {
    await page.goto('/')
    const html = await page.content()
    expect(html).not.toMatch(/wp-content\/uploads\//)
    expect(html).not.toMatch(/app\/uploads\//)
  })

  test('health endpoint responds ok', async ({ request }) => {
    const res = await request.get('/wp-json/foldednews/v1/health')
    expect(res.ok()).toBeTruthy()
    const json = await res.json()
    expect(json.ok).toBe(true)
  })
})
