"use client";

import { Button, Dialog } from "@zandu/ui";
import { useId } from "react";

import { expirationLabel } from "../expirationPresentation";

import type { MembershipRoleAssignment } from "@zandu/api-client";

export function RemoveRoleDialog({
  assignment,
  isRemoving,
  onClose,
  onConfirm,
  open,
}: Readonly<{
  assignment?: MembershipRoleAssignment;
  isRemoving: boolean;
  onClose: () => void;
  onConfirm: () => Promise<void>;
  open: boolean;
}>) {
  const descriptionId = useId();
  if (assignment === undefined) return null;
  const scope =
    assignment.scopeType === "ORGANIZATION"
      ? "toute l’organisation"
      : `${assignment.storeIds.length} magasin(s) (${assignment.storeIds.join(", ")})`;
  return (
    <Dialog descriptionId={descriptionId} onClose={onClose} open={open} title="Retirer le rôle">
      <p id={descriptionId}>
        Retirer le rôle {assignment.roleId} supprimera son accès sur {scope}.{" "}
        {expirationLabel(assignment.expiresAt)}.
      </p>
      <Button disabled={isRemoving} onClick={onClose} type="button" variant="secondary">
        Conserver le rôle
      </Button>
      <Button disabled={isRemoving} onClick={() => void onConfirm()} type="button" variant="danger">
        {isRemoving ? "Retrait en cours" : "Confirmer le retrait"}
      </Button>
    </Dialog>
  );
}
