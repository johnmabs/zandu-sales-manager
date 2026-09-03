import assert from "node:assert/strict";
import test from "node:test";

import { workspacePackageName } from "@zandu/shared";

test("pos resolves shared workspace packages", () => {
  assert.equal(workspacePackageName, "@zandu/shared");
});
