"use client";

import { ApiRequestError } from "@zandu/api-client";
import { Button, ErrorState, Skeleton } from "@zandu/ui";

import type { MembershipResource, MembershipRoleAssignment } from "@zandu/api-client";

type MemberDetailsProperties = Readonly<{
  error?: unknown | undefined;
  isLoading: boolean;
  member?: MembershipResource | undefined;
  onRetry?: (() => void) | undefined;
}>;

export function MemberDetails({ error, isLoading, member, onRetry }: MemberDetailsProperties) {
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
        <p>Statut : {member.status}</p>
      </header>
      <section aria-labelledby="member-roles-title" className="zandu-member-details__section">
        <h2 id="member-roles-title">Rôles et portées</h2>
        {member.roleAssignments.length === 0 ? (
          <p>Aucun rôle attribué.</p>
        ) : (
          <ul>
            {member.roleAssignments.map((assignment) => (
              <RoleAssignmentDetails assignment={assignment} key={assignment.assignmentId} />
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

function RoleAssignmentDetails({ assignment }: Readonly<{ assignment: MembershipRoleAssignment }>) {
  const isOrganizationScope = assignment.scopeType === "ORGANIZATION";
  const isSelectedStoresScope = assignment.scopeType === "SELECTED_STORES";

  return (
    <li>
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
      <p>Expiration : {assignment.expiresAt ?? "Sans expiration"}</p>
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
