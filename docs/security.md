# Security

## Built into the Jawaria Clinic Toolkit plugin (automatic)

- **Theme and plugin code editor disabled** in the dashboard (`DISALLOW_FILE_EDIT`).
- **XML-RPC turned off.** It's a common target for password-guessing attacks.
- **WordPress version hidden**, and old RSD/WLW links removed.
- **Security headers added:** `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, plus HSTS on HTTPS.
- **Username discovery blocked:** `/?author=1` and the public users API no longer reveal usernames.
- **Generic login error message**, so attackers can't tell whether a username exists.
- **Appointment form protection:** security token (nonce), hidden honeypot field against bots, limit of 5 requests per hour per visitor, strict checks on every field, and no medical details collected.
- **Patient records are private.** Appointment requests and reviews are never public, never in sitemaps and only visible to staff.
- **Minimal email notifications.** Staff emails contain name, phone and preferred date only. Details stay in the dashboard.

## Do these on the live site (15 minutes)

1. **SSL (https).** Turn it on from the hosting panel (free Let's Encrypt). Then set the WordPress Address and Site Address to `https://`.
2. **Wordfence Security (free).** It adds a firewall, malware scan, login attempt limits and **two-factor login**. Turn 2FA on for every admin.
3. **UpdraftPlus (free).** Daily database backups and weekly file backups, sent to **Google Drive** (not only the server). Test a restore once.
4. **Users.**
   - Never use "admin" as a username, and give every admin a strong unique password.
   - Give clinic staff the **Editor** role, not Administrator. Delete unused accounts.
5. **Updates.** Update WordPress, Astra, Elementor and plugins every week, and turn on automatic updates for minor releases.
6. **Fewer plugins.** Install only the plugins listed in the README, and delete (not just deactivate) anything unused.
7. **Hosting.** Choose a host with PHP 8.1+, daily backups and a firewall. Keep `wp-config.php` permissions at 440 or 400.
8. **SMTP email.** Use the WP Mail SMTP plugin with a proper email account, so booking notifications aren't marked as spam.

## Privacy

- Collect only what's needed to arrange an appointment.
- Delete old appointment requests on a schedule (for example after 12 months): **Appointments → select → Move to Trash**.
- Keep the privacy policy accurate and dated.
