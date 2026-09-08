"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useEffect } from "react";
import { useForm } from "react-hook-form";

import type { FieldErrors } from "@zandu/error-contract";
import type {
  FieldValues,
  FormState,
  UseFormProps,
  UseFormReturn,
  UseFormSetError,
} from "react-hook-form";
import type { ZodType } from "zod";

export { z } from "zod";

export type FormSubmissionState = Readonly<{
  canSubmit: boolean;
  hasUnsavedChanges: boolean;
  isDisabled: boolean;
  isSubmitting: boolean;
}>;

export type LocalValidationResult<Value> =
  | Readonly<{
      data: Value;
      valid: true;
    }>
  | Readonly<{
      fieldErrors: FieldErrors;
      valid: false;
    }>;

/**
 * Creates a form with fast client validation. Server validation remains
 * authoritative and its field errors should be applied with
 * applyServerFieldErrors after a failed submission.
 */
export function useZanduForm<Value extends FieldValues>(
  schema: ZodType<Value, Value>,
  options: Omit<UseFormProps<Value, unknown, Value>, "resolver"> = {},
): UseFormReturn<Value, unknown, Value> {
  return useForm<Value, unknown, Value>({
    ...options,
    mode: options.mode ?? "onBlur",
    reValidateMode: options.reValidateMode ?? "onChange",
    resolver: zodResolver(schema),
  });
}

/** Validates selected local payloads without turning client checks into a business authority. */
export function validateLocalPayload<Value>(
  schema: ZodType<Value>,
  payload: unknown,
): LocalValidationResult<Value> {
  const result = schema.safeParse(payload);

  if (result.success) {
    return { data: result.data, valid: true };
  }

  const errors: Record<string, string[]> = {};
  for (const issue of result.error.issues) {
    const field = issue.path.map(String).join(".") || "root";
    const messages = errors[field] ?? [];
    messages.push(issue.message);
    errors[field] = messages;
  }

  return { fieldErrors: errors, valid: false };
}

/** Maps backend validation errors into React Hook Form's server-error channel. */
export function applyServerFieldErrors(
  setError: UseFormSetError<FieldValues>,
  fieldErrors: FieldErrors,
): void {
  for (const [field, messages] of Object.entries(fieldErrors)) {
    const message = messages[0];
    if (message !== undefined) {
      setError(field, { message, type: "server" });
    }
  }
}

export function formSubmissionState(formState: FormState<FieldValues>): FormSubmissionState {
  return {
    canSubmit: !formState.disabled && !formState.isSubmitting,
    hasUnsavedChanges: formState.isDirty,
    isDisabled: formState.disabled,
    isSubmitting: formState.isSubmitting,
  };
}

/** Warns before a browser-level navigation discards dirty form values. */
export function useUnsavedChangesWarning(hasUnsavedChanges: boolean): void {
  useEffect(() => {
    if (!hasUnsavedChanges) {
      return;
    }

    const warnBeforeUnload = (event: BeforeUnloadEvent) => {
      event.preventDefault();
      event.returnValue = "";
    };

    window.addEventListener("beforeunload", warnBeforeUnload);

    return () => window.removeEventListener("beforeunload", warnBeforeUnload);
  }, [hasUnsavedChanges]);
}
