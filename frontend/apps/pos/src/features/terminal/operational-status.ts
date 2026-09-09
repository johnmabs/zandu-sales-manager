export type CashSessionStatus = "CLOSED" | "OPEN" | "UNKNOWN";
export type ConnectivityStatus = "OFFLINE" | "ONLINE" | "UNKNOWN";
export type SyncStatus = "PENDING" | "SYNCHRONIZED" | "UNKNOWN";

export type StatusDisplay = Readonly<{
  label: string;
  tone: "neutral" | "success" | "warning";
}>;

export function cashSessionStatusDisplay(status: CashSessionStatus): StatusDisplay {
  switch (status) {
    case "OPEN":
      return { label: "Ouverte", tone: "success" };
    case "CLOSED":
      return { label: "Fermée", tone: "neutral" };
    case "UNKNOWN":
      return { label: "Non résolue", tone: "warning" };
  }
}

export function connectivityStatusDisplay(status: ConnectivityStatus): StatusDisplay {
  switch (status) {
    case "ONLINE":
      return { label: "En ligne", tone: "success" };
    case "OFFLINE":
      return { label: "Hors ligne", tone: "warning" };
    case "UNKNOWN":
      return { label: "À venir", tone: "neutral" };
  }
}

export function syncStatusDisplay(status: SyncStatus): StatusDisplay {
  switch (status) {
    case "SYNCHRONIZED":
      return { label: "Synchronisé", tone: "success" };
    case "PENDING":
      return { label: "En attente", tone: "warning" };
    case "UNKNOWN":
      return { label: "À venir", tone: "neutral" };
  }
}
