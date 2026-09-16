import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

const workflowUrl = new URL("../../.github/workflows/frontend-ci.yml", import.meta.url);
const backendWorkflowUrl = new URL("../../.github/workflows/backend-ci.yml", import.meta.url);
const makefileUrl = new URL("../../Makefile", import.meta.url);

test("frontend CI validates quality, web builds, Tauri compilation, and isolated E2E", async () => {
  const workflow = await readFile(workflowUrl, "utf8");

  for (const command of [
    "pnpm install --frozen-lockfile",
    "pnpm format:check",
    "pnpm lint",
    "pnpm typecheck",
    "pnpm test",
    "pnpm --filter @zandu/admin build",
    "pnpm --filter @zandu/pos build",
    "cargo check --manifest-path apps/pos/src-tauri/Cargo.toml --locked",
    "pnpm exec playwright install --with-deps chromium",
    "pnpm test:e2e",
  ]) {
    assert.match(workflow, new RegExp(command.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")));
  }

  assert.match(workflow, /quality:/);
  assert.match(workflow, /tauri-check:/);
  assert.match(workflow, /e2e:/);
  assert.match(workflow, /timeout-minutes: 15/);
});

test("backend CI blocks stale generated OpenAPI clients", async () => {
  const [workflow, makefile] = await Promise.all([
    readFile(backendWorkflowUrl, "utf8"),
    readFile(makefileUrl, "utf8"),
  ]);

  assert.match(workflow, /pnpm install --frozen-lockfile/);
  assert.match(workflow, /make frontend-openapi-check/);
  assert.match(makefile, /frontend-openapi-check: frontend-openapi/);
  assert.match(
    makefile,
    /git diff --exit-code -- frontend\/packages\/api-client\/src\/generated\/schema\.ts/,
  );
});
