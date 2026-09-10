import { StoresFeaturePlaceholder } from "../components/StoresFeaturePlaceholder";

/**
 * Route composition stays in the Stores feature; Next.js pages only adapt the
 * filesystem route to this component.
 */
export function StoresRoute() {
  return <StoresFeaturePlaceholder />;
}
