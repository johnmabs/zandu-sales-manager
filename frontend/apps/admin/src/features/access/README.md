# Access Management feature

This boundary coordinates the Admin experience for organization memberships,
invitations, roles, role assignments, and organization/store scopes.

It may consume generated API contracts and Frontend Foundation packages. It
must not reproduce the backend authorization engine, calculate effective
permissions, or decide whether a membership, role assignment, or scope change
is legal. Symfony remains authoritative.

Member, invitation, and role slices stay in this feature because their Admin
workflows coordinate closely. Generic UI primitives remain in shared packages.
