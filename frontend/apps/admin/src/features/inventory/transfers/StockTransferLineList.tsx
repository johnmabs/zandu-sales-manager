"use client";

import { formatQuantity } from "@zandu/domain-formatting";
import { EmptyState } from "@zandu/ui";

import { AdminTable } from "../../../components/tables/AdminTable";

import type { AdminTableColumn } from "../../../components/tables/AdminTable";
import type { StockTransferLineResource } from "@zandu/api-client";

export function StockTransferLineList({
  lines,
  locale,
}: Readonly<{ lines: readonly StockTransferLineResource[]; locale: string }>) {
  if (lines.length === 0) {
    return (
      <EmptyState
        title="Aucune ligne de transfert"
        description="Ce brouillon ne contient encore aucun produit."
      />
    );
  }

  const quantity = (value: string | null) =>
    value === null ? "—" : formatQuantity(value, { locale });
  const columns: readonly AdminTableColumn<StockTransferLineResource>[] = [
    { id: "productId", header: "Produit", cell: (line) => line.productId },
    {
      id: "requestedQuantity",
      header: "Quantité demandée",
      cell: (line) => quantity(line.requestedQuantity),
    },
    {
      id: "shippedQuantity",
      header: "Quantité expédiée",
      cell: (line) => quantity(line.shippedQuantity),
    },
    {
      id: "receivedQuantity",
      header: "Quantité reçue",
      cell: (line) => quantity(line.receivedQuantity),
    },
    {
      id: "transitDiscrepancy",
      header: "Écart de transit",
      cell: (line) => quantity(line.transitDiscrepancy),
    },
  ];

  return (
    <AdminTable
      columns={columns}
      emptyTitle="Aucune ligne de transfert"
      emptyDescription="Ce transfert ne contient aucune ligne."
      getRowId={(line) => line.id}
      isLoading={false}
      page={{ items: lines, page: 1, pageSize: lines.length, totalItems: lines.length }}
      query={{ filters: {}, page: 1 }}
    />
  );
}
