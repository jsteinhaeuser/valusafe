# Changelog

All changes that affect users. Generated from the project history on 2026-10-06.

Earlier versions (before 4.3.28) are documented in German only: [changelog_vollstaendig.txt](changelog_vollstaendig.txt).

## 4.3.32 — 2026-10-03

- **Fixed:** In the personal profile, the colour themes "Navy" and "Emerald" could be selected, but "Cloud" was saved instead. The check for allowed themes did not know the two newest ones. The settings page had always handled them correctly.
- **Fixed:** The automatic backup (cron_backup.php) now accepts web requests only with the BACKUP_TOKEN from config.php. If the token was missing, a built-in fallback value applied that could be read in the source code. The setup wizard always creates the token; if you wrote your config.php by hand, check that it contains BACKUP_TOKEN.
- **Fixed:** The menu item "Diagnostic tools" in the admin area now appears only if the Tools folder exists. The installation package deliberately does not include it, so the item led nowhere.
- **Fixed:** In some browsers, such as Edge on Windows, the "Action" drop-down in the overview (Hide, Show again, Delete) showed only one option - the others were white on white. Found while testing Valu-Basic on Windows.

## 4.3.31 — 2026-10-01

- **Fixed:** The admin overview, the settings and the statistics page now label the number of rooms as "Rooms". Previously it said "Locations", and the smaller number next to it was called "Rooms" although it counted the locations within rooms - for example "6 Locations · 1 Rooms · 1 Pos." for six rooms and one location. Now: "6 Rooms · Locations: 1 · Pos.: 1". The numbers themselves were always right, only the labels were wrong. No action needed.

## 4.3.30 — 2026-09-30

- **Fixed:** The Docker image is now based on PHP 8.4 instead of 8.2. PHP 8.2 receives no security fixes after 31 December 2026. If you run ValuSafe with Docker, rebuild the image (in the docker folder: docker compose build --pull, then docker compose up -d). Database, photos, receipts and backups live in their own volumes and are kept; credentials still come from the .env file. If ValuSafe runs at a web host, you are not affected - the host decides the PHP version; ValuSafe runs on PHP 8.2 to 8.4.
- **Fixed:** Under Settings -> "Privacy & my data", both buttons now work as described. "Delete account" used to fail for every account with a technical error message - the account remained. "Download data" did produce a file, but it lacked the account data and the activity log; only the items were included. Now the file contains account, items and activities (without password and access codes), and deletion removes the account together with its profile picture, open password-reset links and stored login attempts. Items are kept and still carry the name shown under "Created by"; log entries are kept as well but no longer point to the account. The note below the button used to promise that items would no longer be assigned to any user - that was not true and has been corrected (all nine languages). The same now applies when an administrator deletes a user in user management. If you tried to delete your account before without success, you can now try again.

## 4.3.29 — 2026-09-28

- **Fixed:** The installation package contained a .user.ini file that made PHP show error messages in the browser - including file paths and fragments of database queries. It was meant for development and should never have been shipped. Since version 4.3.25 ValuSafe switches this display off by itself on almost all pages and writes errors to logs/php_errors.log instead; the file has now been removed. If you installed ValuSafe from the package, please delete the .user.ini file in the installation folder (FTP programs often show it only when hidden files are displayed). The system overview in the admin area shows whether it is still there.
- **Fixed:** The setup wizard now checks every PHP extension ValuSafe needs. Previously it asked for only three; if mbstring or fileinfo was missing at the host, it still showed everything green - afterwards the list stopped with an error or every image upload failed. Both are now required; cURL and EXIF are shown as recommended. Existing installations are not affected: wherever ValuSafe runs, both are present.
- **Fixed:** While the "Help & information" window is open, the (i) in the sidebar is now highlighted. Previously the icon of the page underneath stayed highlighted, for example the gear of the settings - it looked as if the click had not worked. When the window closes, the highlight returns.
- **Fixed:** ValuSafe can now run in a subfolder, such as example.com/valusafe/, without losing its app features. Previously the service worker, the app manifest, the QR code on the "Public link" page and two links assumed that ValuSafe sits in the root of the domain; in a subfolder ValuSafe could not be installed as an app, and the cache would have picked up photos and receipts as well. If you installed in the root, nothing changes for you.

## 4.3.28 — 2026-09-28

- **Fixed:** The system overview in the admin area no longer shows a "security certificate". The card with five stars, "10/10 OWASP" and "Production Ready", and the linked document from June 2026, were not based on any independent review - they were self-made, and later findings disproved several of their claims. Both have been removed. No action needed.
- **Fixed:** In the search filter of the list, the first choice in the "Room" field is now "All rooms" instead of "All locations" - matching what the field has listed since the switch to rooms. In all nine languages. No action needed.
