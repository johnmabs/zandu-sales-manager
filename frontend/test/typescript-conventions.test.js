import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

const baseConfigUrl = new URL("../tsconfig.base.json", import.meta.url);
const adminConfigUrl = new URL("../apps/admin/tsconfig.json", import.meta.url);
const posAppConfigUrl = new URL("../apps/pos/tsconfig.app.json", import.meta.url);
const posNodeConfigUrl = new URL("../apps/pos/tsconfig.node.json", import.meta.url);

async function readJson(url) {
  return JSON.parse(await readFile(url, "utf8"));
}

test("shared TypeScript base configuration enforces strict conventions", async () => {
  const { compilerOptions } = await readJson(baseConfigUrl);

  assert.deepEqual(compilerOptions, {
    strict: true,
    noImplicitAny: true,
    noUncheckedIndexedAccess: true,
    useUnknownInCatchVariables: true,
    exactOptionalPropertyTypes: true,
    noImplicitOverride: true,
    forceConsistentCasingInFileNames: true,
    allowJs: false,
    skipLibCheck: true,
    esModuleInterop: true,
    resolveJsonModule: true,
    isolatedModules: true,
  });
});

test("Admin and POS inherit the shared TypeScript conventions", async () => {
  const [adminConfig, posAppConfig, posNodeConfig] = await Promise.all([
    readJson(adminConfigUrl),
    readJson(posAppConfigUrl),
    readJson(posNodeConfigUrl),
  ]);

  assert.equal(adminConfig.extends, "../../tsconfig.base.json");
  assert.equal(posAppConfig.extends, "../../tsconfig.base.json");
  assert.equal(posNodeConfig.extends, "../../tsconfig.base.json");
});
