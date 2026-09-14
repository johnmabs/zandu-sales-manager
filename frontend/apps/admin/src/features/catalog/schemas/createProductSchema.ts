import { z } from "@zandu/forms";

import type { ProductCreateInput } from "@zandu/api-client";

export const createProductSchema = z.object({
  baseUnitId: z.string().trim().min(1, "L’unité de base est requise."),
  categoryId: z.string(),
  description: z.string(),
  inventoryTracked: z.boolean(),
  name: z.string().trim().min(1, "Le nom est requis."),
  productCode: z.string().trim().min(1, "Le code est requis."),
  taxCategoryId: z.string(),
  type: z.enum(["PHYSICAL", "SERVICE"]),
});

export type CreateProductFormValues = z.infer<typeof createProductSchema>;

export function toProductCreateInput(values: CreateProductFormValues): ProductCreateInput {
  return {
    ...values,
    baseUnitId: values.baseUnitId.trim(),
    categoryId: values.categoryId.trim() || null,
    description: values.description.trim() || null,
    name: values.name.trim(),
    productCode: values.productCode.trim(),
    taxCategoryId: values.taxCategoryId.trim() || null,
  };
}
