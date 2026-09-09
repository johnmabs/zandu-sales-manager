import { expect, test } from "@playwright/test";

test("Admin serves its public entry point", async ({ page }) => {
  await page.goto("/");

  await expect(page).toHaveTitle("Zandu Admin");
  await expect(page.getByRole("heading", { name: "Zandu Admin" })).toBeVisible();
});
