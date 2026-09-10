"use client";

import { ApiRequestError } from "@zandu/api-client";
import { ErrorMapper } from "@zandu/error-contract";
import { Button, ErrorState } from "@zandu/ui";

type StoreErrorScope = "details" | "list";

type StoreErrorPresentation = Readonly<{
  correlationId?: string;
  description: string;
  retryable: boolean;
  title: string;
}>;

const errorMapper = new ErrorMapper();

export function StoreErrorState({
  error,
  onRetry,
  scope,
}: Readonly<{
  error: unknown;
  onRetry?: () => void;
  scope: StoreErrorScope;
}>) {
  const presentation = storeErrorPresentation(error, scope);

  return (
    <ErrorState
      action={
        presentation.retryable || presentation.correlationId !== undefined ? (
          <>
            {presentation.retryable && onRetry !== undefined ? (
              <Button onClick={onRetry} type="button" variant="secondary">
                Réessayer
              </Button>
            ) : null}
            {presentation.correlationId === undefined ? null : (
              <p>Référence de diagnostic : {presentation.correlationId}</p>
            )}
          </>
        ) : undefined
      }
      description={presentation.description}
      title={presentation.title}
    />
  );
}

export function storeErrorPresentation(
  error: unknown,
  scope: StoreErrorScope,
): StoreErrorPresentation {
  if (error instanceof ApiRequestError) {
    const mapped = errorMapper.map(error.apiError);
    if (
      error.apiError.kind === "response" &&
      error.apiError.status === 404 &&
      scope === "details"
    ) {
      return {
        ...(mapped.correlationId === undefined ? {} : { correlationId: mapped.correlationId }),
        description:
          "Ce magasin est introuvable ou n’est pas accessible dans l’organisation active.",
        retryable: mapped.retryable,
        title: "Magasin introuvable",
      };
    }
    if (error.apiError.kind === "response" && error.apiError.status === 403) {
      return {
        ...(mapped.correlationId === undefined ? {} : { correlationId: mapped.correlationId }),
        description:
          scope === "list"
            ? "Votre accès ne permet pas de consulter les magasins de cette organisation."
            : "Votre accès ne permet pas de consulter ce magasin.",
        retryable: mapped.retryable,
        title: "Accès refusé",
      };
    }

    return {
      ...(mapped.correlationId === undefined ? {} : { correlationId: mapped.correlationId }),
      description: mapped.message,
      retryable: mapped.retryable,
      title: mapped.title,
    };
  }

  const mapped = errorMapper.map({ kind: "response", status: 500 });
  return {
    description: mapped.message,
    retryable: mapped.retryable,
    title: mapped.title,
  };
}
