# ExpressionEngine Project Setup Guide - work.zebra.hr

This guide will help you set up and run this ExpressionEngine CMS project for local development.

## Prerequisites

Before starting, ensure you have the following installed:

- **PHP 8.2** (or compatible version)
- **MySQL/MariaDB** database server
- **Apache** web server with `mod_rewrite` enabled (recommended)
- OR **PHP Built-in Server** (for development only)

## Step 1: Database Setup

### 1.1 Create the Database

Create a MySQL database named "zebra":

```sql
CREATE DATABASE zebra CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 1.2 Import Database (if you have a dump file)

If you have a database dump file, import it:

```bash
mysql -u root -p zebra < database_dump.sql
```

## Step 2: Configuration

The database configuration has been updated in `systemxtr/user/config/config.php`:

- **Database name**: `zebra`
- **Host**: `localhost`
- **Username**: `root`
- **Password**: `root`
- **Base path**: `/Users/ashraful3/Ash/Sites/work.zebra.hr/`
- **Base URL**: `http://127.0.0.1:8000/`

If you need to change any of these settings, edit `systemxtr/user/config/config.php`.

## Step 3: Update Database Configuration (if needed)

If the database already has data, you may need to update the `base_path` and `base_url` in the database:

```bash
mysql -u root -p zebra
```

Then run:

```sql
UPDATE exp_config SET value = '/Users/ashraful3/Ash/Sites/work.zebra.hr/' WHERE `key` = 'base_path';
UPDATE exp_config SET value = 'http://127.0.0.1:8000/' WHERE `key` = 'base_url';
```

## Step 4: Start the Development Server

### Option 1: PHP Built-in Server (Recommended for Development)

```bash
cd /Users/ashraful3/Ash/Sites/work.zebra.hr
php -S 127.0.0.1:8000
```

### Option 2: Apache with Virtual Host

Configure Apache to point to the project directory.

## Step 5: Access the Application

- **Frontend**: `http://127.0.0.1:8000/`
- **Control Panel**: `http://127.0.0.1:8000/admin.php`

## Before Going Live

EEHarbor add-ons (Visitor, Channel Images) run unlicensed during local development only. Before deploying to production:

1. Purchase or retrieve license keys from [EEHarbor.com](https://eeharbor.com/members).
2. Enter each key under **Add-ons → [Add-on] → License** in the Control Panel.
3. Set the production domain for each license on your EEHarbor account page.

## Troubleshooting

### Database Connection Issues

- Verify MySQL is running: `mysql -u root -p`
- Check database exists: `SHOW DATABASES;`
- Verify credentials in `systemxtr/user/config/config.php`

### Path Issues

- Ensure `base_path` in config.php is an absolute path
- Ensure `base_url` matches the URL you're using to access the site
- Check file permissions on cache and upload directories

### Permission Issues

Make sure these directories are writable:
- `systemxtr/user/cache/`
- `systemxtr/user/templates/`
- `files/` (if exists)
- `images/` (if exists)
