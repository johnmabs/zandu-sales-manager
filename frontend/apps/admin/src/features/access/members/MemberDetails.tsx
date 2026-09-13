"use client";

import { ApiRequestError } from "@zandu/api-client";
import { Button, ErrorState, Skeleton } from "@zandu/ui";

import { MembershipStatusBadge } from "../components/AccessBadges";
import { RoleAssignmentSummary } from "../components/RoleAssignmentSummary";
import { expirationLabel } from "../expirationPresentation";

import type { MembershipResource, MembershipRoleAssignment } from "@zandu/api-client";

type MemberDetailsProperties = Readonly<{
  error?: unknown | undefined;
  isLoading: boolean;
  member?: MembershipResource | undefined;
  onRetry?: (() => void) | undefined;
  onAssignRole?: (() => void) | undefined;
  onRemoveRole?: ((assignment: MembershipRoleAssignment) => void) | undefined;
  onSuspend?: (() => void) | undefined;
  onReactivate?: (() => void) | undefined;
  onRevoke?: (() => void) | undefined;
}>;

export function MemberDetails({
  error,
  isLoading,
  member,
  onAssignRole,
  onRemoveRole,
  onSuspend,
  onReactivate,
  onRevoke,
  onRetry,
}: MemberDetailsProperties) {
  if (isLoading) {
    return <MemberDetailsSkeleton />;
  }

  if (error !== null && error !== undefined) {
    return <MemberDetailsError error={error} {...(onRetry === undefined ? {} : { onRetry })} />;
  }

  if (member === undefined) {
    return (
      <ErrorState
        description="Ce membre est introuvable ou n’est pas accessible dans l’organisation active."
        title="Membre introuvable"
      />
    );
  }

  return (
    <div className="zandu-member-details">
      <header className="zandu-member-details__header">
        <h1>Membre</h1>
        <p>Identifiant utilisateur : {member.userId}</p>
        <p>
          Statut : <MembershipStatusBadge status={member.status} />
        </p>
        {member.status === "ACTIVE" && onSuspend !== undefined ? (
          <Button onClick={onSuspend} type="button" variant="danger">
            Suspendre le membre
          </Button>
        ) : null}
        {member.status === "SUSPENDED" && onReactivate !== undefined ? (
          <Button onClick={onReactivate} type="button">
            Réactiver le membre
          </Button>
        ) : null}
        {member.status !== "REVOKED" && onRevoke !== undefined ? (
          <Button onClick={onRevoke} type="button" variant="danger">
            Révoquer le membre
          </Button>
        ) : null}
      </header>
      <section aria-labelledby="member-roles-title" className="zandu-member-details__section">
        <h2 id="member-roles-title">Rôles et portées</h2>
        {onAssignRole === undefined ? null : (
          <Button onClick={onAssignRole} type="button">
            Attribuer un rôle
          </Button>
        )}
        {member.roleAssignments.length === 0 ? (
          <p>Aucun rôle attribué.</p>
        ) : (
          <ul>
            {member.roleAssignments.map((assignment) => (
              <RoleAssignmentDetails
                assignment={assignment}
                key={assignment.assignmentId}
                onRemove={onRemoveRole}
              />
            ))}
          </ul>
        )}
      </section>
    </div>
  );
}

export function MemberDetailsSkeleton() {
  return (
    <div aria-label="Chargement du membre" aria-busy="true" role="status">
      <Skeleton className="zandu-skeleton--title" />
      <Skeleton className="zandu-skeleton--text" />
      <Skeleton className="zandu-skeleton--section" />
    </div>
  );
}

function RoleAssignmentDetails({
  assignment,
  onRemove,
}: Readonly<{
  assignment: MembershipRoleAssignment;
  onRemove?: ((assignment: MembershipRoleAssignment) => void) | undefined;
}>) {
  const isOrganizationScope = assignment.scopeType === "ORGANIZATION";
  const isSelectedStoresScope = assignment.scopeType === "SELECTED_STORES";

  return (
    <li>
      <RoleAssignmentSummary assignment={assignment} />
      <p>Rôle : {assignment.roleId}</p>
      <p>Portée : {isOrganizationScope ? "Organisation entière" : assignment.scopeType}</p>
      {isSelectedStoresScope ? (
        <>
          <p>Magasins sélectionnés :</p>
          {assignment.storeIds.length === 0 ? (
            <p>Aucun magasin publié.</p>
          ) : (
            <ul>
              {assignment.storeIds.map((storeId) => (
                <li key={storeId}>{storeId}</li>
              ))}
            </ul>
          )}
        </>
      ) : null}
      <p>{expirationLabel(assignment.expiresAt)}</p>
      {onRemove === undefined ? null : (
        <Button onClick={() => onRemove(assignment)} type="button" variant="danger">
          Retirer ce rôle
        </Button>
      )}
    </li>
  );
}

function MemberDetailsError({
  error,
  onRetry,
}: Readonly<{
  error: unknown;
  onRetry?: (() => void) | undefined;
}>) {
  if (
    error instanceof ApiRequestError &&
    error.apiError.kind === "response" &&
    error.apiError.status === 404
  ) {
    return (
      <ErrorState
        description="Ce membre est introuvable ou n’est pas accessible dans l’organisation active."
        title="Membre introuvable"
      />
    );
  }

  return (
    <ErrorState
      action={
        onRetry === undefined ? undefined : (
          <Button onClick={onRetry} type="button" variant="secondary">
            Réessayer
          </Button>
        )
      }
      description="Le détail de ce membre n’a pas pu être chargé."
      title="Impossible de charger le membre"
    />
  );
}
