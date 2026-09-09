import { idempotencyHeaders } from "@zandu/idempotency";

import type { PublicRuntimeConfig } from "@zandu/config";
import type { ApiError, FieldErrors } from "@zandu/error-contract";

export const CORRELATION_ID_HEADER = "X-Correlation-ID";

export type ApiRequest = Readonly<{
  body?: unknown;
  correlationId?: string;
  idempotencyKey?: string;
  method: "DELETE" | "GET" | "PATCH" | "POST" | "PUT";
  path: string;
  requiresAuthentication?: boolean;
  timeoutMs?: number;
}>;

export type ApiResponse = Readonly<{
  correlationId?: string;
  data: unknown;
  status: number;
}>;

export type FetchImplementation = (url: string, init: RequestInit) => Promise<Response>;

/** Structural port implemented by the existing in-memory AuthenticationManager. */
export type SessionCredentials = Readonly<{
  getAccessToken: () => string | undefined;
  refreshAndRetry: <Result>(retry: (accessToken: string) => Promise<Result>) => Promise<Result>;
}>;

export type ApiClientOptions = Readonly<{
  config: PublicRuntimeConfig;
  defaultTimeoutMs?: number;
  fetchImplementation?: FetchImplementation;
  generateCorrelationId?: () => string;
  session?: SessionCredentials;
}>;

export class ApiRequestError extends Error {
  readonly apiError: ApiError;
  readonly outcomeUnknown: boolean;

  constructor(apiError: ApiError, outcomeUnknown: boolean) {
    super(
      apiError.kind === "network"
        ? (apiError.message ?? "Network request failed.")
        : "API response failed.",
    );
    this.name = "ApiRequestError";
    this.apiError = apiError;
    this.outcomeUnknown = outcomeUnknown;
  }
}

export class ApiClient {
  private readonly config: PublicRuntimeConfig;
  private readonly defaultTimeoutMs: number;
  private readonly fetchImplementation: FetchImplementation;
  private readonly generateCorrelationId: () => string;
  private readonly session: SessionCredentials | undefined;

  constructor({
    config,
    defaultTimeoutMs = 15_000,
    fetchImplementation = fetch,
    generateCorrelationId = generateRequestCorrelationId,
    session,
  }: ApiClientOptions) {
    if (defaultTimeoutMs <= 0) {
      throw new Error("The default timeout must be positive.");
    }

    this.config = config;
    this.defaultTimeoutMs = defaultTimeoutMs;
    this.fetchImplementation = fetchImplementation;
    this.generateCorrelationId = generateCorrelationId;
    this.session = session;
  }

  async request(request: ApiRequest): Promise<ApiResponse> {
    const accessToken =
      request.requiresAuthentication === false ? undefined : this.session?.getAccessToken();

    try {
      return await this.execute(request, accessToken);
    } catch (error: unknown) {
      if (
        !isUnauthorizedResponse(error) ||
        request.requiresAuthentication === false ||
        this.session === undefined
      ) {
        throw error;
      }

      return this.session.refreshAndRetry((refreshedAccessToken) =>
        this.execute(request, refreshedAccessToken),
      );
    }
  }

  private async execute(
    request: ApiRequest,
    accessToken: string | undefined,
  ): Promise<ApiResponse> {
    const correlationId = request.correlationId ?? this.generateCorrelationId();
    const controller = new AbortController();
    const timeoutMs = request.timeoutMs ?? this.defaultTimeoutMs;
    const timeout = setTimeout(() => controller.abort(), timeoutMs);

    try {
      const response = await this.fetchImplementation(this.urlFor(request.path), {
        ...(request.body === undefined ? {} : { body: JSON.stringify(request.body) }),
        credentials: "include",
        headers: {
          Accept: "application/json",
          ...(request.body === undefined ? {} : { "Content-Type": "application/json" }),
          ...(accessToken === undefined ? {} : { Authorization: `Bearer ${accessToken}` }),
          [CORRELATION_ID_HEADER]: correlationId,
          ...(request.idempotencyKey === undefined
            ? {}
            : idempotencyHeaders(request.idempotencyKey)),
        },
        method: request.method,
        signal: controller.signal,
      });

      const responseCorrelationId = response.headers.get(CORRELATION_ID_HEADER) ?? undefined;
      if (!response.ok) {
        throw new ApiRequestError(await responseError(response, responseCorrelationId), false);
      }

      return {
        ...(responseCorrelationId === undefined ? {} : { correlationId: responseCorrelationId }),
        data: await response.json(),
        status: response.status,
      };
    } catch (error: unknown) {
      if (error instanceof ApiRequestError) {
        throw error;
      }

      const timedOut = controller.signal.aborted;
      throw new ApiRequestError(
        { ...(timedOut ? { message: "The request timed out." } : {}), kind: "network" },
        timedOut && request.idempotencyKey !== undefined,
      );
    } finally {
      clearTimeout(timeout);
    }
  }

  private urlFor(path: string): string {
    return new URL(path, this.config.apiBaseUrl).toString();
  }
}

function generateRequestCorrelationId(): string {
  if (typeof globalThis.crypto?.randomUUID !== "function") {
    throw new Error("The runtime cannot generate a correlation id.");
  }

  return globalThis.crypto.randomUUID();
}

async function responseError(
  response: Response,
  correlationId: string | undefined,
): Promise<ApiError> {
  const payload = await response.json().catch((): undefined => undefined);
  const decoded = isErrorPayload(payload) ? payload : undefined;
  const effectiveCorrelationId = decoded?.correlationId ?? correlationId;

  return {
    ...(decoded?.code === undefined ? {} : { code: decoded.code }),
    ...(effectiveCorrelationId === undefined ? {} : { correlationId: effectiveCorrelationId }),
    ...(decoded?.fieldErrors === undefined ? {} : { fieldErrors: decoded.fieldErrors }),
    kind: "response",
    ...(decoded?.message === undefined ? {} : { message: decoded.message }),
    status: response.status,
  };
}

function isErrorPayload(value: unknown): value is Readonly<{
  code?: string;
  correlationId?: string;
  fieldErrors?: FieldErrors;
  message?: string;
}> {
  if (!isRecord(value)) {
    return false;
  }

  const payload = value;
  return (
    (payload.code === undefined || typeof payload.code === "string") &&
    (payload.correlationId === undefined || typeof payload.correlationId === "string") &&
    (payload.message === undefined || typeof payload.message === "string") &&
    (payload.fieldErrors === undefined || isFieldErrors(payload.fieldErrors))
  );
}

function isFieldErrors(value: unknown): value is FieldErrors {
  if (!isRecord(value)) {
    return false;
  }

  return Object.values(value).every(
    (messages) =>
      Array.isArray(messages) && messages.every((message) => typeof message === "string"),
  );
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null;
}

function isUnauthorizedResponse(error: unknown): error is ApiRequestError {
  return (
    error instanceof ApiRequestError &&
    error.apiError.kind === "response" &&
    error.apiError.status === 401
  );
}
