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

## Category-Based Inspection Questions

Inspection templates can now be assigned to a tool category and independently
configured for Checkout, Checkin, or Both. Selection priority is:

1. Matching category + exact inspection type
2. Matching category + Both
3. Default template + exact inspection type
4. Default template + Both

Manage sets at `/admin/inspection_questions.php`.
Import `database/category_inspection_questions.sql` once before using the feature.
