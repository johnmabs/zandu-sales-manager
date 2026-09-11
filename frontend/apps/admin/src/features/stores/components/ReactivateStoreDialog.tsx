"use client";

import { Button, Dialog } from "@zandu/ui";
import { useId } from "react";

type Properties = Readonly<{
  isReactivating: boolean;
  onClose: () => void;
  onConfirm: () => void;
  open: boolean;
  storeName: string;
}>;

export function ReactivateStoreDialog({
  isReactivating,
  onClose,
  onConfirm,
  open,
  storeName,
}: Properties) {
  const descriptionId = useId();

  return (
    <Dialog
      closeLabel="Fermer le dialogue"
      descriptionId={descriptionId}
      onClose={isReactivating ? () => undefined : onClose}
      open={open}
      title="Réactiver le magasin"
    >
      <p id={descriptionId}>Réactiver {storeName} autorisera à nouveau ses opérations.</p>
      <Button disabled={isReactivating} onClick={onClose} type="button">
        Annuler
      </Button>
      <Button disabled={isReactivating} onClick={onConfirm} type="button">
        {isReactivating ? "Réactivation en cours" : "Confirmer la réactivation"}
      </Button>
    </Dialog>
  );
}
