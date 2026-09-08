import assert from "node:assert/strict";
import test from "node:test";

import {
  applyServerFieldErrors,
  formSubmissionState,
  validateLocalPayload,
  z,
} from "../packages/forms/src/index.ts";

const organizationSchema = z.object({
  name: z.string().trim().min(1, "Le nom est requis."),
  timeZone: z.string().min(1, "Le fuseau horaire est requis."),
});

test("runtime schema validation accepts local form payloads and exposes field errors", () => {
  const valid = validateLocalPayload(organizationSchema, {
    name: "Zandu",
    timeZone: "Africa/Lagos",
  });
  assert.equal(valid.valid, true);
  if (valid.valid) {
    assert.deepEqual(valid.data, { name: "Zandu", timeZone: "Africa/Lagos" });
  }

  const invalid = validateLocalPayload(organizationSchema, { name: "", timeZone: "" });
  assert.equal(invalid.valid, false);
  if (!invalid.valid) {
    assert.deepEqual(invalid.fieldErrors, {
      name: ["Le nom est requis."],
      timeZone: ["Le fuseau horaire est requis."],
    });
  }
});

test("backend field errors are applied as server errors without replacing client validation", () => {
  const calls = [];
  applyServerFieldErrors((field, error) => calls.push({ field, error }), {
    name: ["Ce nom est déjà utilisé."],
    timeZone: ["Le fuseau horaire n’est pas pris en charge."],
  });

  assert.deepEqual(calls, [
    { error: { message: "Ce nom est déjà utilisé.", type: "server" }, field: "name" },
    {
      error: { message: "Le fuseau horaire n’est pas pris en charge.", type: "server" },
      field: "timeZone",
    },
  ]);
});

test("submission state keeps dirty state distinct from disabled and submitting state", () => {
  assert.deepEqual(formSubmissionState({ disabled: false, isDirty: true, isSubmitting: false }), {
    canSubmit: true,
    hasUnsavedChanges: true,
    isDisabled: false,
    isSubmitting: false,
  });
  assert.deepEqual(formSubmissionState({ disabled: true, isDirty: false, isSubmitting: true }), {
    canSubmit: false,
    hasUnsavedChanges: false,
    isDisabled: true,
    isSubmitting: true,
  });
});
