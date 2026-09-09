export const IDEMPOTENCY_KEY_HEADER = "Idempotency-Key";
const MAX_IDEMPOTENCY_KEY_BYTES = 255;

export type CriticalMutationStatus =
  | "idle"
  | "submitting"
  | "unknown outcome"
  | "success"
  | "business failure"
  | "technical failure";

export type CriticalMutationState = Readonly<{
  intentId: string;
  key?: string;
  status: CriticalMutationStatus;
}>;

export type IdempotencyKeyGenerator = () => string;

export class IdempotencyError extends Error {
  constructor(message: string) {
    super(message);
    this.name = "IdempotencyError";
  }
}

/**
 * Retains a generated key for one explicit user intention. It is deliberately
 * in-memory: persistent POS operation handling is outside this Foundation.
 */
export class CriticalMutationManager {
  private readonly attempts = new Map<string, CriticalMutationState>();
  private readonly generateKey: IdempotencyKeyGenerator;

  constructor(generateKey: IdempotencyKeyGenerator = generateIdempotencyKey) {
    this.generateKey = generateKey;
  }

  begin(intentId: string): CriticalMutationState {
    const existing = this.attempts.get(intentId);
    if (existing !== undefined) {
      if (existing.status === "unknown outcome") {
        const retry = { ...existing, status: "submitting" } as const;
        this.attempts.set(intentId, retry);

        return retry;
      }

      throw new IdempotencyError(
        "A new intention is required after a known mutation outcome or while it is submitting.",
      );
    }

    const key = this.generateKey();
    assertIdempotencyKey(key);
    const attempt = { intentId, key, status: "submitting" } as const;
    this.attempts.set(intentId, attempt);

    return attempt;
  }

  completeBusinessFailure(intentId: string): CriticalMutationState {
    return this.transition(intentId, "business failure");
  }

  completeSuccess(intentId: string): CriticalMutationState {
    return this.transition(intentId, "success");
  }

  completeTechnicalFailure(intentId: string): CriticalMutationState {
    return this.transition(intentId, "technical failure");
  }

  markUnknownOutcome(intentId: string): CriticalMutationState {
    return this.transition(intentId, "unknown outcome");
  }

  state(intentId: string): CriticalMutationState {
    return this.attempts.get(intentId) ?? { intentId, status: "idle" };
  }

  retire(intentId: string): void {
    this.attempts.delete(intentId);
  }

  private transition(intentId: string, status: Exclude<CriticalMutationStatus, "idle">) {
    const attempt = this.attempts.get(intentId);
    if (attempt === undefined || attempt.key === undefined) {
      throw new IdempotencyError("No critical mutation exists for this intention.");
    }

    if (attempt.status !== "submitting") {
      throw new IdempotencyError("Only a submitting mutation can receive an outcome.");
    }

    const next: CriticalMutationState = {
      intentId: attempt.intentId,
      key: attempt.key,
      status,
    };
    this.attempts.set(intentId, next);

    return next;
  }
}

export function generateIdempotencyKey(): string {
  if (typeof globalThis.crypto?.randomUUID !== "function") {
    throw new IdempotencyError("The runtime cannot generate an idempotency key.");
  }

  return globalThis.crypto.randomUUID();
}

export function idempotencyHeaders(
  key: string,
): Readonly<Record<typeof IDEMPOTENCY_KEY_HEADER, string>> {
  assertIdempotencyKey(key);

  return { [IDEMPOTENCY_KEY_HEADER]: key };
}

export function assertIdempotencyKey(key: string): void {
  if (key.trim().length === 0) {
    throw new IdempotencyError("An idempotency key must not be empty.");
  }

  if (new TextEncoder().encode(key).byteLength > MAX_IDEMPOTENCY_KEY_BYTES) {
    throw new IdempotencyError("An idempotency key must not exceed 255 bytes.");
  }
}
