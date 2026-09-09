import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

import {
  parseAdminTableQuery,
  toAdminTableSearchParams,
  toggleTableSort,
  withTableFilter,
  withTableSearch,
} from "../src/components/tables/query-state.ts";

const tableComponentUrl = new URL("../src/components/tables/AdminTable.ts", import.meta.url);

test("table query state round-trips page, sort, search, and feature filters through the URL", () => {
  const parsed = parseAdminTableQuery(
    new URLSearchParams("page=3&sort=name&direction=desc&search=rice&filter.status=ACTIVE"),
  );

  assert.deepEqual(parsed, {
    filters: { status: "ACTIVE" },
    page: 3,
    search: "rice",
    sort: { direction: "desc", field: "name" },
  });
  assert.equal(
    toAdminTableSearchParams(parsed).toString(),
    "page=3&search=rice&sort=name&direction=desc&filter.status=ACTIVE",
  );
  assert.deepEqual(withTableSearch(parsed, "beans"), { ...parsed, page: 1, search: "beans" });
  assert.deepEqual(withTableFilter(parsed, "status", undefined), {
    ...parsed,
    filters: {},
    page: 1,
  });
  assert.deepEqual(toggleTableSort(parsed, "sku"), {
    ...parsed,
    page: 1,
    sort: { direction: "asc", field: "sku" },
  });
});

test("AdminTable keeps semantic table, state, sort, action, and pagination contracts", async () => {
  const component = await readFile(tableComponentUrl, "utf8");

  assert.match(component, /createElement\(Spinner, \{ label: "Chargement du tableau" \}\)/);
  assert.match(component, /createElement\(ErrorState/);
  assert.match(component, /createElement\(EmptyState/);
  assert.match(component, /"aria-sort": sortDirectionFor/);
  assert.match(component, /"table",/);
  assert.match(component, /rowActions\?: \(row: Row\) => ReactNode/);
  assert.match(component, /createElement\(Pagination,/);
});
