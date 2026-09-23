"use client";

import { ApiRequestError } from "@zandu/api-client";
import { ErrorMapper } from "@zandu/error-contract";
import { Button, ErrorState } from "@zandu/ui";

export function StockTransferErrorState({
  error,
  onRetry,
}: Readonly<{ error: unknown; onRetry: () => void }>) {
  const mapped = new ErrorMapper().map(
    error instanceof ApiRequestError ? error.apiError : { kind: "response", status: 500 },
  );
  return (
    <ErrorState
      title={mapped.title}
      description={mapped.message}
      action={
        <>
          {mapped.retryable ? (
            <Button onClick={onRetry} type="button">
              Réessayer
            </Button>
          ) : null}
          {mapped.correlationId === undefined ? null : (
            <p>Référence de diagnostic : {mapped.correlationId}</p>
          )}
        </>
      }
    />
  );
}
