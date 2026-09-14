import assert from "node:assert/strict";
import { access, readFile } from "node:fs/promises";
import test from "node:test";

import { visibleAccessNavigation } from "../src/features/access/access-navigation.ts";
import { availableMemberActions } from "../src/features/access/members/memberAuthorization.ts";

const featureRoot = new URL("../src/features/access/", import.meta.url);

test("member actions require both server status and projected permission", () => {
  assert.deepEqual(availableMemberActions("ACTIVE", ["MEMBER_SUSPEND", "ROLE_ASSIGN"]), {
    assignRole: true,
    reactivate: false,
    removeRole: false,
    revoke: false,
    suspend: true,
  });
  assert.equal(
    availableMemberActions("REVOKED", ["MEMBER_SUSPEND", "MEMBER_REVOKE"]).reactivate,
    false,
  );
});

test("Access Management owns one coordinated feature boundary", async () => {
  await Promise.all(
    [
      "api/.gitkeep",
      "components/AccessManagementPage.tsx",
      "components/AccessNavigation.tsx",
      "hooks/.gitkeep",
      "invitations/.gitkeep",
      "members/.gitkeep",
      "roles/.gitkeep",
      "routes/MembersRoute.tsx",
      "routes/AccessRoute.tsx",
      "routes/InvitationsRoute.tsx",
      "routes/RolesRoute.tsx",
      "schemas/.gitkeep",
      "tests/.gitkeep",
      "README.md",
      "index.ts",
    ].map((path) => assert.doesNotReject(access(new URL(path, featureRoot)))),
  );
});

test("Access navigation exposes only sections backed by effective permissions", () => {
  const access = {
    accessibleStoreIds: [],
    authorizationVersion: 1,
    organizationId: "organization-1",
    permissions: ["MEMBER_READ", "ROLE_READ"],
    scope: { type: "ORGANIZATION" },
  };

  assert.deepEqual(
    visibleAccessNavigation(access).map((item) => item.href),
    ["/admin/access/members", "/admin/access/roles"],
  );
  assert.deepEqual(visibleAccessNavigation(undefined), []);
});

test("the Next.js Members page delegates through the Access feature public entry", async () => {
  const page = await readFile(new URL("../app/admin/members/page.tsx", import.meta.url), "utf8");
  const membersPage = await readFile(
    new URL("../app/admin/access/members/page.tsx", import.meta.url),
    "utf8",
  );
  const publicEntry = await readFile(new URL("index.ts", featureRoot), "utf8");
  const route = await readFile(new URL("routes/MembersRoute.tsx", featureRoot), "utf8");

  assert.match(page, /redirect\("\/admin\/access\/members"\)/);
  assert.match(membersPage, /features\/access/);
  assert.match(membersPage, /<MembersRoute\s*\/>/);
  assert.match(publicEntry, /\.\/routes\/MembersRoute/);
  assert.match(route, /AccessManagementPage/);
});

test("the foundation documents backend authority without inventing Access contracts", async () => {
  const [readme, page, navigation] = await Promise.all([
    readFile(new URL("README.md", featureRoot), "utf8"),
    readFile(new URL("components/AccessManagementPage.tsx", featureRoot), "utf8"),
    readFile(new URL("components/AccessNavigation.tsx", featureRoot), "utf8"),
  ]);

  assert.match(readme, /Symfony remains authoritative/);
  assert.match(readme, /must not reproduce the backend authorization engine/);
  assert.doesNotMatch(page, /fetch\(|ApiClient|RoleAssignment/);
  assert.doesNotMatch(navigation, /fetch\(|ApiClient|RoleAssignment/);
});

test("Access Management makes a selected-store actor scope explicit", async () => {
  const page = await readFile(new URL("components/AccessManagementPage.tsx", featureRoot), "utf8");
  assert.match(page, /access\?\.scope\.type === "SELECTED_STORES"/);
  assert.match(page, /accessibleStoreIds\.join/);
});
