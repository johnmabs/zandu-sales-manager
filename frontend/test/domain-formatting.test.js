import assert from "node:assert/strict";
import test from "node:test";

import {
  formatBusinessDate,
  formatDateTime,
  formatMoney,
  formatPercentage,
  formatQuantity,
} from "../packages/domain-formatting/src/index.ts";

test("money and quantities preserve decimal-string precision", () => {
  assert.equal(
    formatMoney("12345678901234567890.50", { currency: "USD", locale: "en-US" }),
    "$12,345,678,901,234,567,890.50",
  );
  assert.equal(formatQuantity("1234.500", { locale: "en-US", unit: "kg" }), "1,234.500 kg");
});

test("percentages are shifted with decimal strings rather than floating point", () => {
  assert.equal(formatPercentage("0.125", { locale: "en-US" }), "12.5%");
  assert.equal(formatPercentage("12.5", { locale: "en-US", valueKind: "percentage" }), "12.5%");
});

test("business dates remain calendar dates and timestamps use the provided store timezone", () => {
  assert.equal(
    formatBusinessDate("2026-09-03", { dateStyle: "long", locale: "en-US" }),
    "September 3, 2026",
  );
  assert.match(
    formatDateTime("2026-09-03T12:30:00Z", {
      dateStyle: "short",
      locale: "en-GB",
      timeStyle: "short",
      timeZone: "Africa/Lagos",
    }),
    /13:30/,
  );
});

test("invalid decimal and business-date transports are rejected", () => {
  assert.throws(() => formatMoney("1e3", { currency: "USD" }), /plain decimal string/);
  assert.throws(() => formatBusinessDate("2026-02-30"), /valid calendar dates/);
});
