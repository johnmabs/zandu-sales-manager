import { NotificationViewport } from "@zandu/notifications/react";
import { Badge } from "@zandu/ui";

import {
  cashSessionStatusDisplay,
  connectivityStatusDisplay,
  syncStatusDisplay,
} from "./operational-status";

import type { CashSessionStatus, ConnectivityStatus, SyncStatus } from "./operational-status";
import type { AccessibleStore } from "@zandu/store-context";
import type { ReactNode } from "react";

export type PosOperationalContext = Readonly<{
  cashRegisterLabel: string;
  cashierLabel: string;
  cashSessionStatus: CashSessionStatus;
  connectivityStatus: ConnectivityStatus;
  store?: AccessibleStore;
  syncStatus: SyncStatus;
}>;

export function PosShell({
  children,
  operationalContext,
}: Readonly<{
  children: ReactNode;
  operationalContext: PosOperationalContext;
}>) {
  const cashSession = cashSessionStatusDisplay(operationalContext.cashSessionStatus);
  const connectivity = connectivityStatusDisplay(operationalContext.connectivityStatus);
  const sync = syncStatusDisplay(operationalContext.syncStatus);

  return (
    <div className="zandu-pos-shell">
      <header aria-label="État opérationnel du point de vente" className="zandu-pos-shell__status">
        <dl>
          <div>
            <dt>Magasin</dt>
            <dd>{operationalContext.store?.name ?? "Aucun magasin sélectionné"}</dd>
          </div>
          <div>
            <dt>Caisse</dt>
            <dd>{operationalContext.cashRegisterLabel}</dd>
          </div>
          <div>
            <dt>Caissier</dt>
            <dd>{operationalContext.cashierLabel}</dd>
          </div>
          <div>
            <dt>Session caisse</dt>
            <dd>
              <Badge tone={cashSession.tone}>{cashSession.label}</Badge>
            </dd>
          </div>
          <div>
            <dt>Connectivité</dt>
            <dd>
              <Badge tone={connectivity.tone}>{connectivity.label}</Badge>
            </dd>
          </div>
          <div>
            <dt>Synchronisation</dt>
            <dd>
              <Badge tone={sync.tone}>{sync.label}</Badge>
            </dd>
          </div>
        </dl>
      </header>
      <main className="zandu-pos-shell__content">{children}</main>
      <NotificationViewport />
    </div>
  );
}
