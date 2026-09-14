import type { FoundationApi } from "@zandu/api-client";

export function listCategories(
  api: FoundationApi,
  access: Parameters<FoundationApi["listCategories"]>[0],
) {
  return api.listCategories(access);
}
