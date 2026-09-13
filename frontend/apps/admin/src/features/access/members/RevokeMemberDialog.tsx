"use client";
import { Button, Dialog } from "@zandu/ui";
import { useId } from "react";
export function RevokeMemberDialog({
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
    <Dialog descriptionId={descriptionId} onClose={onClose} open={open} title="Révoquer le membre">
      <p id={descriptionId}>
        Révoquer {memberLabel} bloquera immédiatement son accès. Cette action est terminale, ne
        permet aucune réactivation et conserve l’historique.
      </p>
      <Button disabled={isPending} onClick={onClose} type="button" variant="secondary">
        Annuler
      </Button>
      <Button disabled={isPending} onClick={() => void onConfirm()} type="button" variant="danger">
        {isPending ? "Révocation en cours" : "Confirmer la révocation"}
      </Button>
    </Dialog>
  );
}
