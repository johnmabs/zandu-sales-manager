import { AccessNavigation } from "./AccessNavigation";

type AccessManagementPageProperties = Readonly<{
  title: string;
}>;

/**
 * Shared F2.2 screen frame. Data and actions remain in their later feature
 * slices; this component only exposes routes the server projection permits.
 */
export function AccessManagementPage({ title }: AccessManagementPageProperties) {
  return (
    <section>
      <h1>{title}</h1>
      <AccessNavigation />
    </section>
  );
}
