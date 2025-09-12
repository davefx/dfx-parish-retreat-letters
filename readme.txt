=== DFX Parish Retreat Letters ===
Contributors: davefx
Tags: parish, retreat, letters, confidential, GDPR
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 25.9.12
Requires PHP: 7.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Manage parish retreat programs with confidentiality, GDPR compliance, and user management.

# DFX Parish Retreat Letters

**Enterprise-Grade Retreat Management System for WordPress**

A comprehensive, security-focused WordPress plugin designed for parishes and organizations to manage retreat programs with complete confidentiality, GDPR compliance, and advanced user management capabilities.

## Description

DFX Parish Retreat Letters is a professional-grade retreat management system that provides churches and organizations with enterprise-level security and functionality for managing retreat programs. The plugin features a complete retreat lifecycle management system, secure confidential messaging with military-grade encryption, and a sophisticated three-tier authorization system designed to protect sensitive spiritual and personal communications.

## Core Features

### Comprehensive Retreat Management System
- **Complete Retreat Lifecycle Management**: Create, edit, organize, and track retreats from planning to completion
- **Advanced Date Management**: Flexible scheduling system with date tracking and conflict resolution
- **Retreat Status Tracking**: Monitor retreat progress through customizable status workflows
- **Multi-Retreat Organization**: Handle multiple concurrent retreats with separate management streams

### Advanced Attendant Management
- **Complete Registration System**: Comprehensive attendant registration with personal information tracking
- **Attendant-Retreat Associations**: Flexible assignment system linking attendants to multiple retreats
- **Bulk Operations**: Import/export attendant data via CSV with data validation
- **Personal Information Security**: Encrypted storage of sensitive personal data with GDPR compliance

### Secure Confidential Message System
- **Privacy-Compliant Messaging**: Full adherence to Spanish privacy laws (LOPD-GDD) and European GDPR regulations
- **Cryptographically Secure URLs**: Unique, unguessable URLs generated for each attendant using cryptographic tokens
- **Military-Grade Encryption**: AES-256-CBC encryption for all message content and file attachments
- **Public Submission Interface**: Rich text editor with file upload capabilities for attendant message submission
- **Print-Only Backend Access**: Administrative interface shows no content preview - messages only accessible via secure printing
- **Complete Audit Trails**: Comprehensive logging of all message operations, access attempts, and administrative actions
- **Secure File Storage**: Encrypted file storage outside web directory with access control protection

### Three-Tier Authorization System
- **Plugin Administrators**: Global access to all functionality with complete system control
- **Retreat Managers**: Full control over assigned retreats with permission delegation capabilities
- **Message Managers**: Specialized message-only access with read-only attendant information
- **User Invitation System**: Email-based invitations with secure token authentication and role assignment
- **Hierarchical Permissions**: Sophisticated permission inheritance with granular access control
- **Complete Audit Logging**: Track all administrative actions with user attribution and timestamps

## Enterprise Security & Compliance

### Advanced Security Features
- **Enterprise-Grade Encryption**: AES-256-CBC encryption for all sensitive data storage and transmission
- **GDPR Full Compliance**: Complete implementation of European General Data Protection Regulation requirements
- **Spanish Privacy Law Compliance**: Full adherence to LOPD-GDD (Ley Orgánica de Protección de Datos)
- **Complete Input Validation**: Comprehensive sanitization and validation preventing SQL injection and XSS attacks
- **CSRF Protection**: Cross-Site Request Forgery protection with WordPress nonce validation throughout the system
- **Rate Limiting**: Advanced abuse protection with intelligent rate limiting on sensitive operations
- **Comprehensive Audit Logging**: Complete audit trails for all administrative actions with forensic-level detail

### Privacy & Data Protection
- **Right to be Forgotten**: Complete GDPR Article 17 implementation with secure data erasure
- **Data Minimization**: Collect and store only necessary data with configurable retention policies
- **IP Address Anonymization**: Automatic IP address anonymization after 30-day retention period
- **Automated Data Cleanup**: Configurable data retention policies with automatic secure deletion
- **Consent Management**: Integrated consent tracking and privacy policy compliance
- **Data Export/Portability**: GDPR Article 20 compliance with secure data export functionality

## Technical Architecture

### WordPress Integration Excellence
- **Custom Post Types**: Seamless integration with WordPress content management system
- **Custom Database Tables**: Optimized database schema for retreat-specific data with proper indexing
- **WordPress Capability System**: Deep integration with WordPress user roles and capabilities
- **Email System Integration**: Native WordPress email system for secure invitations and notifications
- **Internationalization Ready**: Complete i18n support with professional Spanish translations
- **Responsive Admin Interface**: Modern, mobile-friendly administration interface with intuitive UX/UI

### Modern Development Standards
- **Object-Oriented Architecture**: Clean, maintainable OOP design with singleton patterns for optimal performance
- **Enterprise Design Patterns**: Implementation of proven design patterns for scalability and maintainability
- **WordPress Coding Standards**: Full compliance with WordPress coding standards and best practices
- **Modern PHP Requirements**: Built for PHP 7.4+ with support for latest PHP features and security standards
- **Database Schema Management**: Automated database migrations with version control and rollback capabilities
- **Security-First Development**: Security considerations integrated into every aspect of the codebase

## System Requirements

### Minimum Requirements
- **WordPress**: 5.0 or higher (WordPress 6.0+ recommended for optimal performance)
- **PHP**: 7.4 or higher (PHP 8.0+ recommended for enhanced security and performance)
- **MySQL**: 5.6 or higher (MySQL 8.0+ or MariaDB 10.3+ recommended)
- **Memory**: 256MB PHP memory limit minimum (512MB+ recommended for bulk operations)
- **Disk Space**: 50MB minimum for plugin files and database storage

### Security Recommendations
- **HTTPS Required**: SSL/TLS encryption mandatory for production environments
- **Server Configuration**: Secure server configuration with proper file permissions
- **WordPress Updates**: Keep WordPress core, themes, and plugins updated
- **Database Security**: Secure database configuration with strong passwords and restricted access
- **Backup Strategy**: Regular automated backups of database and uploaded files

## Installation & Setup

### Quick Installation
1. **Download Plugin**: Download the latest release from the official repository
2. **Upload to WordPress**: Upload the `dfx-parish-retreat-letters` folder to `/wp-content/plugins/`
3. **Activate Plugin**: Activate through the WordPress 'Plugins' admin menu
4. **Database Setup**: Plugin automatically creates required database tables on activation
5. **Initial Configuration**: Access plugin settings to configure basic parameters

### Post-Installation Setup

#### 1. Administrator Configuration
- Navigate to **DFX Retreat Letters** in WordPress admin menu
- Configure global plugin settings and security parameters
- Set up email templates for invitations and notifications
- Configure data retention and privacy compliance settings

#### 2. Permission System Setup
- Create initial Plugin Administrators through WordPress user management
- Set up Retreat Manager roles for retreat-specific administration
- Configure Message Manager roles for confidential message access
- Test invitation system with secure token generation

#### 3. Security Configuration
- Verify HTTPS is properly configured and enforced
- Configure secure file storage directory outside web root
- Set up backup procedures for encrypted data
- Review and configure rate limiting settings

#### 4. GDPR Compliance Setup
- Configure data retention policies according to local regulations
- Set up privacy policy integration
- Configure consent management settings
- Test data export and erasure functionality

## Usage Guide

### For Plugin Administrators

#### Creating and Managing Retreats
- **Retreat Creation**: Create new retreats with comprehensive details including dates, descriptions, and capacity limits
- **Retreat Organization**: Organize retreats by categories, dates, and status for efficient management
- **Attendant Registration**: Set up registration forms and manage attendant information securely
- **Message URL Configuration**: Generate cryptographically secure URLs for confidential message submission

#### User Permission Management
- **Invite Users**: Send secure email invitations with role-specific access tokens
- **Assign Roles**: Delegate specific retreat management or message access permissions
- **Monitor Access**: Review user activity and access logs through comprehensive audit trails
- **Revoke Access**: Instantly revoke user permissions with complete session invalidation

### For Retreat Managers

#### Managing Assigned Retreats
- **Retreat Administration**: Full control over assigned retreats including attendant management
- **Attendant Operations**: Add, edit, and manage attendant information with bulk import/export capabilities
- **Message Management**: Access confidential messages through secure print-only interface
- **Permission Delegation**: Invite and assign Message Managers for specific retreat message handling

#### Advanced Attendant Features
- **CSV Import/Export**: Bulk attendant operations with data validation and error reporting
- **Personal Information Security**: View and manage encrypted personal data with audit logging
- **Communication Tracking**: Monitor message submission and printing activity

### For Message Managers

#### Confidential Message Operations
- **Secure Message Access**: View submitted confidential messages through print-only interface
- **Print Tracking**: All message printing activities are logged with timestamp and user attribution
- **Attendant Information**: Read-only access to attendant details for message context
- **Audit Compliance**: Complete audit trail of all message access and printing operations

#### Privacy Protection Features
- **No Content Preview**: Administrative interface prevents content display for maximum privacy
- **Secure Printing**: Encrypted message content only accessible through controlled printing system
- **Access Logging**: All message access attempts logged for security and compliance

## Developer Information

### Plugin Architecture

#### Database Schema
The plugin creates and manages several custom database tables:

- **`{prefix}_dfx_prl_retreats`**: Core retreat information with status tracking
- **`{prefix}_dfx_prl_attendants`**: Encrypted attendant personal information
- **`{prefix}_dfx_prl_confidential_messages`**: AES-256 encrypted message content
- **`{prefix}_dfx_prl_message_files`**: Encrypted file attachments with secure storage
- **`{prefix}_dfx_prl_message_print_log`**: Comprehensive audit trail for message printing
- **`{prefix}_dfx_prl_retreat_permissions`**: Three-tier authorization system data
- **`{prefix}_dfx_prl_retreat_invitations`**: Secure invitation token management

#### Core Classes and Design Patterns

```php
// Main plugin singleton
DFX_Parish_Retreat_Letters::get_instance()

// Security and encryption utilities
DFX_Parish_Retreat_Letters_Security::get_instance()

// GDPR compliance management
DFX_Parish_Retreat_Letters_GDPR::get_instance()

// Three-tier permission system
DFX_Parish_Retreat_Letters_Permissions::get_instance()

// Database operations
DFX_Parish_Retreat_Letters_Database::get_instance()
```

#### Security Implementation Details

##### Encryption Methods
- **Algorithm**: AES-256-CBC with HMAC-SHA256 authentication
- **Key Management**: WordPress-integrated key derivation with salt rotation
- **File Encryption**: Separate encryption keys for file contents and metadata

##### Permission System
```php
// Check user permissions for retreat access
$permissions->can_user_manage_retreat($user_id, $retreat_id)

// Verify message manager access
$permissions->can_user_access_messages($user_id, $retreat_id)

// Audit logging for all permission checks
$audit->log_permission_check($user_id, $action, $resource_id)
```

#### Extension Points for Developers

##### Custom Hooks and Filters
```php
// Customize encryption parameters
add_filter('dfx_prl_retreat_letters_encryption_config', $callback);

// Extend GDPR compliance features
add_action('dfx_prl_retreat_letters_gdpr_data_export', $callback);

// Customize permission logic
add_filter('dfx_prl_retreat_letters_user_permissions', $callback);

// Extend audit logging
add_action('dfx_prl_retreat_letters_audit_log', $callback);
```

##### API Endpoints
The plugin provides secure REST API endpoints for:
- Retreat management operations
- Attendant data handling (with encryption)
- Secure message submission
- Permission verification
- Audit log access (admin only)

### Security Best Practices Implemented

- **Input Validation**: Comprehensive sanitization using WordPress standards
- **SQL Injection Prevention**: Prepared statements and WordPress database abstraction
- **Cross-Site Scripting (XSS) Protection**: Output escaping and Content Security Policy headers
- **Cross-Site Request Forgery (CSRF) Protection**: WordPress nonce validation throughout
- **Data Encryption**: AES-256-CBC for all sensitive data at rest
- **Secure Communications**: HTTPS enforcement and secure token generation
- **Audit Logging**: Complete forensic-level activity logging

## Plugin Structure

```
dfx-parish-retreat-letters/
├── dfx-parish-retreat-letters.php    # Main plugin file and initialization
├── includes/                         # Core plugin classes
│   ├── class-dfx-parish-retreat-letters.php    # Main plugin singleton
│   ├── class-admin.php                          # Administrative interface
│   ├── class-database.php                      # Database management
│   ├── class-security.php                      # Encryption and security
│   ├── class-permissions.php                   # Three-tier authorization
│   ├── class-retreat.php                       # Retreat management
│   ├── class-attendant.php                     # Attendant operations
│   ├── class-confidential-message.php          # Secure messaging
│   ├── class-message-file.php                  # Encrypted file handling
│   ├── class-print-log.php                     # Print audit logging
│   ├── class-gdpr.php                          # GDPR compliance
│   ├── class-invitations.php                   # User invitation system
│   ├── class-i18n.php                          # Internationalization
│   └── admin.js                                # Admin interface scripts
├── languages/                        # Translation files
│   ├── dfx-parish-retreat-letters.pot          # Translation template
│   ├── dfx-parish-retreat-letters-es_ES.po     # Spanish translation
│   └── dfx-parish-retreat-letters-es_ES.mo     # Compiled Spanish translation
├── uninstall.php                     # Clean uninstallation procedures
├── LICENSE                           # GPL v3 license
└── README.md                         # This comprehensive documentation
```

## Internationalization

The plugin is fully translation-ready and includes professional translations:

- **English (en_US)**: Default language with complete interface coverage
- **Spanish (es_ES)**: Professional translation for Spanish-speaking parishes
- **Translation Template**: Complete `.pot` file for additional language translations

### Adding New Languages
1. Use the provided `dfx-parish-retreat-letters.pot` template
2. Create new `.po` and `.mo` files for your target language
3. Place translation files in the `languages/` directory
4. Follow WordPress translation standards and guidelines

## License

This plugin is licensed under the **GNU General Public License v3.0 or later**.

- **Freedom to Use**: Use the plugin for any purpose, including commercial applications
- **Freedom to Study**: Access to complete source code and documentation
- **Freedom to Modify**: Modify the plugin to meet your specific requirements
- **Freedom to Distribute**: Share the plugin with others under the same license terms

For complete license terms, see [LICENSE](LICENSE) file or visit [GNU GPL v3](https://www.gnu.org/licenses/gpl-3.0.html).

## Author & Support

**David Marín Carreño (DaveFX)**
- **Website**: [davefx.com](https://davefx.com)
- **GitHub Profile**: [@davefx](https://github.com/davefx)
- **Project Repository**: [dfx-parish-retreat-letters](https://github.com/davefx/dfx-parish-retreat-letters)
- **Issue Tracking**: Report bugs and feature requests through GitHub Issues
- **Documentation**: Complete documentation available in this README

### Contributing
Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Submit a pull request with detailed description
4. Follow WordPress coding standards
5. Include appropriate tests and documentation

## Changelog

### 25.9.12

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
