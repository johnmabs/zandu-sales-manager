import { z } from "@zandu/forms";

import type { ProductResource, ProductUpdateInput } from "@zandu/api-client";

export const updateProductSchema = z.object({
  baseUnitId: z.string().trim().min(1, "L’unité de base est requise."),
  categoryId: z.string(),
  description: z.string(),
  inventoryTracked: z.boolean(),
  name: z.string().trim().min(1, "Le nom est requis."),
  productCode: z.string().trim().min(1, "Le code est requis."),
  taxCategoryId: z.string(),
  type: z.enum(["PHYSICAL", "SERVICE"]),
});

export type UpdateProductFormValues = z.infer<typeof updateProductSchema>;

export function productUpdateDefaults(product: ProductResource): UpdateProductFormValues {
  return {
    baseUnitId: product.baseUnitId,
    categoryId: product.categoryId ?? "",
    description: product.description ?? "",
    inventoryTracked: product.inventoryTracked,
    name: product.name,
    productCode: product.productCode,
    taxCategoryId: product.taxCategoryId ?? "",
    type: product.type === "SERVICE" ? "SERVICE" : "PHYSICAL",
  };
}

export function toProductUpdateInput(
  values: UpdateProductFormValues,
  status: string,
): ProductUpdateInput {
  const editable = {
    categoryId: values.categoryId.trim() || null,
    description: values.description.trim() || null,
    inventoryTracked: values.inventoryTracked,
    name: values.name.trim(),
    taxCategoryId: values.taxCategoryId.trim() || null,
    type: values.type,
  };
  return status === "DRAFT"
    ? { ...editable, baseUnitId: values.baseUnitId.trim(), productCode: values.productCode.trim() }
    : editable;
}
