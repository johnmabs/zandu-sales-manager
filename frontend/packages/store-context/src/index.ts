export type StoreStatus = "ACTIVE" | "SUSPENDED" | "CLOSURE_PENDING" | "CLOSED";

export type AccessibleStore = Readonly<{
  currency: string;
  id: string;
  locale: string;
  name: string;
  organizationId: string;
  status: StoreStatus;
  timeZone: string;
}>;

export type StoreContextStatus =
  | "UNKNOWN"
  | "NO_ACCESSIBLE_STORES"
  | "NO_SELECTABLE_STORES"
  | "SELECTION_REQUIRED"
  | "ACTIVE";

export type StoreContextState = Readonly<{
  activeStore?: AccessibleStore;
  organizationId: string;
  stores: readonly AccessibleStore[];
  status: StoreContextStatus;
}>;

type StoreContextListener = (state: StoreContextState) => void;

export class StoreSelectionError extends Error {
  constructor(message: string) {
    super(message);
    this.name = "StoreSelectionError";
  }
}

/**
 * Holds the store selected within one already-authorized organization. The
 * supplied list must come from the server's effective access scope; this
 * manager neither grants access nor persists a preference.
 */
export class StoreContextManager {
  private currentState: StoreContextState;
  private readonly listeners = new Set<StoreContextListener>();

  constructor(organizationId: string) {
    this.currentState = {
      organizationId,
      status: "UNKNOWN",
      stores: [],
    };
  }

  getState(): StoreContextState {
    return this.currentState;
  }

  getSelectableStores(): readonly AccessibleStore[] {
    return this.currentState.stores.filter(isSelectableStore);
  }

  subscribe(listener: StoreContextListener): () => void {
    this.listeners.add(listener);

    return () => {
      this.listeners.delete(listener);
    };
  }

  /**
   * Reconciles the server-provided access scope while preserving a still-valid
   * active store. If access or operational availability changes, the active
   * store is cleared and a valid store is selected only when it is unique.
   */
  setAccessibleStores(stores: readonly AccessibleStore[]): StoreContextState {
    const normalizedStores = normalizeStores(stores, this.currentState.organizationId);
    const currentStoreId = this.currentState.activeStore?.id;
    const activeStore =
      currentStoreId === undefined
        ? undefined
        : normalizedStores.find((store) => store.id === currentStoreId && isSelectableStore(store));

    if (activeStore !== undefined) {
      return this.setState({
        activeStore,
        organizationId: this.currentState.organizationId,
        status: "ACTIVE",
        stores: normalizedStores,
      });
    }

    const selectableStores = normalizedStores.filter(isSelectableStore);
    if (normalizedStores.length === 0) {
      return this.setState({
        organizationId: this.currentState.organizationId,
        status: "NO_ACCESSIBLE_STORES",
        stores: normalizedStores,
      });
    }

    if (selectableStores.length === 0) {
      return this.setState({
        organizationId: this.currentState.organizationId,
        status: "NO_SELECTABLE_STORES",
        stores: normalizedStores,
      });
    }

    if (selectableStores.length === 1) {
      const onlyStore = selectableStores[0];
      if (onlyStore === undefined) {
        throw new StoreSelectionError("A selectable store was expected.");
      }

      return this.setState({
        activeStore: onlyStore,
        organizationId: this.currentState.organizationId,
        status: "ACTIVE",
        stores: normalizedStores,
      });
    }

    return this.setState({
      organizationId: this.currentState.organizationId,
      status: "SELECTION_REQUIRED",
      stores: normalizedStores,
    });
  }

  selectStore(storeId: string): StoreContextState {
    const store = this.currentState.stores.find((candidate) => candidate.id === storeId);
    if (store === undefined) {
      throw new StoreSelectionError("The store is not in the current access scope.");
    }

    if (!isSelectableStore(store)) {
      throw new StoreSelectionError("The store is not operational.");
    }

    return this.setState({
      activeStore: store,
      organizationId: this.currentState.organizationId,
      status: "ACTIVE",
      stores: this.currentState.stores,
    });
  }

  clear(): StoreContextState {
    const selectableStores = this.getSelectableStores();

    return this.setState({
      organizationId: this.currentState.organizationId,
      status:
        this.currentState.stores.length === 0
          ? "NO_ACCESSIBLE_STORES"
          : selectableStores.length === 0
            ? "NO_SELECTABLE_STORES"
            : "SELECTION_REQUIRED",
      stores: this.currentState.stores,
    });
  }

  private setState(state: StoreContextState): StoreContextState {
    this.currentState = state;
    for (const listener of this.listeners) {
      listener(state);
    }

    return state;
  }
}

export function isSelectableStore(store: AccessibleStore): boolean {
  return store.status === "ACTIVE";
}

function normalizeStores(
  stores: readonly AccessibleStore[],
  organizationId: string,
): readonly AccessibleStore[] {
  const seenStoreIds = new Set<string>();

  return stores.map((store) => {
    if (store.organizationId !== organizationId) {
      throw new StoreSelectionError("A store belongs to a different organization.");
    }

    if (seenStoreIds.has(store.id)) {
      throw new StoreSelectionError("The access scope contains a duplicate store.");
    }

    seenStoreIds.add(store.id);
    return { ...store };
  });
}
