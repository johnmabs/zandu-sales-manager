import { defineConfig, devices } from "@playwright/test";

export default defineConfig({
  testDir: "./test/e2e",
  use: {
    baseURL: "http://127.0.0.1:3100",
    trace: "retain-on-failure",
  },
  webServer: {
    command: "pnpm --filter @zandu/admin dev --hostname 127.0.0.1 --port 3100",
    env: {
      NEXT_PUBLIC_API_BASE_URL: "/api/",
      NEXT_PUBLIC_APP_ENV: "test",
    },
    port: 3100,
    reuseExistingServer: !process.env.CI,
  },
  projects: [{ name: "chromium", use: { ...devices["Desktop Chrome"] } }],
});
