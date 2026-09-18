import { z } from "@zandu/forms";

import type { StockInitializeInput } from "@zandu/api-client";

const exactNonNegativeDecimal = z
  .string()
  .trim()
  .min(1, "Cette valeur est requise.")
  .regex(/^\d+(?:\.\d+)?$/, "Utilisez un nombre décimal positif ou nul avec un point.");

export const initializeStockSchema = z.object({
  quantity: exactNonNegativeDecimal,
  unitCost: exactNonNegativeDecimal,
});

export type InitializeStockFormValues = z.infer<typeof initializeStockSchema>;

export function toStockInitializeInput(values: InitializeStockFormValues): StockInitializeInput {
  return {
    quantity: values.quantity.trim(),
    unitCost: values.unitCost.trim(),
  };
}
