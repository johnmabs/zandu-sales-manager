"use client";
import { Button, Dialog } from "@zandu/ui";
import { useId } from "react";
export function SuspendMemberDialog({
  isPending,
  memberLabel,
  onClose,
  onConfirm,
  open,
}: Readonly<{
  isPending: boolean;
  memberLabel: string;
  onClose: () => void;
  onConfirm: () => Promise<void>;
  open: boolean;
}>) {
  const descriptionId = useId();
  return (
    <Dialog descriptionId={descriptionId} onClose={onClose} open={open} title="Suspendre le membre">
      <p id={descriptionId}>
        Suspendre {memberLabel} bloquera immédiatement son accès à l’organisation. Cette action
        reste réversible.
      </p>
      <Button disabled={isPending} onClick={onClose} type="button" variant="secondary">
        Annuler
      </Button>
      <Button disabled={isPending} onClick={() => void onConfirm()} type="button" variant="danger">
        {isPending ? "Suspension en cours" : "Confirmer la suspension"}
      </Button>
    </Dialog>
  );
}
