export type AuthStatus =
  | "UNKNOWN"
  | "AUTHENTICATING"
  | "AUTHENTICATED"
  | "UNAUTHENTICATED"
  | "REFRESHING";

export type AuthCredentials = Readonly<{
  email: string;
  password: string;
}>;

export type AuthTokens = Readonly<{
  accessToken: string;
  refreshExpiresAt?: string;
}>;

export type AuthAccessScope =
  | Readonly<{ type: "ORGANIZATION" }>
  | Readonly<{ storeIds: readonly string[]; type: "SELECTED_STORES" }>;

export type AuthEffectiveAccess = Readonly<{
  accessibleStoreIds: readonly string[];
  authorizationVersion: number;
  organizationId: string;
  permissions: readonly string[];
  scope: AuthAccessScope;
}>;

export type AuthOrganization = Readonly<{
  defaultCurrency: string;
  defaultLocale: string;
  defaultTimeZone: string;
  id: string;
  name: string;
  status: "ACTIVE" | "SUSPENDED" | "CLOSURE_PENDING" | "CLOSED";
}>;

export type AuthActor = Readonly<{
  authorizationVersion: number;
  email?: string;
  effectiveAccess: AuthEffectiveAccess;
  id: string;
  organizationId: string;
  organizations: readonly AuthOrganization[];
  userId: string;
}>;

export type AuthSessionMetadata = Readonly<{
  authorizationVersion: number;
  refreshExpiresAt?: string;
  sessionId?: string;
}>;

export type AuthState = Readonly<{
  actor?: AuthActor;
  expiresAt?: string;
  session?: AuthSessionMetadata;
  status: AuthStatus;
}>;

/**
 * The transport owns HTTP and platform-specific credential exchange. The
 * The access token is retained in memory only. The transport owns the
 * HttpOnly refresh cookie, which is deliberately inaccessible to JavaScript.
 */
export interface AuthTransport {
  login(credentials: AuthCredentials): Promise<AuthTokens>;
  logout(): Promise<void>;
  refresh(): Promise<AuthTokens>;
  resolveActor(accessToken: string): Promise<AuthActor>;
}

type JwtMetadata = Readonly<{
  authorizationVersion?: number;
  expiresAt: string;
  sessionId?: string;
}>;

type AuthStateListener = (state: AuthState) => void;

export class AuthorizationVersionInvalidatedError extends Error {
  constructor() {
    super("The session authorization version is no longer current.");
    this.name = "AuthorizationVersionInvalidatedError";
  }
}

export class AuthenticationManager {
  private accessToken: string | undefined;
  private currentState: AuthState = { status: "UNKNOWN" };
  private readonly listeners = new Set<AuthStateListener>();
  private refreshInFlight: Promise<AuthState> | undefined;
  private refreshExpiresAt: string | undefined;
  private readonly transport: AuthTransport;

  constructor(transport: AuthTransport) {
    this.transport = transport;
  }

  getAccessToken(): string | undefined {
    return this.accessToken;
  }

  getState(): AuthState {
    return this.currentState;
  }

  subscribe(listener: AuthStateListener): () => void {
    this.listeners.add(listener);

    return () => {
      this.listeners.delete(listener);
    };
  }

  async login(credentials: AuthCredentials): Promise<AuthState> {
    this.setState({ status: "AUTHENTICATING" });

    try {
      return await this.establish(await this.transport.login(credentials));
    } catch (error: unknown) {
      this.clearSession();
      throw error;
    }
  }

  /**
   * Restores a browser session by rotating the server-managed HttpOnly cookie
   * when no access token is currently held in memory.
   */
  async bootstrap(tokens?: AuthTokens): Promise<AuthState> {
    if (tokens !== undefined) {
      this.remember(tokens);
    }

    if (this.accessToken === undefined) {
      return this.refresh();
    }

    if (isExpired(readJwtMetadata(this.accessToken).expiresAt)) {
      return this.refresh();
    }

    try {
      return await this.establishCurrentSession();
    } catch (error: unknown) {
      this.clearSession();
      throw error;
    }
  }

  async refresh(): Promise<AuthState> {
    if (this.refreshInFlight !== undefined) {
      return this.refreshInFlight;
    }

    this.setState({ status: "REFRESHING" });
    this.refreshInFlight = this.rotate();

    try {
      return await this.refreshInFlight;
    } finally {
      this.refreshInFlight = undefined;
    }
  }

  async refreshAndRetry<Result>(retry: (accessToken: string) => Promise<Result>): Promise<Result> {
    const state = await this.refresh();
    const accessToken = this.accessToken;

    if (state.status !== "AUTHENTICATED" || accessToken === undefined) {
      throw new AuthenticationRequiredError();
    }

    return retry(accessToken);
  }

  async logout(): Promise<void> {
    try {
      await this.transport.logout();
    } finally {
      this.clearSession();
    }
  }

  private async rotate(): Promise<AuthState> {
    try {
      return await this.establish(await this.transport.refresh());
    } catch (error: unknown) {
      this.clearSession();
      throw error;
    }
  }

  private async establish(tokens: AuthTokens): Promise<AuthState> {
    this.remember(tokens);
    return this.establishCurrentSession();
  }

  private async establishCurrentSession(): Promise<AuthState> {
    const accessToken = this.accessToken;
    if (accessToken === undefined) {
      throw new AuthenticationRequiredError();
    }

    const metadata = readJwtMetadata(accessToken);
    if (isExpired(metadata.expiresAt)) {
      throw new AuthenticationRequiredError();
    }

    const actor = await this.transport.resolveActor(accessToken);
    if (
      metadata.authorizationVersion !== undefined &&
      actor.authorizationVersion !== metadata.authorizationVersion
    ) {
      throw new AuthorizationVersionInvalidatedError();
    }

    this.setState({
      actor,
      expiresAt: metadata.expiresAt,
      session: {
        authorizationVersion: actor.authorizationVersion,
        ...(this.refreshExpiresAt === undefined ? {} : { refreshExpiresAt: this.refreshExpiresAt }),
        ...(metadata.sessionId === undefined ? {} : { sessionId: metadata.sessionId }),
      },
      status: "AUTHENTICATED",
    });

    return this.currentState;
  }

  private remember(tokens: AuthTokens): void {
    this.accessToken = tokens.accessToken;
    this.refreshExpiresAt = tokens.refreshExpiresAt;
  }

  private clearSession(): void {
    this.accessToken = undefined;
    this.refreshExpiresAt = undefined;
    this.setState({ status: "UNAUTHENTICATED" });
  }

  private setState(state: AuthState): void {
    this.currentState = state;
    for (const listener of this.listeners) {
      listener(state);
    }
  }
}

export class AuthenticationRequiredError extends Error {
  constructor() {
    super("Authentication is required.");
    this.name = "AuthenticationRequiredError";
  }
}

function readJwtMetadata(accessToken: string): JwtMetadata {
  const payload = accessToken.split(".")[1];
  if (payload === undefined) {
    throw new AuthenticationRequiredError();
  }

  try {
    const parsed: unknown = JSON.parse(decodeBase64Url(payload));
    if (!isRecord(parsed) || typeof parsed.exp !== "number") {
      throw new AuthenticationRequiredError();
    }

    return {
      ...(typeof parsed.authorizationVersion === "number"
        ? { authorizationVersion: parsed.authorizationVersion }
        : {}),
      expiresAt: new Date(parsed.exp * 1000).toISOString(),
      ...(typeof parsed.sessionId === "string" ? { sessionId: parsed.sessionId } : {}),
    };
  } catch (error: unknown) {
    if (error instanceof AuthenticationRequiredError) {
      throw error;
    }

    throw new AuthenticationRequiredError();
  }
}

function decodeBase64Url(value: string): string {
  const padded = value
    .replace(/-/g, "+")
    .replace(/_/g, "/")
    .padEnd(Math.ceil(value.length / 4) * 4, "=");
  return atob(padded);
}

function isExpired(expiresAt: string): boolean {
  return Date.parse(expiresAt) <= Date.now();
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null;
}
