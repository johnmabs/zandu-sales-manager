import { z } from "@zandu/forms";

import type { StoreCreateInput } from "@zandu/api-client";

/**
 * The OpenAPI contract publishes string fields and a nullable address. Business
 * rules such as code uniqueness remain server-owned.
 */
export const createStoreSchema = z.object({
  address: z.string(),
  code: z.string(),
  currency: z.string(),
  locale: z.string(),
  name: z.string(),
  timeZone: z.string(),
});

export type CreateStoreFormValues = z.infer<typeof createStoreSchema>;

export function toCreateStoreInput(values: CreateStoreFormValues): StoreCreateInput {
  return {
    address: values.address === "" ? null : values.address,
    code: values.code,
    currency: values.currency,
    locale: values.locale,
    name: values.name,
    timeZone: values.timeZone,
  };
}
