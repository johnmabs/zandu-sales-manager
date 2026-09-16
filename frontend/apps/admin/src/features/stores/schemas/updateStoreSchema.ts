import { z } from "@zandu/forms";

import type { StoreUpdateInput } from "@zandu/api-client";

export const updateStoreSchema = z.object({
  address: z.string(),
  locale: z.string(),
  name: z.string(),
  timeZone: z.string(),
});

export type UpdateStoreFormValues = z.infer<typeof updateStoreSchema>;

export function toUpdateStoreInput(
  values: UpdateStoreFormValues,
  expectedVersion: number,
): StoreUpdateInput {
  return {
    address: values.address === "" ? null : values.address,
    expectedVersion,
    locale: values.locale,
    name: values.name,
    timeZone: values.timeZone,
  };
}
