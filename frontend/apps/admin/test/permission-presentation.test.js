import assert from "node:assert/strict";
import test from "node:test";

import { permissionPresentation } from "../src/features/access/permissions/permissionPresentation.ts";

test("permission presentation translates known PermissionCode values without making authorization decisions", () => {
  assert.deepEqual(permissionPresentation("ROLE_ASSIGN"), {
    description: "Attribuer des rôles aux membres.",
    label: "Attribuer des rôles",
  });
});

test("permission presentation retains an unknown server code safely", () => {
  const presentation = permissionPresentation("FUTURE_PERMISSION");

  assert.match(presentation.label, /FUTURE_PERMISSION/);
  assert.match(presentation.description, /FUTURE_PERMISSION/);
});
