export type PermissionPresentation = Readonly<{
  description: string;
  label: string;
}>;

const presentations: Readonly<Record<string, PermissionPresentation>> = {
  ORGANIZATION_READ: {
    description: "Consulter l’organisation active.",
    label: "Consulter l’organisation",
  },
  ORGANIZATION_UPDATE: {
    description: "Modifier les informations de l’organisation.",
    label: "Modifier l’organisation",
  },
  ORGANIZATION_SUSPEND: {
    description: "Suspendre l’organisation.",
    label: "Suspendre l’organisation",
  },
  STORE_CREATE: { description: "Créer des magasins.", label: "Créer des magasins" },
  STORE_READ: { description: "Consulter les magasins.", label: "Consulter les magasins" },
  STORE_UPDATE: { description: "Modifier les magasins.", label: "Modifier les magasins" },
  STORE_SUSPEND: {
    description: "Suspendre ou réactiver les magasins.",
    label: "Gérer le statut des magasins",
  },
  STORE_CLOSE: {
    description: "Demander ou annuler la fermeture des magasins.",
    label: "Gérer la fermeture des magasins",
  },
  MEMBER_INVITE: {
    description: "Inviter ou annuler l’invitation de membres.",
    label: "Inviter des membres",
  },
  MEMBER_READ: {
    description: "Consulter les membres de l’organisation.",
    label: "Consulter les membres",
  },
  MEMBER_SUSPEND: {
    description: "Suspendre ou réactiver les memberships.",
    label: "Gérer le statut des membres",
  },
  MEMBER_REVOKE: { description: "Révoquer les memberships.", label: "Révoquer des membres" },
  ROLE_READ: { description: "Consulter le catalogue de rôles.", label: "Consulter les rôles" },
  ROLE_ASSIGN: { description: "Attribuer des rôles aux membres.", label: "Attribuer des rôles" },
  ROLE_REVOKE: { description: "Retirer des rôles aux membres.", label: "Retirer des rôles" },
  SECURITY_AUDIT_READ: {
    description: "Consulter le journal de sécurité.",
    label: "Consulter l’audit de sécurité",
  },
  CATALOG_READ: { description: "Consulter le catalogue.", label: "Consulter le catalogue" },
  UNIT_OF_MEASURE_CREATE: {
    description: "Créer des unités de mesure.",
    label: "Créer des unités de mesure",
  },
  UNIT_OF_MEASURE_UPDATE: {
    description: "Modifier des unités de mesure.",
    label: "Modifier des unités de mesure",
  },
  UNIT_OF_MEASURE_ACTIVATE: {
    description: "Activer des unités de mesure.",
    label: "Activer des unités de mesure",
  },
  UNIT_OF_MEASURE_DEACTIVATE: {
    description: "Désactiver des unités de mesure.",
    label: "Désactiver des unités de mesure",
  },
  CATEGORY_CREATE: { description: "Créer des catégories.", label: "Créer des catégories" },
  CATEGORY_UPDATE: { description: "Modifier des catégories.", label: "Modifier des catégories" },
  CATEGORY_ARCHIVE: { description: "Archiver des catégories.", label: "Archiver des catégories" },
  PRODUCT_CREATE: { description: "Créer des produits.", label: "Créer des produits" },
  PRODUCT_READ: { description: "Consulter les produits.", label: "Consulter les produits" },
  PRODUCT_UPDATE: { description: "Modifier des produits.", label: "Modifier des produits" },
  PRODUCT_ACTIVATE: { description: "Activer des produits.", label: "Activer des produits" },
  PRODUCT_DEACTIVATE: { description: "Désactiver des produits.", label: "Désactiver des produits" },
  PRODUCT_ARCHIVE: { description: "Archiver des produits.", label: "Archiver des produits" },
  SUPPLIER_CREATE: { description: "Créer des fournisseurs.", label: "Créer des fournisseurs" },
  SUPPLIER_READ: {
    description: "Consulter les fournisseurs.",
    label: "Consulter les fournisseurs",
  },
  SUPPLIER_UPDATE: {
    description: "Modifier des fournisseurs.",
    label: "Modifier des fournisseurs",
  },
  SUPPLIER_ARCHIVE: {
    description: "Archiver des fournisseurs.",
    label: "Archiver des fournisseurs",
  },
  PURCHASE_ORDER_CREATE: {
    description: "Créer des commandes fournisseur.",
    label: "Créer des commandes fournisseur",
  },
  PURCHASE_ORDER_READ: {
    description: "Consulter les commandes fournisseur.",
    label: "Consulter les commandes fournisseur",
  },
  PURCHASE_ORDER_UPDATE_DRAFT: {
    description: "Modifier les brouillons de commande.",
    label: "Modifier les brouillons de commande",
  },
  PURCHASE_ORDER_CONFIRM: {
    description: "Confirmer les commandes fournisseur.",
    label: "Confirmer les commandes fournisseur",
  },
  PURCHASE_ORDER_CANCEL: {
    description: "Annuler les commandes fournisseur.",
    label: "Annuler les commandes fournisseur",
  },
  PURCHASE_ORDER_CLOSE: {
    description: "Clôturer les commandes fournisseur.",
    label: "Clôturer les commandes fournisseur",
  },
  GOODS_RECEIPT_CREATE: {
    description: "Créer des réceptions fournisseur.",
    label: "Créer des réceptions fournisseur",
  },
  GOODS_RECEIPT_READ: {
    description: "Consulter les réceptions fournisseur.",
    label: "Consulter les réceptions fournisseur",
  },
  GOODS_RECEIPT_POST: {
    description: "Publier les réceptions fournisseur.",
    label: "Publier les réceptions fournisseur",
  },
  GOODS_RECEIPT_CANCEL: {
    description: "Annuler les réceptions fournisseur.",
    label: "Annuler les réceptions fournisseur",
  },
  PURCHASING_OVER_RECEIPT: {
    description: "Autoriser les sur-réceptions.",
    label: "Autoriser les sur-réceptions",
  },
  PURCHASING_RECEIPT_CORRECT: {
    description: "Corriger les réceptions fournisseur.",
    label: "Corriger les réceptions fournisseur",
  },
  PURCHASE_RETURN_CREATE: {
    description: "Créer des retours fournisseur.",
    label: "Créer des retours fournisseur",
  },
  PURCHASE_RETURN_READ: {
    description: "Consulter les retours fournisseur.",
    label: "Consulter les retours fournisseur",
  },
  PURCHASE_RETURN_SHIP: {
    description: "Expédier les retours fournisseur.",
    label: "Expédier les retours fournisseur",
  },
  PURCHASE_RETURN_CANCEL: {
    description: "Annuler les retours fournisseur.",
    label: "Annuler les retours fournisseur",
  },
  PRICE_LIST_CREATE: {
    description: "Créer des listes de prix.",
    label: "Créer des listes de prix",
  },
  PRICE_LIST_READ: {
    description: "Consulter les listes de prix.",
    label: "Consulter les listes de prix",
  },
  PRICE_LIST_UPDATE: {
    description: "Modifier les listes de prix.",
    label: "Modifier les listes de prix",
  },
  PRICE_LIST_ACTIVATE: {
    description: "Activer les listes de prix.",
    label: "Activer les listes de prix",
  },
  PRICE_LIST_ARCHIVE: {
    description: "Archiver les listes de prix.",
    label: "Archiver les listes de prix",
  },
  PRODUCT_PRICE_CREATE: { description: "Créer des prix produit.", label: "Créer des prix produit" },
  PRODUCT_PRICE_READ: {
    description: "Consulter les prix produit.",
    label: "Consulter les prix produit",
  },
  PRODUCT_PRICE_UPDATE: {
    description: "Modifier les prix produit.",
    label: "Modifier les prix produit",
  },
  PRODUCT_PRICE_ARCHIVE: {
    description: "Archiver les prix produit.",
    label: "Archiver les prix produit",
  },
  INVENTORY_READ: { description: "Consulter le stock.", label: "Consulter le stock" },
  INVENTORY_INITIALIZE: { description: "Initialiser le stock.", label: "Initialiser le stock" },
  INVENTORY_ADJUST: { description: "Ajuster le stock.", label: "Ajuster le stock" },
  STOCK_TRANSFER_CREATE: {
    description: "Créer des transferts de stock.",
    label: "Créer des transferts de stock",
  },
  STOCK_TRANSFER_READ: {
    description: "Consulter les transferts de stock.",
    label: "Consulter les transferts de stock",
  },
  STOCK_TRANSFER_UPDATE: {
    description: "Modifier les transferts de stock.",
    label: "Modifier les transferts de stock",
  },
  STOCK_TRANSFER_CANCEL: {
    description: "Annuler les transferts de stock.",
    label: "Annuler les transferts de stock",
  },
  STOCK_TRANSFER_SHIP: {
    description: "Expédier les transferts de stock.",
    label: "Expédier les transferts de stock",
  },
  STOCK_TRANSFER_RECEIVE: {
    description: "Réceptionner les transferts de stock.",
    label: "Réceptionner les transferts de stock",
  },
  STOCK_COUNT_CREATE: {
    description: "Créer des inventaires physiques.",
    label: "Créer des inventaires physiques",
  },
  STOCK_COUNT_READ: {
    description: "Consulter les inventaires physiques.",
    label: "Consulter les inventaires physiques",
  },
  STOCK_COUNT_START: {
    description: "Ouvrir les inventaires physiques.",
    label: "Ouvrir les inventaires physiques",
  },
  STOCK_COUNT_RECORD: { description: "Saisir les comptages.", label: "Saisir les comptages" },
  STOCK_COUNT_FINALIZE: {
    description: "Finaliser les inventaires physiques.",
    label: "Finaliser les inventaires physiques",
  },
  STOCK_COUNT_CANCEL: {
    description: "Annuler les inventaires physiques.",
    label: "Annuler les inventaires physiques",
  },
  STOCK_MOVEMENT_READ: {
    description: "Consulter les mouvements de stock.",
    label: "Consulter les mouvements de stock",
  },
  INVENTORY_COSTING_INITIALIZE: {
    description: "Initialiser la valorisation du stock.",
    label: "Initialiser la valorisation du stock",
  },
  INVENTORY_COST_ASSIGN: {
    description: "Attribuer les coûts de stock.",
    label: "Attribuer les coûts de stock",
  },
  CASH_REGISTER_CREATE: { description: "Créer des caisses.", label: "Créer des caisses" },
  CASH_REGISTER_READ: { description: "Consulter les caisses.", label: "Consulter les caisses" },
  CASH_REGISTER_UPDATE: { description: "Modifier les caisses.", label: "Modifier les caisses" },
  CASH_REGISTER_MANAGE: { description: "Gérer les caisses.", label: "Gérer les caisses" },
  CASH_SESSION_OPEN: {
    description: "Ouvrir les sessions de caisse.",
    label: "Ouvrir les sessions de caisse",
  },
  CASH_SESSION_READ: {
    description: "Consulter les sessions de caisse.",
    label: "Consulter les sessions de caisse",
  },
  CASH_SESSION_CLOSE: {
    description: "Clôturer les sessions de caisse.",
    label: "Clôturer les sessions de caisse",
  },
  CASH_MOVEMENT_READ: {
    description: "Consulter les mouvements de caisse.",
    label: "Consulter les mouvements de caisse",
  },
  CASH_IN_RECORD: {
    description: "Enregistrer les entrées de caisse.",
    label: "Enregistrer les entrées de caisse",
  },
  CASH_OUT_RECORD: {
    description: "Enregistrer les sorties de caisse.",
    label: "Enregistrer les sorties de caisse",
  },
  CASH_WITHDRAWAL_RECORD: {
    description: "Enregistrer les retraits de caisse.",
    label: "Enregistrer les retraits de caisse",
  },
  SALE_CREATE: { description: "Créer des ventes.", label: "Créer des ventes" },
  SALE_READ: { description: "Consulter les ventes.", label: "Consulter les ventes" },
  SALE_UPDATE_DRAFT: {
    description: "Modifier les brouillons de vente.",
    label: "Modifier les brouillons de vente",
  },
  SALE_CANCEL_DRAFT: {
    description: "Annuler les brouillons de vente.",
    label: "Annuler les brouillons de vente",
  },
  SALE_COMPLETE: { description: "Finaliser les ventes.", label: "Finaliser les ventes" },
  SALE_PRICE_OVERRIDE: {
    description: "Modifier les prix lors des ventes.",
    label: "Modifier les prix de vente",
  },
  PAYMENT_REFUND_CREATE: {
    description: "Créer des remboursements.",
    label: "Créer des remboursements",
  },
  PAYMENT_REFUND_READ: {
    description: "Consulter les remboursements.",
    label: "Consulter les remboursements",
  },
  SALE_RETURN_CREATE: {
    description: "Créer des retours de vente.",
    label: "Créer des retours de vente",
  },
  SALE_RETURN_READ: {
    description: "Consulter les retours de vente.",
    label: "Consulter les retours de vente",
  },
  SALE_RETURN_COMPLETE: {
    description: "Finaliser les retours de vente.",
    label: "Finaliser les retours de vente",
  },
  SALE_RETURN_CANCEL: {
    description: "Annuler les retours de vente.",
    label: "Annuler les retours de vente",
  },
};

/** Presentation-only mapping. Authorization decisions always use server-provided permission codes. */
export function permissionPresentation(permissionCode: string): PermissionPresentation {
  return (
    presentations[permissionCode] ?? {
      description: `La permission publiée « ${permissionCode} » n’est pas encore décrite dans l’Admin.`,
      label: `Permission inconnue : ${permissionCode}`,
    }
  );
}
