import Link from "next/link";

/**
 * The current OpenAPI contract exposes invitation creation and cancellation,
 * but no tenant-scoped invitation read operation. Without a returned target,
 * cancellation cannot be offered safely from the Admin UI.
 */
export function InvitationLifecycleUnavailable() {
  return (
    <section aria-labelledby="invitation-lifecycle-title">
      <h2 id="invitation-lifecycle-title">Suivi des invitations indisponible</h2>
      <p role="status">
        Le contrat API actuel ne permet pas de consulter les invitations de l’organisation.
      </p>
      <p>
        Vous pouvez créer une invitation. Son annulation sera proposée lorsqu’une API de lecture
        tenant-scoped fournira une invitation cible.
      </p>
      <Link href="/admin/access/invite">Inviter un membre</Link>
    </section>
  );
}
