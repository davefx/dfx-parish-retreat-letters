=== DFX Parish Retreat Letters ===
Contributors: davefx
Tags: parish, retreat, letters, confidential, GDPR
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 26.03.22
Requires PHP: 7.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Manage personal messages in parish retreats: attendants, confidential messages, permissions, and GDPR compliance — all in one place.

== Description ==

DFX Parish Retreat Letters lets your parish manage the full lifecycle of confidential personal messages for retreat attendants — from collecting letters through a public web form to printing them securely in the admin, while keeping every piece of content fully encrypted and every action fully audited.

= How it works =

1. **Create a retreat** and register your attendants.
2. **Share each attendant's unique, private URL** with the people who want to write to them — family, friends, spiritual directors.
3. **Writers fill in the form** on a clean public page: they can type a rich-text message, attach images or documents, and agree to a legal disclaimer. A simple arithmetic CAPTCHA protects against bots.
4. **Messages are stored encrypted** in the database. Nobody can read them by browsing the admin — they are only revealed at print time.
5. **Authorised staff print the messages** from the admin panel. Each print is logged with the user's name, timestamp, and IP address.
6. **Messages are handed to attendants** during or after the retreat.

= Retreat management =

* Create retreats with name, location, start and end dates, and a custom welcome message shown on the submission form.
* Set a legal disclaimer text and an acceptance checkbox label that writers must tick before they can submit.
* Enable or disable optional **Notes** and **Internal Notes** fields per retreat (Notes are exportable; Internal Notes are not).
* Set custom **body CSS classes** on the message-form page per retreat, so each retreat can use a different visual style.
* Choose a custom **header block** and **footer block** (any WordPress block or template part) to brand the submission form page.
* Delete a retreat together with all its attendants and messages in one action.

= Attendant management =

* Add attendants individually or **import them from a CSV file** (supports merge mode to add emergency-contact data without overwriting existing records).
* Each attendant stores: name, surnames, date of birth, and the following optional fields — notes, internal notes, emergency-contact details (name, surnames, relationship, email), inviting person, and incompatibilities.
* **Export attendants to CSV** including their unique message URL, message count, and all standard fields.
* Sort and filter the attendant list by name, message count, notes, or any other available column.
* The attendant list shows at a glance how many messages each person has received and how many have not yet been printed.
* Delete individual attendants, or remove all attendants from a retreat at once.

= Confidential message submission (public form) =

* Each attendant has a **unique, cryptographically secure URL** (based on a random token). Anyone with the link can submit a message without logging in to WordPress.
* The submission form provides a **rich-text editor** (with formatting, images, and copy-paste from Word or Google Docs).
* Writers can attach **images and documents** (PDF, DOCX, and other common types). If a message has multiple non-image files, they are bundled into a ZIP for printing.
* An optional **legal disclaimer** with a configurable acceptance checkbox can be required before submission.
* A simple **arithmetic CAPTCHA** prevents automated submissions. Logged-in WordPress users skip the CAPTCHA.
* The form URL includes the attendant's initials as a suffix for easy identification when sharing links, without exposing the full name.
* **Rate limiting** (20 requests per hour per IP) prevents abuse.

= Secure message access and printing =

* The admin interface **never displays message content** — there is no content-preview panel. This protects confidentiality if a screen is visible to others.
* Authorised users open a message and click **Print**. The plugin decrypts the content on the fly, renders it in a print-ready format with the recipient's name and the sender's name, and sends it to the printer.
* Each print action is recorded in a **print log** (user, timestamp, IP address). The log is visible from the attendant's message list.
* Multiple images in a single message are laid out so they do not split across pages.

= Three-tier permission system =

The plugin uses three access levels, each scoped to specific retreats:

**Plugin Administrators** (WordPress users with the `manage_retreat_plugin` capability, automatically granted to WordPress Administrators):

* Create and delete retreats.
* Manage all attendants and all messages across all retreats.
* Grant or revoke permissions for any retreat.
* Access Global Settings and Privacy & Compliance pages.

**Retreat Managers** (assigned per retreat):

* Full control of their assigned retreat: edit retreat details, manage attendants, access all messages.
* Invite and assign Message Managers to their retreat.
* Cannot access other retreats or global settings.

**Message Managers** (assigned per retreat):

* Read-only access to attendant names for context.
* Can open and print confidential messages for their retreat.
* Cannot edit attendants, retreat details, or permissions.
* All print actions are logged.

= User invitations =

* Invite any email address to become a Retreat Manager or Message Manager for a specific retreat directly from the retreat's **Access Management** tab.
* The invitee receives an email with a secure, time-limited token link.
* If the email address already belongs to a WordPress user, they are granted the role immediately on acceptance. If not, a new WordPress account is created for them.
* Pending invitations can be cancelled at any time. Expired invitations are cleaned up automatically.

= Encryption and data security =

* All message content and file attachments are encrypted with **AES-256-CBC** and authenticated with HMAC-SHA256 before being written to the database or disk.
* Encrypted files are stored **outside the web root**, so they cannot be accessed directly via a browser.
* The encryption key is generated automatically on first activation and stored in the database. An admin notice prompts you to move it to `wp-config.php` by defining the constant `DFXPRL_ENCRYPTION_KEY` for better security. If the constant and the database key ever differ, the plugin detects the mismatch and offers a one-click resolution.
* Every sensitive admin action (permission grants, revocations, invitation events) is written to a **permission audit log**.

= GDPR and privacy compliance =

* **Right to Erasure** (GDPR Article 17): delete all personal data for a specific email address or attendant in one action.
* **Data Portability** (GDPR Article 20): export all personal data associated with an email address as a structured file.
* **IP address anonymisation**: sender IP addresses are automatically anonymised after a configurable retention period (default 30 days). A daily WordPress cron job handles the cleanup.
* **Configurable data retention**: set how long messages and audit log entries are kept before automatic deletion.
* **Spanish privacy law (LOPD-GDD)**: the plugin was designed with Spanish data-protection requirements in mind, in addition to GDPR.
* All settings are found under **Retreats > Privacy & Compliance**.

= Global settings =

Under **Retreats > Global Settings** you can configure:

* Default header and footer blocks for the message submission form (overridable per retreat).
* Default body CSS classes for the submission form page.
* Encryption key management (including the option to remove a database-stored key in favour of the `wp-config.php` constant).

= Internationalisation =

* The plugin ships with a complete **Spanish (es_ES)** translation.
* A `.pot` template file is included so you can add your own language.
* The public submission form uses informal Spanish ("tú") for a friendlier tone.

== Installation ==

1. Upload the `dfx-parish-retreat-letters` folder to `/wp-content/plugins/`.
2. Activate the plugin through the WordPress **Plugins** menu.
3. The plugin automatically creates all required database tables on activation.
4. Navigate to **Retreats** in the WordPress admin sidebar to get started.

= Recommended post-installation steps =

1. Go to **Retreats > Global Settings** and review the default header, footer, and body-class settings for the submission form.
2. Go to **Retreats > Privacy & Compliance** and configure data-retention periods to match your local legal requirements.
3. For production sites, add the following line to `wp-config.php` to store the encryption key outside the database:
   `define( 'DFXPRL_ENCRYPTION_KEY', 'your-long-random-secret-here' );`
   Replace the placeholder with a long, cryptographically random string — for example one generated by `wp_generate_password( 64, true, true )` in the WordPress shell, or by an equivalent secure random generator. The plugin will display a notice reminding you to do this if the key is still in the database.
4. Verify that your site uses HTTPS. The submission form URLs contain sensitive tokens and must be served over a secure connection.

== Frequently Asked Questions ==

= Who can see the content of submitted messages? =

Nobody can read message content by browsing the admin interface. Content is only revealed at print time, and only to users who have the Retreat Manager or Message Manager role for that retreat. Every print is logged.

= How do writers submit messages without a WordPress account? =

Each attendant has a unique, cryptographically secure URL. You share that URL (e.g. by email or WhatsApp) with the people who want to write to the attendant. They open the link in any browser, fill in the form, and submit — no login required.

= What happens to the submission form URL after the retreat? =

The URL remains valid until you delete the attendant or the retreat. If you want to stop accepting new messages, you can delete the attendant's token by deleting and re-adding the attendant, or by deleting the retreat entirely.

= Does the plugin use WordPress Custom Post Types or pages? =

No. All data is stored in custom database tables (prefixed `{prefix}dfxprl_*`, e.g. `wp_dfxprl_retreats`). The public submission form is served directly by the plugin using a rewrite rule — you do not need to create any WordPress page.

= Can I customise the look of the submission form? =

Yes. You can set a custom WordPress block as the header and footer of the form page — globally under **Retreats > Global Settings**, or per retreat on the retreat edit screen. You can also add custom CSS body classes per retreat.

= What file types can writers attach? =

Common image formats (JPG, PNG, GIF, WebP), PDF, DOCX, and other document types supported by your server. The maximum file size is determined by your PHP and server configuration and is shown on the form. If a message contains multiple files and at least one is not an image, a ZIP archive is generated for printing.

= Is the plugin GDPR-compliant? =

Yes. It implements GDPR Articles 17 (Right to Erasure) and 20 (Data Portability), anonymises sender IP addresses after a configurable period, and supports configurable data-retention policies with automatic deletion. All settings are under **Retreats > Privacy & Compliance**.

= Where is the encryption key stored? =

By default it is generated automatically and stored in the WordPress database (`wp_options`). The plugin will display an admin notice recommending that you move it to `wp-config.php` by defining the `DFXPRL_ENCRYPTION_KEY` constant. The wp-config.php approach is safer because the key is then separate from the encrypted data.

= Can I add translations for my language? =

Yes. A `.pot` translation template is included in the `languages/` directory. Create `.po` and `.mo` files for your language and place them in that folder following standard WordPress translation conventions.

## Changelog

### 26.03.22

- Fix: solved JS not loading on the retreat edit page, which prevented the access management feature (user search, grant/revoke permissions, invitations) from working.
- Fix: corrected nonce mismatch that caused all Access Management AJAX calls to fail the security check.
- Fix: normalized all internal identifiers from dfx-prl/dfx_prl to dfxprl for consistency with WordPress plugin repo policy.
- Fix: removed dead code (render_permission_management_section method that was never called).
- Fix: corrected CSS class mismatches between PHP templates and JavaScript event listeners in the Access Management section.
- Fix: admin styles now load correctly on all plugin pages, including the add-retreat and privacy pages.
- Fix: tab buttons in the Access Management section are now visually connected to their content panel.
- Fix: the "Remove Database Key and Use wp-config.php Key" button now works. Script was never output because it was registered via wp_add_inline_script during the admin_notices hook, which fires after wp_print_scripts. Fixed by hooking to admin_enqueue_scripts with a proper inline-only script handle and wp_localize_script for the nonce.
- Fix: the remove-database-key AJAX handler now also accepts the legacy DFX_PARISH_RETREAT_LETTERS_ENCRYPTION_KEY constant, matching the same backward-compatibility logic as the rest of the encryption layer.

### 26.03.21

- Feature: added the extra class 'dfxprl-message-form' to the body tag of the message form screen.

- Feature: retreats now have a field to set extra classes to the body tag of the message form screen, allowing 
  per-retreat customizations.

- Change: Removed CSS customizations from the plugin, as they are not allowed in WordPress plugins repo.

- Fix: solved a few JS errors due to renaming several classes and variables.

### 25.12.10

- Fix: Now always using enqueue functions to add JS or CSS to the different pages.
- Fix: Fixed problem affecting CAPTCHA validation when logged in.
- Fix: Replaced the plugin prefix from dfx-prl to dfxprl to be accepted in WordPress.org repository.
- Fix: Added a few extra parameter validations

### 25.12.09

- Feature: Marked compatibility with WordPress 6.9
- Fix: Fixing some warnings by Plugin Check

### 25.12.05

- Feature: added feature to delete encryption key from the database if there's a key mismatch between the database and 
  the config file.

- Feature: added feature to remove all the attendants and their messages from a retreat.

### 25.11.28

- Fix: if the server wrongly determined that the maximum file size was 0, all file uploads were disallowed.

### 25.11.27

- Fix: accepting "0" as a valid answer for security question calculations.

### 25.11.26

- Fix: adding URL suffix in the invitation message

### 25.11.24

- Feature: add the attendant name in the message placeholder to avoid mistakes when identifying the attentant

### 25.11.23

- Fix: to keep confidentiality, the suffix in URLs will now have the initials, not the full name and surnames

### 25.11.21

- Feature: Adding attendant name and surnames as URL suffix when copying it from the attendant list

### 25.11.20

- Feature: Added global per-retreat message counter.

### 25.10.30

- Fix: sorting indicator in messages count column is now fixed
- Feature: Allow sorting by the notes and internal_notes fields (if they exist)

### 25.10.29

- Feature: Adding new internal notes field, not exportable nor importable.
- Feature: Adding sorting options and filters to the attendant list
- Feature: Adding message templates to be sent to emergency contacts in order to request letters

### 25.10.23

- Feature: Adding three new optional fields for attendants: emergency-contact relationship, inviting-person and incompatibilities.

### 25.10.21

- Feature: Display number of non-printed messages in the attendant list
- Feature: When printing a message, print the recipientname before the sender name
- Feature: Adding Message URL column when exporting to CSV
- Fix: When a message has more than one image, fix the first image size so it fits in the first page
- Fix: If an image or file cannot be uploaded, return an error instead of accepting the partial message with missing files.
- Feature: Show actual server limits.
- Change: Corrected CSS and JS prefix from "dfx" to "dfx-prl" or "dfx_prl". CSS customizations should be adapted

### 25.10.15

- Feature: New notes field for retreat attendants, optional. The field can be enabled or disabled per retreat.
- Feature: The Emergency Contact Surname field is now optional.

### 25.10.8

- Feature: Add place holder in the message form Captcha

### 25.9.30

- Feature: improved error messages on AJAX calls.
- Fix: solved problem with messages when pasted from Microsoft Office, and some style information wrongly got into 
       the message body.

### 25.9.28

- Fix: increased the allowed rate limit to 20 requests per hour, as 3 was too low for real use cases.

### 25.9.26

- Fix: messages with multiple images are now printed correctly, without splitting images in two pages.
- Fix: if a message have multiple attached files, and any of them is not an image, a ZIP file will be generated now. 

### 25.9.24

- Fix: now correctly generating error 404 when accessing messages form page with wrong token
- Fix: solved error that prevented the removal of retreats
- Fix: solved bug with custom headers and footers

### 25.9.18

- Fix: Expanding size of mime_type field so DOCX files can be uploaded

### 25.9.16

- Fix: Correcting several security issues to include plugin in WordPress.org repository

### 25.9.12

- Feature: Added support for custom header, footer, and CSS in letters form page, globally and per-retreat.
- Feature: Added support to grant global Retreat Manager permissions to specific users.
- Feature: New emergency contact email field for attendants.
- Fix: Retreat managers can now edit retreats they manage.
- Fix: Correcting translation loading issue in old WP versions

### 25.9.11

- Fix: Fixing several warnings from WordPress.org
- Fix: Captcha won't show calculations with negative numbers

### 25.9.7

- Feature: disable submit button if legal disclaimer has not been accepted

### 25.9.5

- Feature: added new legal disclaimer fields in letters form
- Feature: now including sender's name when printing a letter

### 25.7.27

- Fix: Correcting formatting in outgoing mail messages
- Fix: Adding missing translations
- Fix: Removed foreign keys from old DB setups

### 25.7.23

- Fix: WordPress coding standards violations- security and best practices improvements
- Fix: Image paste processing in public message submission frontend
- Fix: Skip rate limits for logged-in WordPress users
- Fix: rate limiting for unsuccessful message submissions
- Fix: Change Spanish translations from formal to informal language in public message frontend
- Fix: admin notices auto-hiding issue by removing automatic fadeOut
- Feature: Implement CSV import merge functionality for attendant emergency contact data
- Fix: CSV export pagination issue - handle per_page=-1 correctly
- Fix: redirect issue by handling form submissions on admin_init hook
- Fix: attendant creation error handling to prevent header conflicts

### 25.7.22

- Fix: solved error in database creation and upgrade processes

### 25.7.21 (Foundation Release)

#### Major Features
- Complete three-tier authorization system
- Secure confidential message system using AES-256 encryption
- Advanced attendant management with bulk operations
- User invitation system with secure token authentication

#### Security & Privacy Enhancements
- Full GDPR compliance with automated data retention policies
- Enhanced encryption for personal information and secure file storage
- Print-only message access with comprehensive audit trails
- Forensic-level audit logging
- IP address anonymization
- Foundation-level security implementation with modern PHP practices

#### Architecture & Development
- Core plugin architecture using OOP and singleton patterns
- Database schema optimization and integrated migration system
- Full compliance with WordPress coding standards

#### Features & Improvements
- Retreat-attendant association management
- CSV import/export with data validation
- Basic retreat and attendant management
- Responsive admin interface with modern UX/UI

#### Internationalization
- Complete i18n support, including Spanish translations
---

**DFX Parish Retreat Letters** - Enterprise-grade retreat management for the modern parish.
