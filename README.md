# ToolTrack Pro v1 - Foundation

This package includes:

- Installer
- Secure login and logout
- Fixed session user handling
- Administrator, Tool Room Attendant, and Supervisor roles
- User management
- Dashboard
- CSRF protection
- Password hashing
- Session timeout
- Login throttling
- Audit logging

## Installation

1. Extract the folder into your web root, for example:

   C:\xampp\htdocs\ToolTrack_Pro_v1

2. Open:

   http://localhost/ToolTrack_Pro_v1/install/

3. Enter your MySQL credentials and create the administrator.

4. Open `config/database.php` and enter the same database credentials if needed.

5. Open `config/config.php` and change `BASE_URL` if you renamed the folder.

6. Delete or rename the `install` folder after installation.

7. Open:

   http://localhost/ToolTrack_Pro_v1/

## Requirements

- PHP 8.1+
- MySQL 5.7+ or MariaDB 10.4+
- PDO MySQL extension
- Apache or Nginx
