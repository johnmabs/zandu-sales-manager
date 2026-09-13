import { ApiRequestError } from "@zandu/api-client";
import { ErrorMapper } from "@zandu/error-contract";
import { Alert } from "@zandu/ui";

const mapper = new ErrorMapper();

export function accessErrorPresentation(error: unknown) {
  const apiError =
    error instanceof ApiRequestError ? error.apiError : { kind: "response" as const, status: 500 };
  return mapper.map(apiError);
}

export function AccessMutationError({ error }: Readonly<{ error: unknown }>) {
  const presentation = accessErrorPresentation(error);
  return (
    <Alert tone="danger">
      <strong>{presentation.title}</strong>
      <p>{presentation.message}</p>
      {presentation.correlationId === undefined ? null : (
        <p>Référence de diagnostic : {presentation.correlationId}</p>
      )}
    </Alert>
  );
}
