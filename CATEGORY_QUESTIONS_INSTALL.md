# Category-Based Inspection Questions

## Install

1. Back up the database and application.
2. Import `database/category_inspection_questions.sql`.
3. Replace the application files with the files in this package.
4. Open **Administration → Inspection Questions**.

## How it works

Each question set can be assigned to:

- A specific tool category, or
- Default / All Categories

Each set can apply to Checkout, Checkin, or Both.

The system automatically looks up the scanned tool's `category_id` and chooses
the most specific active question set. If the category has no configured set,
the original default inspection questions are used.

Starter question sets are included for Power Tools, Hand Tools, and Safety
Equipment when those categories exist.
