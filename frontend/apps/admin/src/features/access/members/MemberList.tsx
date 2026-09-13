"use client";

import { Button, EmptyState, ErrorState, Skeleton } from "@zandu/ui";
import Link from "next/link";

import { AdminTable } from "../../../components/tables/AdminTable";

import type { AdminTableColumn } from "../../../components/tables/AdminTable";
import type { MembershipResource, MembershipRoleAssignment } from "@zandu/api-client";

type MemberListProperties = Readonly<{
  error?: unknown | undefined;
  isLoading: boolean;
  members?: readonly MembershipResource[] | undefined;
  onRetry?: (() => void) | undefined;
}>;

const columns: readonly AdminTableColumn<MembershipResource>[] = [
  {
    cell: (member) => (
      <Link href={`/app/access/members/${encodeURIComponent(member.id)}`}>{member.userId}</Link>
    ),
    header: "Utilisateur",
    id: "userId",
  },
  { cell: (member) => member.status, header: "Statut", id: "status" },
  {
    cell: (member) => <RoleAssignmentsSummary assignments={member.roleAssignments} />,
    header: "Rôles et portées",
    id: "roleAssignments",
  },
];

const tableQuery = { filters: {}, page: 1 } as const;

export function MemberList({ error, isLoading, members, onRetry }: MemberListProperties) {
  if (isLoading) {
    return <MemberListSkeleton />;
  }

  if (error !== undefined) {
    return <MemberListError {...(onRetry === undefined ? {} : { onRetry })} />;
  }

  if (members === undefined || members.length === 0) {
    return (
      <EmptyState
        description="Les membres de cette organisation apparaîtront ici."
        title="Aucun membre"
      />
    );
  }

  return (
    <AdminTable
      columns={columns}
      emptyDescription="Les membres de cette organisation apparaîtront ici."
      emptyTitle="Aucun membre"
      getRowId={(member) => member.id}
      isLoading={false}
      page={{ items: members, page: 1, pageSize: members.length, totalItems: members.length }}
      query={tableQuery}
    />
  );
}

function RoleAssignmentsSummary({
  assignments,
}: Readonly<{
  assignments: readonly MembershipRoleAssignment[];
}>) {
  if (assignments.length === 0) {
    return "Aucun rôle attribué";
  }

  return (
    <ul className="zandu-member-role-assignments">
      {assignments.map((assignment) => (
        <li key={assignment.assignmentId}>
          {assignment.roleId} — {scopeSummary(assignment)}
        </li>
      ))}
    </ul>
  );
}

function scopeSummary(assignment: MembershipRoleAssignment): string {
  if (assignment.scopeType === "ORGANIZATION") {
    return "Organisation entière";
  }
  if (assignment.scopeType === "SELECTED_STORES") {
    return `${assignment.storeIds.length} magasin${assignment.storeIds.length === 1 ? "" : "s"}`;
  }

  return `Portée non reconnue (${assignment.scopeType})`;
}

function MemberListError({ onRetry }: Readonly<{ onRetry?: (() => void) | undefined }>) {
  return (
    <ErrorState
      action={
        onRetry === undefined ? undefined : (
          <Button onClick={onRetry} type="button" variant="secondary">
            Réessayer
          </Button>
        )
      }
      description="La liste des membres n’a pas pu être chargée."
      title="Impossible de charger les membres"
    />
  );
}

export function MemberListSkeleton() {
  return (
    <div aria-label="Chargement des membres" aria-busy="true" role="status">
      <Skeleton className="zandu-skeleton--title" />
      <Skeleton className="zandu-skeleton--table" />
    </div>
  );
}
