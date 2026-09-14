"use client";

import { Button, ErrorState } from "@zandu/ui";

export default function AdminRouteError({
  reset,
}: Readonly<{
  reset: () => void;
}>) {
  return (
    <main className="zandu-admin-session-state">
      <ErrorState
        action={
          <Button onClick={reset} type="button">
            Réessayer
          </Button>
        }
        description="Une erreur est survenue lors du chargement de cette section."
        title="Chargement impossible"
      />
    </main>
  );
}
