export type AppEnvironment = "development" | "production" | "test";

export type PublicRuntimeConfig = Readonly<{
  apiBaseUrl: string;
  appEnvironment: AppEnvironment;
}>;

export class PublicConfigurationError extends Error {
  constructor(message: string) {
    super(message);
    this.name = "PublicConfigurationError";
  }
}

/** Validates only the explicitly public variables provided by an application runtime adapter. */
export function publicRuntimeConfig(
  environment: Readonly<Record<"API_BASE_URL" | "APP_ENV", string | undefined>>,
): PublicRuntimeConfig {
  const apiBaseUrl = environment.API_BASE_URL;
  if (apiBaseUrl === undefined || apiBaseUrl.trim().length === 0) {
    throw new PublicConfigurationError("API_BASE_URL is required.");
  }

  const parsedUrl = parseApiBaseUrl(apiBaseUrl);
  const appEnvironment = environment.APP_ENV;
  if (
    appEnvironment !== "development" &&
    appEnvironment !== "production" &&
    appEnvironment !== "test"
  ) {
    throw new PublicConfigurationError("APP_ENV must be development, production, or test.");
  }

  return { apiBaseUrl: parsedUrl, appEnvironment };
}

function parseApiBaseUrl(value: string): string {
  let url: URL;
  try {
    url = new URL(value);
  } catch {
    throw new PublicConfigurationError("API_BASE_URL must be an absolute URL.");
  }

  if (url.protocol !== "http:" && url.protocol !== "https:") {
    throw new PublicConfigurationError("API_BASE_URL must use HTTP or HTTPS.");
  }

  if (url.username.length > 0 || url.password.length > 0) {
    throw new PublicConfigurationError("API_BASE_URL must not include credentials.");
  }

  return url.toString();
}
