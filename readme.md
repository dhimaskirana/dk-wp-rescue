# DK WordPress Rescue Kit 🚀

A lightweight, standalone emergency recovery tool for WordPress.

Developed by Dhimas Kirana, the **DK WordPress Rescue Kit** is a single-file PHP utility designed to fix "White Screen of Death" (WSoD) and other fatal errors when the WordPress Dashboard is inaccessible. It operates independently of the WordPress Core, making it functional even when your site is completely down.

## 🛠 Features

 - **Zero-Dependency Execution:** Works without loading wp-load.php. Perfect for when the core is corrupted.
 - **Live WP_DEBUG Toggle:** Instantly enable or disable error reporting by modifying wp-config.php via a UI switch.
 - **Core Integrity Restore:** Reinstall wp-admin, wp-includes, and root PHP files from the official WordPress.org repository without touching wp-content or wp-config.php.
 - **Plugin & Theme Recovery:** Reinstall or update any plugin/theme directly from the WordPress.org repository to overwrite corrupted files.
 - **Queue-Based Processing:** Uses a JavaScript-managed job queue (Download -> Extract -> Cleanup) to prevent PHP execution timeouts on shared hosting.
 - **Modern UI:** Clean, responsive dashboard with progress tracking for every action.

## 📂 Installation & Usage

 1. Download/Copy the **dk-wp-rescue.php** script.
 2. Upload the file to your WordPress root directory (the same folder as wp-config.php).
 3. Access the tool via your browser: [https://yourdomain.com/dk-wp-rescue.php?pwd=wprescue4321](https://yourdomain.com/dk-rescue.php?pwd=wprescue4321)

(Note: You can change the ***$secret*** password inside the script).

**Perform Repairs:**

 - Toggle WP_DEBUG to see the exact cause of the crash.
 - Reinstall the Core if you suspect system file corruption.
 - Reinstall specific Plugins or Themes identified as the source of the error.

**⚠️⚠️⚠️ CLEANUP: Crucial! Delete the dk-wp-rescue.php file from your server immediately after the site is restored.**

## ⚠️ Security Warning

This tool is extremely powerful and bypasses standard WordPress authentication.

Never leave this file on a live server.

Always change the default password ($secret) before use.

I'm not responsible for any data loss or unauthorized access resulting from the misuse of this tool.

## 🏗 Technical Details

 - **Language:** PHP 7.4+
 - **Requirements:** ZipArchive PHP Extension, file_get_contents with allow_url_fopen enabled.
 - **Architecture:** Procedural PHP API with a Vanilla JavaScript (Fetch API) Frontend.

## 👨‍💻 Author

Dhimas Kirana

## 📄 License

This project is for internal use and professional digital solution environments. Use with caution.