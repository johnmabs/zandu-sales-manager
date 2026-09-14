"use client";
import { can, useEffectiveAccess } from "@zandu/authorization";
import { useOrganizationContext } from "@zandu/organization-context";
import { ErrorState, Spinner } from "@zandu/ui";
import Link from "next/link";
import { usePathname } from "next/navigation";

import { visiblePricingNavigation } from "../pricing-navigation";

import type { ReactNode } from "react";

export function PricingSection({
  title,
  permission,
  children,
}: Readonly<{ title: string; permission?: string; children?: ReactNode }>) {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const pathname = usePathname();
  const navigation = visiblePricingNavigation(access);
  if (access === undefined || activeOrganizationId === undefined)
    return <Spinner label="Chargement des autorisations" />;
  if (
    access.organizationId !== activeOrganizationId ||
    (permission === undefined ? navigation.length === 0 : !can(access, permission))
  )
    return (
      <ErrorState
        title="Accès refusé"
        description="Votre accès ne permet pas de consulter cette section de l’organisation active."
      />
    );
  return (
    <section>
      <h1>{title}</h1>
      <nav aria-label="Navigation Tarification">
        <ul>
          {navigation.map((item) => (
            <li key={item.href}>
              <Link href={item.href} aria-current={pathname === item.href ? "page" : undefined}>
                {item.label}
              </Link>
            </li>
          ))}
        </ul>
      </nav>
      {children}
    </section>
  );
}
