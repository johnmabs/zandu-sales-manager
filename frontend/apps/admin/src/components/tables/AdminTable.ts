"use client";

import { EmptyState, ErrorState, Pagination, Spinner } from "@zandu/ui";
import { createElement, Fragment } from "react";

import type { AdminTableQuery } from "./query-state";
import type { ReactNode } from "react";

export type ServerPage<Row> = Readonly<{
  items: readonly Row[];
  page: number;
  pageSize: number;
  totalItems: number;
}>;

export type AdminTableColumn<Row> = Readonly<{
  cell: (row: Row) => ReactNode;
  header: string;
  id: string;
  sortable?: boolean;
}>;

export type AdminTableProps<Row> = Readonly<{
  columns: readonly AdminTableColumn<Row>[];
  emptyDescription: string;
  emptyTitle: string;
  error?: Error;
  getRowId: (row: Row) => string;
  isLoading: boolean;
  onPageChange?: (page: number) => void;
  onSort?: (column: AdminTableColumn<Row>) => void;
  page?: ServerPage<Row>;
  query: AdminTableQuery;
  rowActions?: (row: Row) => ReactNode;
}>;

/**
 * A small Admin table renderer for one server page. Features retain ownership
 * of their columns, filters, actions, and query fetching rather than feeding a
 * universal client-side DataTable.
 */
export function AdminTable<Row>({
  columns,
  emptyDescription,
  emptyTitle,
  error,
  getRowId,
  isLoading,
  onPageChange,
  onSort,
  page,
  query,
  rowActions,
}: AdminTableProps<Row>): ReactNode {
  if (isLoading) {
    return createElement(Spinner, { label: "Chargement du tableau" });
  }

  if (error !== undefined) {
    return createElement(ErrorState, {
      description: error.message,
      title: "Impossible de charger les données",
    });
  }

  if (page === undefined || page.items.length === 0) {
    return createElement(EmptyState, { description: emptyDescription, title: emptyTitle });
  }

  const pageCount = Math.ceil(page.totalItems / page.pageSize);
  const table = createElement(
    "table",
    { className: "zandu-admin-table" },
    createElement(
      "thead",
      undefined,
      createElement(
        "tr",
        undefined,
        ...columns.map((column) =>
          createElement(
            "th",
            {
              "aria-sort": sortDirectionFor(query, column),
              key: column.id,
              scope: "col",
            },
            column.sortable === true && onSort !== undefined
              ? createElement(
                  "button",
                  {
                    className: "zandu-admin-table__sort",
                    onClick: () => onSort(column),
                    type: "button",
                  },
                  column.header,
                )
              : column.header,
          ),
        ),
        rowActions === undefined ? undefined : createElement("th", { scope: "col" }, "Actions"),
      ),
    ),
    createElement(
      "tbody",
      undefined,
      ...page.items.map((row) =>
        createElement(
          "tr",
          { key: getRowId(row) },
          ...columns.map((column) => createElement("td", { key: column.id }, column.cell(row))),
          rowActions === undefined ? undefined : createElement("td", undefined, rowActions(row)),
        ),
      ),
    ),
  );

  return createElement(
    Fragment,
    undefined,
    createElement("div", { className: "zandu-admin-table__scroll" }, table),
    onPageChange === undefined || pageCount <= 1
      ? undefined
      : createElement(Pagination, {
          currentPage: page.page,
          onPageChange,
          pageCount,
        }),
  );
}

function sortDirectionFor<Row>(
  query: AdminTableQuery,
  column: AdminTableColumn<Row>,
): "ascending" | "descending" | "none" | undefined {
  if (column.sortable !== true) {
    return undefined;
  }

  if (query.sort?.field !== column.id) {
    return "none";
  }

  return query.sort.direction === "asc" ? "ascending" : "descending";
}
