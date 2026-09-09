"use client";

import { Button, Dialog, Toast } from "@zandu/ui";
import { createContext, useContext, useRef, useSyncExternalStore } from "react";

import { NotificationCenter } from "./index";

import type { ReactNode } from "react";

const NotificationCenterContext = createContext<NotificationCenter | undefined>(undefined);
const emptyNotifications: readonly [] = [];

export function NotificationProvider({
  children,
  notificationCenter,
}: Readonly<{
  children: ReactNode;
  notificationCenter?: NotificationCenter;
}>) {
  const centerReference = useRef<NotificationCenter | undefined>(undefined);
  centerReference.current ??= notificationCenter ?? new NotificationCenter();

  return (
    <NotificationCenterContext.Provider value={centerReference.current}>
      {children}
    </NotificationCenterContext.Provider>
  );
}

export function useNotifications(): NotificationCenter {
  const notificationCenter = useContext(NotificationCenterContext);
  if (notificationCenter === undefined) {
    throw new Error("useNotifications must be used inside NotificationProvider.");
  }

  return notificationCenter;
}

export function NotificationViewport() {
  const notificationCenter = useNotifications();
  const notifications = useSyncExternalStore(
    (listener) => notificationCenter.subscribe(listener),
    () => notificationCenter.getState(),
    () => emptyNotifications,
  );

  return (
    <ol aria-label="Notifications" className="zandu-notification-viewport">
      {notifications.map((notification) => (
        <li key={notification.id}>
          <Toast tone={notification.tone}>
            <span>{notification.message}</span>
            <Button
              aria-label="Fermer la notification"
              onClick={() => notificationCenter.dismiss(notification.id)}
              type="button"
              variant="secondary"
            >
              Fermer
            </Button>
          </Toast>
        </li>
      ))}
    </ol>
  );
}

export function ConfirmationDialog({
  cancelLabel = "Annuler",
  confirmLabel,
  impact,
  onCancel,
  onConfirm,
  open,
  title,
}: Readonly<{
  cancelLabel?: string;
  confirmLabel: string;
  impact: string;
  onCancel: () => void;
  onConfirm: () => void;
  open: boolean;
  title: string;
}>) {
  return (
    <Dialog onClose={onCancel} open={open} title={title}>
      <p>{impact}</p>
      <div className="zandu-confirmation-dialog__actions">
        <Button onClick={onCancel} type="button" variant="secondary">
          {cancelLabel}
        </Button>
        <Button onClick={onConfirm} type="button" variant="danger">
          {confirmLabel}
        </Button>
      </div>
    </Dialog>
  );
}
