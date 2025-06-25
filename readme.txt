=== SpamXpert - Advanced Anti-Spam Protection ===
Contributors: spamxpert
Donate link: https://spamxpert.com/donate
Tags: spam, anti-spam, honeypot, security, protection
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Multi-layered anti-spam solution with honeypot traps, time-based checks, and deep integration with WordPress forms.

== Description ==

SpamXpert is a powerful, lightweight anti-spam plugin that protects your WordPress site from spam submissions without annoying CAPTCHAs. It uses multiple detection methods to stop bots while remaining invisible to legitimate users.

**Key Features:**

* **Dynamic Honeypot Fields** - Randomized invisible fields that trap spam bots
* **Time-Based Detection** - Blocks forms submitted too quickly (bots)
* **Zero User Friction** - No CAPTCHAs or puzzles for real users
* **WordPress Core Integration** - Protects login, registration, and comment forms
* **Detailed Logging** - Track and analyze blocked spam attempts
* **Lightweight** - Less than 5KB of frontend assets
* **GDPR Compliant** - No external API calls or data sharing

**Protected Forms:**

* WordPress Login Forms
* WordPress Registration Forms
* WordPress Comment Forms
* More integrations coming soon!

**How It Works:**

1. Adds invisible honeypot fields to forms that only bots will fill
2. Tracks form submission timing to detect bot behavior
3. Validates submissions server-side for maximum security
4. Logs spam attempts for analysis and reporting

== Installation ==

1. Upload the `spamxpert` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to SpamXpert → Settings to configure the plugin
4. That's it! Your forms are now protected

== Frequently Asked Questions ==

= Will this slow down my website? =

No! SpamXpert is designed to be extremely lightweight. It adds less than 5KB of assets and uses efficient validation methods.

= Does it work with caching plugins? =

Yes, SpamXpert is fully compatible with all major caching plugins.

= Can legitimate users still submit forms? =

Absolutely! SpamXpert is invisible to real users. They won't see any extra fields or challenges.

= How do I know it's working? =

Check the SpamXpert dashboard to see blocked spam attempts and statistics.

= Is it GDPR compliant? =

Yes, SpamXpert doesn't make any external API calls or share data with third parties.

== Screenshots ==

1. SpamXpert Dashboard - See your spam protection statistics at a glance
2. Settings Page - Easy configuration options
3. Spam Logs - Detailed logging of blocked attempts
4. Protected Login Form - Invisible protection in action

== Changelog ==

= 1.0.0 =
* Initial release
* Dynamic honeypot field protection
* Time-based spam detection
* WordPress core forms integration (login, registration, comments)
* Comprehensive logging system
* Admin dashboard with statistics
* Debug mode for testing

== Upgrade Notice ==

= 1.0.0 =
Initial release of SpamXpert. Upgrade from manual spam management to automated protection!

== Additional Info ==

**Development**

SpamXpert is actively developed and maintained. We welcome contributions and feedback!

* [Report bugs](https://spamxpert.com/support)
* [Request features](https://spamxpert.com/features)
* [Documentation](https://spamxpert.com/docs)

**Pro Version Coming Soon**

The Pro version will include:
* AI-powered spam detection
* Advanced CAPTCHA options
* IP reputation checking
* Geo-blocking
* Priority support
* And much more!

Stay tuned for updates! 