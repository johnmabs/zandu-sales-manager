import { PosShell } from "../features/terminal/PosShell";

const unresolvedOperationalContext = {
  cashRegisterLabel: "Aucune caisse sélectionnée",
  cashierLabel: "Session non résolue",
  cashSessionStatus: "UNKNOWN",
  connectivityStatus: "UNKNOWN",
  syncStatus: "UNKNOWN",
} as const;

export function App() {
  return (
    <PosShell operationalContext={unresolvedOperationalContext}>
      <section className="zandu-pos-shell__placeholder" aria-labelledby="pos-title">
        <h1 id="pos-title">Zandu POS</h1>
        <p>La vente sera disponible après la résolution du contexte opérationnel.</p>
      </section>
    </PosShell>
  );
}
