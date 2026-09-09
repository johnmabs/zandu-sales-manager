export type ToastTone = "info" | "success" | "warning";

export type ToastNotification = Readonly<{
  id: string;
  message: string;
  tone: ToastTone;
}>;

export type ToastNotificationInput = Readonly<{
  message: string;
  tone?: ToastTone;
}>;

export type FailurePresentation = "INLINE_ERROR" | "PAGE_ERROR";

export type FailureNotification = Readonly<{
  critical: boolean;
}>;

type NotificationListener = () => void;

/** Manages transient, non-critical feedback only. */
export class NotificationCenter {
  private nextId = 1;
  private notifications: readonly ToastNotification[] = [];
  private readonly listeners = new Set<NotificationListener>();

  dismiss(id: string): void {
    const remaining = this.notifications.filter((notification) => notification.id !== id);
    if (remaining.length === this.notifications.length) {
      return;
    }

    this.notifications = remaining;
    this.publish();
  }

  getState(): readonly ToastNotification[] {
    return this.notifications;
  }

  notify({ message, tone = "info" }: ToastNotificationInput): string {
    const id = `notification-${this.nextId}`;
    this.nextId += 1;
    this.notifications = [...this.notifications, { id, message, tone }];
    this.publish();

    return id;
  }

  subscribe(listener: NotificationListener): () => void {
    this.listeners.add(listener);

    return () => {
      this.listeners.delete(listener);
    };
  }

  private publish(): void {
    for (const listener of this.listeners) {
      listener();
    }
  }
}

/** Critical failures, such as insufficient stock, cannot be reduced to a toast. */
export function failurePresentation({ critical }: FailureNotification): FailurePresentation {
  return critical ? "PAGE_ERROR" : "INLINE_ERROR";
}
