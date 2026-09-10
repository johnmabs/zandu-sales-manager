import type { FoundationApi } from "@zandu/api-client";

type StoreAccess = Parameters<FoundationApi["listAccessibleStores"]>[0];

/**
 * The Foundation client obtains the authoritative collection and applies its
 * defensive organization and store-scope projection before it reaches this UI.
 */
export function listStores(api: FoundationApi, access: StoreAccess) {
  return api.listAccessibleStores(access);
}
