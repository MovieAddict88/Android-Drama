# DramaBox Clone

A high-fidelity clone of the DramaBox platform, featuring a responsive dark-mode UI, mobile-optimized video player, and an automated content scraping/generation system.

## Features

- **Dynamic Homepage:** Responsive grid layout matching `dramabox.com` aesthetic.
- **Vertical Video Player:** Mobile-first 9:16 aspect ratio player with episode navigation.
- **Admin Dashboard:** Centralized panel to manage content from multiple platforms.
- **Automated Scraping:** One-click content generation for dramas from DramaBox using their Book IDs.
- **Portable Database:** Comes with a pre-configured SQLite database for immediate setup.

## Setup

1. **Host the files** on any PHP-enabled web server (e.g., Apache, Nginx, or local development like XAMPP/MAMP).
2. **Access the site** via your browser.
3. **Admin Panel:** Navigate to `/admin/login.php` (Default credentials should be set during installation or found in the database).
4. **Content Generation:** Go to the "DramaBox" section in the admin panel and click "Generate Content" for any drama you wish to add.

## Requirements

- PHP 7.4+
- PHP cURL extension (for scraping)
- PHP PDO (with SQLite or MySQL support)

## Architecture

- **Frontend:** Pure PHP, Bootstrap 5, Bootstrap Icons.
- **Backend:** Pure PHP (No frameworks).
- **Database:** PDO-based abstraction supporting both MySQL and SQLite.
- **Scraper:** Enhanced cURL-based scraper targeting `dramaboxdb.com` and the Sansekai API.
