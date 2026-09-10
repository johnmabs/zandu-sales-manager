import { ApiRequestError } from "@zandu/api-client";
import { useCallback, useRef } from "react";

/**
 * A timeout only means the client did not receive a response. It never proves
 * that Symfony did not complete the command.
 */
export function hasUnknownStoreMutationOutcome(error: unknown): boolean {
  return (
    error instanceof ApiRequestError &&
    error.apiError.kind === "network" &&
    (error.outcomeUnknown || error.apiError.message === "The request timed out.")
  );
}

export class SingleFlight {
  private inFlight = false;

  async run<Result>(operation: () => Promise<Result>): Promise<Result | undefined> {
    if (this.inFlight) {
      return undefined;
    }

    this.inFlight = true;
    try {
      return await operation();
    } finally {
      this.inFlight = false;
    }
  }
}

export function useSingleFlight() {
  const singleFlight = useRef(new SingleFlight());

  return useCallback(
    async <Result>(operation: () => Promise<Result>): Promise<Result | undefined> => {
      return singleFlight.current.run(operation);
    },
    [],
  );
}
