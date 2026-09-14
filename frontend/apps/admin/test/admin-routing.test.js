import assert from "node:assert/strict";
import { access } from "node:fs/promises";
import test from "node:test";

const routes = [
  "/login",
  "/admin",
  "/admin/organization",
  "/admin/stores",
  "/admin/stores/[storeId]",
  "/admin/stores/[storeId]/edit",
  "/admin/stores/new",
  "/admin/members",
  "/admin/access",
  "/admin/access/members",
  "/admin/access/members/[memberId]",
  "/admin/access/invitations",
  "/admin/access/invite",
  "/admin/access/roles",
  "/admin/catalog",
  "/admin/pricing",
  "/admin/inventory",
  "/admin/purchasing",
  "/admin/cash",
  "/admin/sales",
];

test("Admin routing exposes independent pages for each foundation capability", async () => {
  await Promise.all(
    routes.map(async (route) => {
      const pageUrl = new URL(`../app${route}/page.tsx`, import.meta.url);

      await assert.doesNotReject(access(pageUrl));
    }),
  );
});
