import { z } from "@zandu/forms";

import type {
  StockTransferCreateInput,
  StockTransferLineCreateInput,
  StockTransferReceiveInput,
  StockTransferShipInput,
} from "@zandu/api-client";

const positiveExactDecimal = z
  .string()
  .trim()
  .min(1, "La quantité est requise.")
  .regex(/^\d+(?:\.\d+)?$/, "Utilisez un nombre décimal positif avec un point.")
  .refine((value) => !/^0+(?:\.0+)?$/.test(value), "La quantité doit être supérieure à zéro.");

const nonNegativeExactDecimal = z
  .string()
  .trim()
  .min(1, "La quantité est requise.")
  .regex(/^\d+(?:\.\d+)?$/, "Utilisez un nombre décimal positif ou nul avec un point.");

export const stockTransferCreateSchema = z
  .object({
    destinationStoreId: z.string().trim().min(1, "Le magasin destination est requis."),
    sourceStoreId: z.string().trim().min(1, "Le magasin source est requis."),
  })
  .refine((values) => values.sourceStoreId !== values.destinationStoreId, {
    message: "Le magasin destination doit être différent du magasin source.",
    path: ["destinationStoreId"],
  });

export const stockTransferLineSchema = z.object({
  productId: z.string().trim().min(1, "Le produit est requis."),
  requestedQuantity: positiveExactDecimal,
});

export const stockTransferLineQuantitySchema = z.object({
  requestedQuantity: positiveExactDecimal,
});

export const stockTransferShipSchema = z.object({
  lines: z
    .array(
      z.object({
        lineId: z.string().trim().min(1),
        shippedQuantity: nonNegativeExactDecimal,
      }),
    )
    .min(1, "Le transfert doit contenir au moins une ligne."),
});

export const stockTransferReceiveSchema = z.object({
  lines: z
    .array(
      z.object({
        lineId: z.string().trim().min(1),
        receivedQuantity: nonNegativeExactDecimal,
      }),
    )
    .min(1, "Le transfert doit contenir au moins une ligne."),
});

export type StockTransferCreateFormValues = z.infer<typeof stockTransferCreateSchema>;
export type StockTransferLineFormValues = z.infer<typeof stockTransferLineSchema>;
export type StockTransferLineQuantityFormValues = z.infer<typeof stockTransferLineQuantitySchema>;
export type StockTransferReceiveFormValues = z.infer<typeof stockTransferReceiveSchema>;
export type StockTransferShipFormValues = z.infer<typeof stockTransferShipSchema>;

export function toStockTransferCreateInput(
  values: StockTransferCreateFormValues,
): StockTransferCreateInput {
  return {
    destinationStoreId: values.destinationStoreId.trim(),
    sourceStoreId: values.sourceStoreId.trim(),
  };
}

export function toStockTransferLineCreateInput(
  values: StockTransferLineFormValues,
): StockTransferLineCreateInput {
  return {
    productId: values.productId.trim(),
    requestedQuantity: values.requestedQuantity.trim(),
  };
}

export function toStockTransferShipInput(
  values: StockTransferShipFormValues,
): StockTransferShipInput {
  return {
    lines: values.lines.map((line) => ({
      lineId: line.lineId.trim(),
      shippedQuantity: line.shippedQuantity.trim(),
    })),
  };
}

export function toStockTransferReceiveInput(
  values: StockTransferReceiveFormValues,
): StockTransferReceiveInput {
  return {
    lines: values.lines.map((line) => ({
      lineId: line.lineId.trim(),
      receivedQuantity: line.receivedQuantity.trim(),
    })),
  };
}
