import assert from "node:assert/strict";
import { readFile, unlink, writeFile } from "node:fs/promises";
import test from "node:test";
import { fileURLToPath } from "node:url";

import { ESLint } from "eslint";

const packageUrl = new URL("../package.json", import.meta.url);
const eslintConfigUrl = new URL("../eslint.config.js", import.meta.url);
const workspaceDirectory = fileURLToPath(new URL("../", import.meta.url));
const boundaryFixtureUrl = new URL("../apps/admin/.lint-boundary-fixture.ts", import.meta.url);

test("workspace exposes the Foundation code-quality commands", async () => {
  const { scripts } = JSON.parse(await readFile(packageUrl, "utf8"));

  for (const command of ["build", "format", "format:check", "lint", "typecheck", "test"]) {
    assert.equal(typeof scripts[command], "string", `${command} must be a workspace command`);
  }
});

test("lint configuration enforces shared imports, unused-code, and boundary rules", async () => {
  const config = await readFile(eslintConfigUrl, "utf8");

  assert.match(config, /@typescript-eslint\/no-explicit-any/);
  assert.match(config, /@typescript-eslint\/no-unused-vars/);
  assert.match(config, /@typescript-eslint\/consistent-type-assertions/);
  assert.match(config, /import\/order/);
  assert.match(config, /applicationBoundary\("admin", "pos"\)/);
  assert.match(config, /applicationBoundary\("pos", "admin"\)/);
  assert.match(config, /Shared packages must not depend on applications/);
});

test("Admin cannot import POS implementation files", async () => {
  const eslint = new ESLint({ cwd: workspaceDirectory });
  await writeFile(boundaryFixtureUrl, 'import "../pos/src/App";\n');

  try {
    const [result] = await eslint.lintFiles([fileURLToPath(boundaryFixtureUrl)]);

    assert.ok(result.messages.some(({ ruleId }) => ruleId === "import/no-restricted-paths"));
  } finally {
    await unlink(boundaryFixtureUrl);
  }
});
