type RoutePlaceholderProperties = Readonly<{
  title: string;
}>;

/**
 * Temporary content for a routed Admin capability.
 *
 * The Admin shell, navigation and feature-specific content are introduced by
 * later epics; routes remain independently addressable in the meantime.
 */
export function RoutePlaceholder({ title }: RoutePlaceholderProperties) {
  return (
    <main>
      <h1>{title}</h1>
    </main>
  );
}
