import { z } from "@zandu/forms";

import type { StockAdjustInput } from "@zandu/api-client";

const exactSignedDecimal = /^-?\d+(?:\.\d+)?$/;
const exactNonNegativeDecimal = /^\d+(?:\.\d+)?$/;

export const adjustStockSchema = z
  .object({
    delta: z
      .string()
      .trim()
      .min(1, "Le delta est requis.")
      .regex(exactSignedDecimal, "Utilisez un nombre décimal signé avec un point.")
      .refine((value) => !isExactZero(value), "Le delta doit être différent de zéro."),
    reason: z.string().trim().min(1, "La raison est requise."),
    unitCost: z.string().trim(),
  })
  .superRefine((values, context) => {
    if (!isIncomingStockAdjustment(values.delta)) return;
    if (values.unitCost.length === 0) {
      context.addIssue({
        code: "custom",
        message: "Le coût unitaire est requis pour une entrée de stock.",
        path: ["unitCost"],
      });
    } else if (!exactNonNegativeDecimal.test(values.unitCost)) {
      context.addIssue({
        code: "custom",
        message: "Utilisez un nombre décimal positif ou nul avec un point.",
        path: ["unitCost"],
      });
    }
  });

export type AdjustStockFormValues = z.infer<typeof adjustStockSchema>;

export function isIncomingStockAdjustment(delta: string): boolean {
  const value = delta.trim();
  return exactSignedDecimal.test(value) && !value.startsWith("-") && !isExactZero(value);
}

export function toStockAdjustInput(values: AdjustStockFormValues): StockAdjustInput {
  const delta = values.delta.trim();
  return {
    delta,
    reason: values.reason.trim(),
    ...(isIncomingStockAdjustment(delta) ? { unitCost: values.unitCost.trim() } : {}),
  };
}

function isExactZero(value: string): boolean {
  const unsigned = value.trim().replace(/^-/, "");
  return /^0+(?:\.0+)?$/.test(unsigned);
}
