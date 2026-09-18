import { ApiRequestError } from "@zandu/api-client";

/** A timed-out mutation may already have committed and must not be replayed blindly. */
export function hasUnknownInventoryMutationOutcome(error: unknown): boolean {
  return (
    error instanceof ApiRequestError &&
    error.apiError.kind === "network" &&
    (error.outcomeUnknown || error.apiError.message === "The request timed out.")
  );
}
