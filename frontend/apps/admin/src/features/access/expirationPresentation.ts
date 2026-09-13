export function expirationLabel(expiresAt: string | null, timeZone = "UTC"): string {
  if (expiresAt === null) return "Sans expiration";
  const instant = new Date(expiresAt);
  if (Number.isNaN(instant.getTime())) return `Expiration publiée : ${expiresAt}`;
  return `Expire le ${new Intl.DateTimeFormat("fr-FR", {
    dateStyle: "medium",
    timeStyle: "short",
    timeZone,
  }).format(instant)}`;
}
