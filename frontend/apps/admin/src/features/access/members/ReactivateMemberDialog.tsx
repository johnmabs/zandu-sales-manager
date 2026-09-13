"use client";
import { Button, Dialog } from "@zandu/ui";
import { useId } from "react";
export function ReactivateMemberDialog({
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
    <Dialog descriptionId={descriptionId} onClose={onClose} open={open} title="Réactiver le membre">
      <p id={descriptionId}>
        Réactiver {memberLabel} lui permettra de nouveau d’utiliser les accès encore attribués.
      </p>
      <Button disabled={isPending} onClick={onClose} type="button" variant="secondary">
        Annuler
      </Button>
      <Button disabled={isPending} onClick={() => void onConfirm()} type="button">
        {isPending ? "Réactivation en cours" : "Confirmer la réactivation"}
      </Button>
    </Dialog>
  );
}
