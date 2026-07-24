# ToolTrack Pro REST API

Base URL:

`https://example.com/ToolTrack_Pro_v1/api/v1`

## Authentication

Use a Bearer token:

```http
Authorization: Bearer tt_your_token_here
Accept: application/json
Content-Type: application/json
```

Tokens are created by an administrator at:

`/admin/api_tokens.php`

## Scopes

- `tools:read`
- `employees:read`
- `checkout:read`
- `checkout:write`
- `maintenance:read`
- `maintenance:write`

## Pagination

List endpoints support:

- `page`
- `per_page`

Maximum `per_page` is 100.

Example:

`GET /api/v1/tools.php?page=2&per_page=50`

## Tool Filters

`GET /api/v1/tools.php`

Optional query parameters:

- `q`
- `status`
- `category_id`
- `location_id`
- `page`
- `per_page`

## Employee Filters

`GET /api/v1/employees.php`

Optional query parameters:

- `q`
- `status`
- `department_id`
- `page`
- `per_page`

## Create Checkout

`POST /api/v1/checkout.php`

```json
{
  "employee_id": 12,
  "tool_ids": [3, 8, 15],
  "due_date": "2026-08-01 17:00:00",
  "notes": "Issued for field work"
}
```

## Return Tool

`POST /api/v1/return.php`

```json
{
  "tool_id": 3,
  "return_condition": "Good",
  "return_status": "Returned",
  "notes": "Returned clean and operational"
}
```

Allowed return statuses:

- `Returned`
- `Inspection`
- `Repair`
- `Lost`

## Create Work Order

`POST /api/v1/work_order.php`

```json
{
  "tool_id": 3,
  "maintenance_type_id": 1,
  "title": "Replace damaged power cord",
  "description": "Cord insulation is split near the housing.",
  "priority": "High",
  "assigned_to": "Maintenance Team",
  "vendor_name": "",
  "due_date": "2026-08-05"
}
```

## Example cURL

```bash
curl -H "Authorization: Bearer tt_your_token_here" \
     -H "Accept: application/json" \
     "https://example.com/ToolTrack_Pro_v1/api/v1/tools.php?status=Available"
```

## Success Response

```json
{
  "success": true,
  "data": [],
  "meta": {
    "page": 1,
    "per_page": 25,
    "total": 0,
    "total_pages": 1
  },
  "request_id": "..."
}
```

## Error Response

```json
{
  "success": false,
  "error": {
    "message": "Invalid or expired API token.",
    "details": []
  },
  "request_id": "..."
}
```

## Security Notes

- Tokens are stored as SHA-256 hashes.
- Plain tokens are displayed only once.
- Token scopes restrict endpoint access.
- Expiration and revocation are supported.
- Checkout and return endpoints use database transactions and row locking.
- API requests are logged with request ID, IP, status, and execution time.
