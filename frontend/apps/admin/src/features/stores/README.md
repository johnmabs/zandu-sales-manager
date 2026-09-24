# Stores feature

This boundary owns the Admin Stores API adapters, feature components, hooks,
route composition, runtime schemas, optional local state, and its tests.
Next.js route adapters consume the feature through `index.ts`; internal modules
remain implementation details of the feature.

It may consume Foundation packages, but shared packages and other applications
must not depend on it. Store API contracts are added only from the backend
OpenAPI contract in the Epics that need them; this feature does not reproduce
the Store aggregate or its lifecycle rules.
