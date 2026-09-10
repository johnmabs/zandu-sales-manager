import assert from "node:assert/strict";
import test from "node:test";

import { resolveAdminApiBaseUrl } from "../src/runtime/adminApiBaseUrl.ts";

test("same-origin API paths follow the browser origin", () => {
  assert.equal(
    resolveAdminApiBaseUrl("/api/", "http://127.0.0.1:3000"),
    "http://127.0.0.1:3000/api/",
  );
});

test("absolute API URLs remain unchanged", () => {
  assert.equal(
    resolveAdminApiBaseUrl("https://api.zandu.test/api/", "https://admin.zandu.test"),
    "https://api.zandu.test/api/",
  );
});

test("protocol-relative URLs are not treated as same-origin paths", () => {
  assert.equal(
    resolveAdminApiBaseUrl("//untrusted.example/api/", "https://admin.zandu.test"),
    "//untrusted.example/api/",
  );
});
