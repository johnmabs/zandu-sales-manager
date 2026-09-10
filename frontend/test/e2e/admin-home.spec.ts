import { expect, test } from "@playwright/test";

test("Admin redirects its public entry point to login", async ({ page }) => {
  await page.goto("/");

  await expect(page).toHaveTitle("Zandu Admin");
  await expect(page).toHaveURL("/login");
  await expect(page.getByRole("heading", { name: "Connexion" })).toBeVisible();
});
