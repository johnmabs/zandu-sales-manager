export type SortDirection = "asc" | "desc";

export type TableSort = Readonly<{
  direction: SortDirection;
  field: string;
}>;

export type AdminTableQuery = Readonly<{
  filters: Readonly<Record<string, string>>;
  page: number;
  search?: string;
  sort?: TableSort;
}>;

const filterPrefix = "filter.";

/** Parses table state from URL parameters so refresh, sharing, and history preserve it. */
export function parseAdminTableQuery(searchParams: URLSearchParams): AdminTableQuery {
  const filters: Record<string, string> = {};
  for (const [key, value] of searchParams) {
    if (key.startsWith(filterPrefix) && value !== "") {
      filters[key.slice(filterPrefix.length)] = value;
    }
  }

  const requestedPage = Number.parseInt(searchParams.get("page") ?? "1", 10);
  const page = Number.isSafeInteger(requestedPage) && requestedPage > 0 ? requestedPage : 1;
  const requestedSearch = searchParams.get("search");
  const search = requestedSearch === null ? undefined : nonBlank(requestedSearch);
  const requestedSortField = searchParams.get("sort");
  const sortField = requestedSortField === null ? undefined : nonBlank(requestedSortField);
  const direction = searchParams.get("direction");
  const sort =
    sortField === undefined || !isSortDirection(direction)
      ? undefined
      : { direction, field: sortField };

  return {
    filters,
    page,
    ...(search === undefined ? {} : { search }),
    ...(sort === undefined ? {} : { sort }),
  };
}

export function toAdminTableSearchParams(query: AdminTableQuery): URLSearchParams {
  const searchParams = new URLSearchParams();
  if (query.page > 1) {
    searchParams.set("page", String(query.page));
  }

  if (query.search !== undefined && query.search !== "") {
    searchParams.set("search", query.search);
  }

  if (query.sort !== undefined) {
    searchParams.set("sort", query.sort.field);
    searchParams.set("direction", query.sort.direction);
  }

  for (const [name, value] of Object.entries(query.filters).sort(([first], [second]) =>
    first.localeCompare(second),
  )) {
    if (value !== "") {
      searchParams.set(`${filterPrefix}${name}`, value);
    }
  }

  return searchParams;
}

export function withTablePage(query: AdminTableQuery, page: number): AdminTableQuery {
  return { ...query, page: Math.max(1, page) };
}

export function withTableSearch(
  query: AdminTableQuery,
  search: string | undefined,
): AdminTableQuery {
  const normalizedSearch = nonBlank(search ?? "");

  return {
    ...query,
    page: 1,
    ...(normalizedSearch === undefined ? {} : { search: normalizedSearch }),
  };
}

export function withTableFilter(
  query: AdminTableQuery,
  name: string,
  value: string | undefined,
): AdminTableQuery {
  const filters = { ...query.filters };
  const normalizedValue = nonBlank(value ?? "");
  if (normalizedValue === undefined) {
    delete filters[name];
  } else {
    filters[name] = normalizedValue;
  }

  return { ...query, filters, page: 1 };
}

export function toggleTableSort(query: AdminTableQuery, field: string): AdminTableQuery {
  if (query.sort?.field !== field) {
    return { ...query, page: 1, sort: { direction: "asc", field } };
  }

  if (query.sort.direction === "asc") {
    return { ...query, page: 1, sort: { direction: "desc", field } };
  }

  const { sort: _sort, ...queryWithoutSort } = query;

  return { ...queryWithoutSort, page: 1 };
}

function nonBlank(value: string): string | undefined {
  const normalized = value.trim();

  return normalized === "" ? undefined : normalized;
}

function isSortDirection(value: string | null): value is SortDirection {
  return value === "asc" || value === "desc";
}
