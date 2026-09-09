import { NotificationProvider } from "@zandu/notifications/react";

import { AdminRuntimeProvider } from "../src/runtime/AdminRuntime";

import type { Metadata } from "next";
import type { ReactNode } from "react";

import "./globals.css";
import "@zandu/notifications/styles.css";

export const metadata: Metadata = {
  title: "Zandu Admin",
  description: "Zandu Sales Manager administration",
};

type RootLayoutProperties = Readonly<{
  children: ReactNode;
}>;

export default function RootLayout({ children }: RootLayoutProperties) {
  return (
    <html lang="en">
      <body>
        <NotificationProvider>
          <AdminRuntimeProvider>{children}</AdminRuntimeProvider>
        </NotificationProvider>
      </body>
    </html>
  );
}
