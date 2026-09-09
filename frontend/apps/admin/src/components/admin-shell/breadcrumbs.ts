import type { AdminNavigationItem } from "./navigation";

export type Breadcrumb = Readonly<{
  href: string;
  label: string;
}>;

export function breadcrumbsForPath(
  pathname: string,
  navigation: readonly AdminNavigationItem[],
): readonly Breadcrumb[] {
  const item = navigation.find((candidate) => candidate.href === pathname);

  return item === undefined
    ? [{ href: "/app", label: "Accueil" }]
    : [
        { href: "/app", label: "Accueil" },
        { href: item.href, label: item.label },
      ];
}
