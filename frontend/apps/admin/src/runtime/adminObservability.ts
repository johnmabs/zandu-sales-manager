import { FrontendObservability } from "@zandu/observability";

import type { AppEnvironment } from "@zandu/config";
import type { FrontendTelemetryEvent, FrontendTelemetrySink } from "@zandu/observability";

export const ADMIN_TELEMETRY_EVENT = "zandu:telemetry";

/**
 * Keeps the runtime adapter vendor-neutral. A future OpenTelemetry browser
 * adapter can consume this event without receiving request bodies or headers.
 */
export function createAdminTelemetrySink(
  dispatch: ((event: Event) => boolean) | undefined = globalThis.dispatchEvent?.bind(globalThis),
): FrontendTelemetrySink {
  return {
    emit(event) {
      dispatch?.(new CustomEvent<FrontendTelemetryEvent>(ADMIN_TELEMETRY_EVENT, { detail: event }));
    },
  };
}

export function createAdminObservability(
  environment: AppEnvironment,
  clientVersion: string,
  sink: FrontendTelemetrySink = createAdminTelemetrySink(),
) {
  return new FrontendObservability({ metadata: { clientVersion, environment }, sink });
}
