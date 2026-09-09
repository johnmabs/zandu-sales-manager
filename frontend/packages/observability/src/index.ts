export type ClientTelemetryMetadata = Readonly<{
  clientVersion: string;
  environment: "development" | "production" | "test";
}>;

type TelemetryEventBase = ClientTelemetryMetadata &
  Readonly<{
    occurredAt: string;
  }>;

export type TechnicalErrorEvent = TelemetryEventBase &
  Readonly<{
    errorType: string;
    kind: "technical_error";
    source: "route" | "unknown" | "window";
  }>;

export type RouteLoadEvent = TelemetryEventBase &
  Readonly<{
    durationMs: number;
    kind: "route_load";
    route: string;
  }>;

export type ApiFailureEvent = TelemetryEventBase &
  Readonly<{
    correlationId?: string;
    failureKind: "network" | "response";
    kind: "api_failure";
    method: "DELETE" | "GET" | "PATCH" | "POST" | "PUT";
    outcomeUnknown: boolean;
    path: string;
    status?: number;
  }>;

export type FrontendTelemetryEvent = TechnicalErrorEvent | RouteLoadEvent | ApiFailureEvent;

type TechnicalErrorEventData = Readonly<{
  errorType: string;
  kind: "technical_error";
  source: "route" | "unknown" | "window";
}>;

type RouteLoadEventData = Readonly<{
  durationMs: number;
  kind: "route_load";
  route: string;
}>;

type ApiFailureEventData = Readonly<{
  correlationId?: string;
  failureKind: "network" | "response";
  kind: "api_failure";
  method: "DELETE" | "GET" | "PATCH" | "POST" | "PUT";
  outcomeUnknown: boolean;
  path: string;
  status?: number;
}>;

type TelemetryEventData = TechnicalErrorEventData | RouteLoadEventData | ApiFailureEventData;

/** Port to an OpenTelemetry exporter or another infrastructure adapter. */
export type FrontendTelemetrySink = Readonly<{
  emit: (event: FrontendTelemetryEvent) => void;
}>;

export type ApiFailureObservation = Omit<ApiFailureEventData, "kind">;

export type ApiFailureObserver = Readonly<{
  recordApiFailure: (observation: ApiFailureObservation) => void;
}>;

export type FrontendObservabilityOptions = Readonly<{
  metadata: ClientTelemetryMetadata;
  now?: () => number;
  sink: FrontendTelemetrySink;
}>;

/**
 * Produces a deliberately small, structured telemetry vocabulary.
 * It never accepts request bodies, headers, actors, or error messages.
 */
export class FrontendObservability implements ApiFailureObserver {
  private readonly metadata: ClientTelemetryMetadata;
  private readonly now: () => number;
  private readonly sink: FrontendTelemetrySink;

  constructor({ metadata, now = Date.now, sink }: FrontendObservabilityOptions) {
    if (metadata.clientVersion.trim().length === 0) {
      throw new Error("The client version must not be empty.");
    }

    this.metadata = metadata;
    this.now = now;
    this.sink = sink;
  }

  recordApiFailure(observation: ApiFailureObservation): void {
    this.emit({ ...observation, kind: "api_failure", path: sanitizeRoute(observation.path) });
  }

  recordTechnicalError(error: unknown, source: TechnicalErrorEvent["source"] = "unknown"): void {
    this.emit({ errorType: errorTypeFor(error), kind: "technical_error", source });
  }

  recordRouteLoad(route: string, durationMs: number): void {
    if (!Number.isFinite(durationMs) || durationMs < 0) {
      throw new Error("Route load duration must be a non-negative finite number.");
    }

    this.emit({ durationMs, kind: "route_load", route: sanitizeRoute(route) });
  }

  startRouteLoad(route: string): () => void {
    const startedAt = this.now();
    let completed = false;

    return () => {
      if (completed) {
        return;
      }
      completed = true;
      this.recordRouteLoad(route, this.now() - startedAt);
    };
  }

  private emit(event: TelemetryEventData): void {
    const annotatedEvent = {
      ...event,
      ...this.metadata,
      occurredAt: new Date(this.now()).toISOString(),
    };

    this.sink.emit(annotatedEvent);
  }
}

function errorTypeFor(error: unknown): string {
  return error instanceof Error && error.name.length > 0 ? error.name : "UnknownError";
}

function sanitizeRoute(route: string): string {
  const pathname = new URL(route, "https://zandu.invalid").pathname;

  return pathname
    .split("/")
    .map((segment) => {
      if (/^\d+$/.test(segment) || /^[0-9a-f]{8}-[0-9a-f-]{27,}$/i.test(segment)) {
        return ":id";
      }

      return segment;
    })
    .join("/");
}
