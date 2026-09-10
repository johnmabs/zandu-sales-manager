const blockerMessages: Readonly<Record<string, string>> = {
  DRAFT_GOODS_RECEIPT: "Une réception de marchandises est encore au brouillon.",
  OPEN_CASH_SESSION: "Une session de caisse est encore ouverte.",
  OPEN_GOODS_RECEIPT_CORRECTION: "Une correction de réception est encore ouverte.",
  OPEN_PURCHASE_ORDER: "Une commande fournisseur est encore ouverte.",
  OPEN_PURCHASE_RETURN: "Un retour fournisseur est encore ouvert.",
  OPEN_STOCK_COUNT: "Un inventaire est encore ouvert.",
};

export function storeClosureBlockerMessage(code: string): string {
  return (
    blockerMessages[code] ??
    `Un élément opérationnel doit être résolu avant la fermeture (code diagnostic : ${code}).`
  );
}

export function StoreClosureBlockers({ blockers }: Readonly<{ blockers: readonly string[] }>) {
  if (blockers.length === 0) {
    return <p>Aucun blocker n’a été signalé.</p>;
  }

  return (
    <section aria-labelledby="store-closure-blockers-title">
      <h3 id="store-closure-blockers-title">Éléments à résoudre</h3>
      <ul>
        {blockers.map((blocker, index) => (
          <li key={`${blocker}-${index}`}>{storeClosureBlockerMessage(blocker)}</li>
        ))}
      </ul>
    </section>
  );
}
