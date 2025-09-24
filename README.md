# Car Booking Application – Secure Version

## Team details
- Member 1: _Add name and index number_
- Member 2: _Add name and index number_
- Member 3: _Add name and index number_
- Member 4: _Add name and index number_

## Project links
- Original project: <https://github.com/your-source-repository>
- Secure fork: <https://github.com/your-secure-repository>

Replace the above links with the actual GitHub repositories that contain the original and improved codebases.

## Summary of improvements
This iteration of the Car Booking Application focuses on fixing critical security flaws and adding OpenID Connect login support. Highlights include:

- Replaced hard-coded database credentials with environment/config driven settings and hardened session cookies.
- Migrated all authentication code to use prepared statements and modern password hashing (`password_hash`/`password_verify`).
- Added reusable CSRF token helpers and integrated them into user, booking and administrative forms.
- Sanitised all database writes and escaped all untrusted output to eliminate SQL injection and cross-site scripting vectors.
- Hardened the administrative vehicle upload flow with strict file validation and randomised filenames.
- Added granular privilege checks and safe update workflows to the booking management console.
- Implemented Google OpenID Connect login, including state validation and secure account provisioning.

## Configuration
Create `inc/config.php` based on `inc/config.sample.php` (do **not** commit the file with real secrets):

```php
<?php
return [
    'host' => '127.0.0.1',
    'username' => 'rentcar_user',
    'password' => 'change-me',
    'database' => 'rentcar',
    'google_client_id' => 'your-google-client-id',
    'google_client_secret' => 'your-google-client-secret',
    'google_redirect_uri' => 'https://your-domain.example/oauth/google_callback.php',
];
```

For local testing you can set the same values through environment variables `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`, `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI`.

## Database
Import the updated schema located in `rentcar.sql`. It contains secure default credentials:

- **Admin login**: `admin` / `Admin@123`
- **Sample user**: `alice@example.com` / `Password123!`

The schema adds OAuth identity columns and enforces unique constraints on email addresses.

## Running the application
1. Configure the database and import `rentcar.sql`.
2. Ensure PHP, MySQL/MariaDB and the cURL extension are available.
3. Place the project inside your web server root (e.g., Apache with PHP).
4. Update the document root/virtual host to point to the project directory.
5. Visit `http://localhost/index.php`.

## OAuth notes
The Google OpenID Connect integration requires a Google Cloud project with OAuth consent configured. Set the authorised redirect URI to `<base-url>/oauth/google_callback.php` and store the credentials in `inc/config.php` or the environment.

## Reporting
Document all discovered vulnerabilities, the mitigation steps, and any remaining risks in the final PDF report that accompanies this project.
