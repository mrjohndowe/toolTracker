# Notification Scheduler

Run this command every 15 minutes:

```bash
php /path/to/ToolTrack_Pro_v1/cron/run_notifications.php
```

## Windows Task Scheduler

Program/script:

```text
C:\xampp\php\php.exe
```

Arguments:

```text
C:\xampp\htdocs\ToolTrack_Pro_v1\cron\run_notifications.php
```

Recommended trigger:

- Repeat every 15 minutes
- Run whether the user is logged in or not

## Linux Cron

```cron
*/15 * * * * /usr/bin/php /var/www/ToolTrack_Pro_v1/cron/run_notifications.php
```

## Tasks Included

- Overdue checkout alerts
- Upcoming maintenance alerts
- Overdue work-order alerts
- Upcoming calibration alerts
- Email queue processing

## Email

The included sender uses PHP `mail()`.

For production, replace it with authenticated SMTP or an email API.
