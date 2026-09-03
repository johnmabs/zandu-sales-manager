"use client";

import { forwardRef, useEffect, useId, useRef } from "react";

import type {
  ButtonHTMLAttributes,
  HTMLAttributes,
  InputHTMLAttributes,
  ReactNode,
  SelectHTMLAttributes,
  TextareaHTMLAttributes,
} from "react";

function classNames(...names: Array<string | undefined>) {
  return names.filter(Boolean).join(" ");
}

export type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
  variant?: "primary" | "secondary" | "danger";
};

export function Button({ className, variant = "primary", ...props }: ButtonProps) {
  return (
    <button
      className={classNames("zandu-button", `zandu-button--${variant}`, className)}
      {...props}
    />
  );
}

export type IconButtonProps = Omit<ButtonProps, "children"> & {
  "aria-label": string;
  children: ReactNode;
};

export function IconButton({ className, ...props }: IconButtonProps) {
  return <Button className={classNames("zandu-icon-button", className)} {...props} />;
}

export const Input = forwardRef<HTMLInputElement, InputHTMLAttributes<HTMLInputElement>>(
  ({ className, ...props }, reference) => (
    <input className={classNames("zandu-input", className)} ref={reference} {...props} />
  ),
);
Input.displayName = "Input";

export const Textarea = forwardRef<
  HTMLTextAreaElement,
  TextareaHTMLAttributes<HTMLTextAreaElement>
>(({ className, ...props }, reference) => (
  <textarea className={classNames("zandu-textarea", className)} ref={reference} {...props} />
));
Textarea.displayName = "Textarea";

export const Select = forwardRef<HTMLSelectElement, SelectHTMLAttributes<HTMLSelectElement>>(
  ({ className, ...props }, reference) => (
    <select className={classNames("zandu-select", className)} ref={reference} {...props} />
  ),
);
Select.displayName = "Select";

type ChoiceProps = Omit<InputHTMLAttributes<HTMLInputElement>, "type"> & {
  label: ReactNode;
};

export function Checkbox({ className, label, ...props }: ChoiceProps) {
  return (
    <label className={classNames("zandu-choice", className)}>
      <input type="checkbox" {...props} />
      <span>{label}</span>
    </label>
  );
}

export function Radio({ className, label, ...props }: ChoiceProps) {
  return (
    <label className={classNames("zandu-choice", className)}>
      <input type="radio" {...props} />
      <span>{label}</span>
    </label>
  );
}

type OverlayProps = {
  children: ReactNode;
  onClose: () => void;
  open: boolean;
  title: string;
};

function useOverlayFocus(open: boolean, onClose: () => void) {
  const reference = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!open) {
      return;
    }

    reference.current?.focus();

    const manageKeyboardFocus = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        onClose();

        return;
      }

      if (event.key !== "Tab") {
        return;
      }

      const focusableElements = Array.from(
        reference.current?.querySelectorAll<HTMLElement>(
          'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
        ) ?? [],
      );
      const firstElement = focusableElements[0];
      const lastElement = focusableElements.at(-1);

      if (firstElement === undefined || lastElement === undefined) {
        return;
      }

      if (event.shiftKey && document.activeElement === firstElement) {
        event.preventDefault();
        lastElement.focus();

        return;
      }

      if (
        !event.shiftKey &&
        (document.activeElement === lastElement ||
          !reference.current?.contains(document.activeElement))
      ) {
        event.preventDefault();
        firstElement.focus();
      }
    };

    document.addEventListener("keydown", manageKeyboardFocus);

    return () => document.removeEventListener("keydown", manageKeyboardFocus);
  }, [onClose, open]);

  return reference;
}

export function Dialog({ children, onClose, open, title }: OverlayProps) {
  const titleId = useId();
  const reference = useOverlayFocus(open, onClose);

  if (!open) {
    return null;
  }

  return (
    <div className="zandu-overlay" role="presentation">
      <section
        aria-labelledby={titleId}
        aria-modal="true"
        className="zandu-dialog"
        ref={reference}
        role="dialog"
        tabIndex={-1}
      >
        <header className="zandu-overlay__header">
          <h2 id={titleId}>{title}</h2>
          <IconButton aria-label="Close dialog" onClick={onClose} type="button">
            ×
          </IconButton>
        </header>
        <div className="zandu-overlay__content">{children}</div>
      </section>
    </div>
  );
}

export function Drawer({ children, onClose, open, title }: OverlayProps) {
  const titleId = useId();
  const reference = useOverlayFocus(open, onClose);

  if (!open) {
    return null;
  }

  return (
    <div className="zandu-overlay" role="presentation">
      <section
        aria-labelledby={titleId}
        aria-modal="true"
        className="zandu-drawer"
        ref={reference}
        role="dialog"
        tabIndex={-1}
      >
        <header className="zandu-overlay__header">
          <h2 id={titleId}>{title}</h2>
          <IconButton aria-label="Close drawer" onClick={onClose} type="button">
            ×
          </IconButton>
        </header>
        <div className="zandu-overlay__content">{children}</div>
      </section>
    </div>
  );
}

export function DropdownMenu({ children, label }: { children: ReactNode; label: string }) {
  return (
    <details className="zandu-dropdown-menu">
      <summary className="zandu-button zandu-button--secondary">{label}</summary>
      <div className="zandu-dropdown-menu__content" role="menu">
        {children}
      </div>
    </details>
  );
}

export function Tooltip({ children, content }: { children: ReactNode; content: string }) {
  const id = useId();

  return (
    <span aria-describedby={id} className="zandu-tooltip" tabIndex={0}>
      {children}
      <span className="zandu-tooltip__content" id={id} role="tooltip">
        {content}
      </span>
    </span>
  );
}

export function Badge({
  children,
  tone = "neutral",
}: {
  children: ReactNode;
  tone?: "neutral" | "success" | "warning" | "danger";
}) {
  return <span className={`zandu-badge zandu-badge--${tone}`}>{children}</span>;
}

export function Alert({
  children,
  tone = "info",
}: {
  children: ReactNode;
  tone?: "info" | "success" | "warning" | "danger";
}) {
  return (
    <div className={`zandu-alert zandu-alert--${tone}`} role="alert">
      {children}
    </div>
  );
}

export function Toast({
  children,
  tone = "info",
}: {
  children: ReactNode;
  tone?: "info" | "success" | "warning" | "danger";
}) {
  return (
    <div className={`zandu-toast zandu-toast--${tone}`} role="status">
      {children}
    </div>
  );
}

export function Spinner({ label = "Loading" }: { label?: string }) {
  return <span aria-label={label} className="zandu-spinner" role="status" />;
}

export function Skeleton({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
  return <div aria-hidden="true" className={classNames("zandu-skeleton", className)} {...props} />;
}

type StateProps = {
  action?: ReactNode;
  description: string;
  title: string;
};

export function EmptyState({ action, description, title }: StateProps) {
  return (
    <section className="zandu-state" aria-live="polite">
      <h2>{title}</h2>
      <p>{description}</p>
      {action}
    </section>
  );
}

export function ErrorState({ action, description, title }: StateProps) {
  return (
    <section className="zandu-state zandu-state--error" role="alert">
      <h2>{title}</h2>
      <p>{description}</p>
      {action}
    </section>
  );
}

export function Pagination({
  currentPage,
  onPageChange,
  pageCount,
}: {
  currentPage: number;
  onPageChange: (page: number) => void;
  pageCount: number;
}) {
  const pages = Array.from({ length: pageCount }, (_, index) => index + 1);

  return (
    <nav aria-label="Pagination" className="zandu-pagination">
      {pages.map((page) => (
        <Button
          aria-current={page === currentPage ? "page" : undefined}
          key={page}
          onClick={() => onPageChange(page)}
          type="button"
          variant={page === currentPage ? "primary" : "secondary"}
        >
          {page}
        </Button>
      ))}
    </nav>
  );
}
