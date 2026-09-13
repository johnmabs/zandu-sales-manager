"use client";

import { useEffectiveAccess } from "@zandu/authorization";
import Link from "next/link";
import { usePathname } from "next/navigation";

import { visibleAccessNavigation } from "../access-navigation";

export function AccessNavigation() {
  const access = useEffectiveAccess();
  const pathname = usePathname();
  const navigation = visibleAccessNavigation(access);

  if (navigation.length === 0) {
    return null;
  }

  return (
    <nav aria-label="Navigation Accès" className="zandu-access-navigation">
      <ul>
        {navigation.map((item) => (
          <li key={item.href}>
            <Link aria-current={pathname === item.href ? "page" : undefined} href={item.href}>
              {item.label}
            </Link>
          </li>
        ))}
      </ul>
    </nav>
  );
}
