# ToolTrack Inspection Workflow Rebuild

This build directly repairs the uploaded ToolTrack project.

## Main fixes

- The inspection request now loads `checkout/_common.php`, so
  `update_transaction_status()` always runs after check-in.
- A physical check-in always sets `checkout_items.return_status` to `Returned`.
- The tool itself is independently routed to:
  - `Available`
  - `Inspection`
  - `Repair`
- Checkout transactions close when no `Pending` items remain.
- Desktop redirects use application-relative paths exactly once.
- Mobile API responses include `BASE_URL` exactly once, fixing duplicated folder
  names and broken CSS routes.
- Multi-tool inspection queues remain supported.

## Upgrade existing database

Import:

`database/inspection_rebuild_migration.sql`

This closes older transactions whose items were already returned and normalizes
older `Inspection` or `Repair` return labels to `Returned` while preserving each
current tool status.
