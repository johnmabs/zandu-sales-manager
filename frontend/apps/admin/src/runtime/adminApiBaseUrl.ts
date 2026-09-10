export function resolveAdminApiBaseUrl(
  configuredBaseUrl: string | undefined,
  browserOrigin: string | undefined,
): string | undefined {
  if (configuredBaseUrl === undefined || !configuredBaseUrl.trim().startsWith("/")) {
    return configuredBaseUrl;
  }

  if (configuredBaseUrl.trim().startsWith("//") || browserOrigin === undefined) {
    return configuredBaseUrl;
  }

  return new URL(configuredBaseUrl.trim(), browserOrigin).toString();
}
