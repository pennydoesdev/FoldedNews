import { defineConfig } from '@playwright/test'

/**
 * Smoke + accessibility tests run against a deployed environment. Set BASE_URL
 * (e.g. https://foldednews.ddev.site) to run them; without it the specs skip,
 * so CI stays green until a live target is available.
 */
export default defineConfig({
  testDir: './tests/e2e',
  timeout: 30_000,
  reporter: 'line',
  use: {
    baseURL: process.env.BASE_URL || 'http://localhost',
    trace: 'on-first-retry',
  },
})
