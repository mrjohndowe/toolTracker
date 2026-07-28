# Inspection 404 Hotfix

This patch fixes inspection redirects when ToolTrack is installed in a subfolder such as:

`http://localhost/ToolTrack`

## Install

Copy the contents of this ZIP into the ToolTrack project root and replace matching files.

Important files:

- `inspections/_common.php`
- `inspections/queue.php`
- `checkout/new.php`
- `checkout/return.php`
- `mobile/api_checkout.php`
- `mobile/api_return.php`

Test the route by opening:

`YOUR_TOOLTRACK_URL/inspections/path_test.php`

It should report that `queue.php exists=yes`.

Make sure `BASE_URL` points to the ToolTrack installation path. Examples:

- Root installation: `http://localhost`
- XAMPP subfolder: `http://localhost/ToolTrack`
- Domain subfolder: `https://example.com/tooltrack`
