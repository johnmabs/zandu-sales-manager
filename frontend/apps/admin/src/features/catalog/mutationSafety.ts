import { ApiRequestError } from "@zandu/api-client";
import { useCallback, useRef } from "react";

export function hasUnknownCatalogMutationOutcome(error: unknown): boolean {
  return (
    error instanceof ApiRequestError &&
    error.apiError.kind === "network" &&
    (error.outcomeUnknown || error.apiError.message === "The request timed out.")
  );
}

class SingleFlight {
  private inFlight = false;

  async run<Result>(operation: () => Promise<Result>): Promise<Result | undefined> {
    if (this.inFlight) return undefined;
    this.inFlight = true;
    try {
      return await operation();
    } finally {
      this.inFlight = false;
    }
  }
}

export function useCatalogSingleFlight() {
  const singleFlight = useRef(new SingleFlight());
  return useCallback(
    <Result>(operation: () => Promise<Result>) => singleFlight.current.run(operation),
    [],
  );
}
