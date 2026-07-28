# ToolTrack Direct Inspection Integration

This package replaces the Phase 4 desktop checkout/return files and the Phase 7 mobile checkout/return files so inspections launch automatically.

## Install
1. Back up the application and database.
2. Import `database/inspection_questions.sql` if it has not already been imported.
3. Copy all folders from this package into the ToolTrack project root and allow replacement of matching files.
4. Apply the navigation links from `includes/header_inspection_patch.txt`.

## Workflow
- Desktop and mobile checkout create the transaction, then open a required inspection queue for every tool.
- Desktop and mobile returns locate the pending checkout item, then open the check-in questionnaire before finalizing the return.
- The return status is selected automatically from the answers:
  - Complete and working: Available
  - Missing contents or poor condition: Inspection
  - Not working: Repair

## Replaced files
- `checkout/new.php`
- `checkout/return.php`
- `mobile/api_checkout.php`
- `mobile/checkout.php`
- `mobile/api_return.php`
- `mobile/return.php`

## Added files
- `inspections/_common.php`
- `inspections/queue.php`
- `inspections/history.php`
- `admin/inspection_questions.php`
