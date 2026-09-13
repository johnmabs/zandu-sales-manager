import assert from "node:assert/strict";
import { access } from "node:fs/promises";
import test from "node:test";

const routes = [
  "/login",
  "/app",
  "/app/organization",
  "/app/stores",
  "/app/stores/[storeId]",
  "/app/stores/[storeId]/edit",
  "/app/stores/new",
  "/app/members",
  "/app/access",
  "/app/access/members",
  "/app/access/members/[memberId]",
  "/app/access/invitations",
  "/app/access/invite",
  "/app/access/roles",
  "/app/catalog",
  "/app/pricing",
  "/app/inventory",
  "/app/purchasing",
  "/app/cash",
  "/app/sales",
];

test("Admin routing exposes independent pages for each foundation capability", async () => {
  await Promise.all(
    routes.map(async (route) => {
      const pageUrl = new URL(`../app${route}/page.tsx`, import.meta.url);

      await assert.doesNotReject(access(pageUrl));
    }),
  );
});
