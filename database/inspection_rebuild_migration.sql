USE tooltrack;

-- Normalize older completed returns that were labeled as Inspection or Repair.
-- Their tool status remains Inspection/Repair; only the checkout item is marked
-- as physically returned.
UPDATE checkout_items
SET return_status = 'Returned'
WHERE returned_at IS NOT NULL
  AND return_status IN ('Inspection', 'Repair');

-- Repair transaction statuses based on the actual number of pending items.
UPDATE checkout_transactions ct
LEFT JOIN (
    SELECT
        transaction_id,
        COUNT(*) AS total_count,
        SUM(return_status = 'Pending') AS pending_count
    FROM checkout_items
    GROUP BY transaction_id
) counts ON counts.transaction_id = ct.id
SET
    ct.status = CASE
        WHEN COALESCE(counts.total_count, 0) > 0
         AND COALESCE(counts.pending_count, 0) = 0 THEN 'Closed'
        WHEN COALESCE(counts.pending_count, 0) < COALESCE(counts.total_count, 0) THEN 'Partially Returned'
        ELSE 'Open'
    END,
    ct.returned_date = CASE
        WHEN COALESCE(counts.total_count, 0) > 0
         AND COALESCE(counts.pending_count, 0) = 0
        THEN COALESCE(ct.returned_date, NOW())
        ELSE ct.returned_date
    END
WHERE ct.status <> 'Cancelled';
