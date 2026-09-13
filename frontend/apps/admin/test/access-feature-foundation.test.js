import assert from "node:assert/strict";
import { access, readFile } from "node:fs/promises";
import test from "node:test";

const featureRoot = new URL("../src/features/access/", import.meta.url);

test("Access Management owns one coordinated feature boundary", async () => {
  await Promise.all(
    [
      "api/.gitkeep",
      "components/AccessManagementPage.tsx",
      "hooks/.gitkeep",
      "invitations/.gitkeep",
      "members/.gitkeep",
      "roles/.gitkeep",
      "routes/MembersRoute.tsx",
      "schemas/.gitkeep",
      "tests/.gitkeep",
      "README.md",
      "index.ts",
    ].map((path) => assert.doesNotReject(access(new URL(path, featureRoot)))),
  );
});

test("the Next.js Members page delegates through the Access feature public entry", async () => {
  const page = await readFile(new URL("../app/app/members/page.tsx", import.meta.url), "utf8");
  const publicEntry = await readFile(new URL("index.ts", featureRoot), "utf8");
  const route = await readFile(new URL("routes/MembersRoute.tsx", featureRoot), "utf8");

  assert.match(page, /features\/access/);
  assert.match(page, /<MembersRoute\s*\/>/);
  assert.match(publicEntry, /\.\/routes\/MembersRoute/);
  assert.match(route, /AccessManagementPage/);
});

test("the foundation documents backend authority without inventing Access contracts", async () => {
  const [readme, page] = await Promise.all([
    readFile(new URL("README.md", featureRoot), "utf8"),
    readFile(new URL("components/AccessManagementPage.tsx", featureRoot), "utf8"),
  ]);

  assert.match(readme, /Symfony remains authoritative/);
  assert.match(readme, /must not reproduce the backend authorization engine/);
  assert.doesNotMatch(page, /fetch\(|ApiClient|PermissionCode|RoleAssignment/);
});
