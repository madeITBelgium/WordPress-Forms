=== Forms ===
Contributors: madeit
Donate link: http://www.madeit.be/donate/
Tags: contact, form, contact form, feedback, email, captcha, form submit, newsletter
Requires at least: 5.0
Tested up to: 6.8.0
Requires PHP: 8.0
Stable tag: 2.9.0
License: GNU GPL v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Build easy and flexible forms with Forms.

== Description ==

Forms is an easy form manager that lets you manage all your cool forms. Creating your own contact form or newsletter subscriber is easy.

= Docs & Support =

You can find [docs](https://github.com/madeITBelgium/WordPress-Forms), [FAQ](https://github.com/madeITBelgium/WordPress-Forms) and more detailed information about Forms on [madeit.be](https://www.madeit.be/). If you were unable to find the answer to your question on the FAQ or in any of the documentation, you should check the [support forum](http://wordpress.org/support/plugin/forms-by-made-it) on WordPress.org or [GitHub](https://github.com/madeITBelgium/WordPress-Forms). If you can't locate any topics that pertain to your particular issue, post a new topic for it.

= Recommended Plugins =

The following plugins are working with Forms:


= Translations =

You can [translate Forms](https://www.madeit.be/forms-plugin) on [__translate.wordpress.org__](https://translate.wordpress.org/projects/wp-plugins/forms-by-made-it).

== Installation ==

1. Upload the entire `forms-by-madeit` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.

You will find 'Forms' menu in your WordPress admin panel.

For basic usage, you can also have a look at the [plugin homepage](https://github.com/madeITBelgium/WordPress-Forms).

== Changelog ==
= 2.9.0 =
* Added Webhook support

= 2.8.3 =
* Fix default input

= 2.8.2 =
* Bump version

= 2.8.1 =
* Bug fix in form settings

= 2.8.0 =
* Better spam prevention
* Active Campaign integration
* Security fix: CVE-2024-51791
* Bug fixes

= 2.7.0 =
* Support file Upload
* Improved error messages
* Bug fixes

= 2.6.0 =
* Add individual Radio input field
* Add settings Meta box to form.
* Add option to limit submits. (Cookie based)

= 2.5.0 =
* Nummeric field support

= 2.4.1 =
* Blocks improvments

= 2.4.0 =
* Blocks improvments

= 2.3.0 =
* Google recaptcha V3

= 2.2.0 =
* Quiz support

= 2.1.0 =
* Mailerlite integration
* Klaviyo integration
* Bug fix in Ajax support
* Spam filters

= 2.0.2 =
* Bug fix migration old data to new post types
* Bug fix in loading older forms in front-end

= 2.0.1 =
* 2.0.0 - Release

= 2.0.0 - Beta =
* Database rewrite - Using Post Types instead of custom datbase tables.
* Improved interface

= 1.12.1 - 1.12.4 =
* Fix XSS bug in Title field (Credits to: Shubhangi Dawkhar & WPScan)
* Fix delete form bug

= 1.12.0 =
* Email-service.be integration

= 1.11.0 =
* Send In Blue integration

= 1.10.1 =
* Fix bug in WP CLI

= 1.10.0 =
* Improved mailchimp integration (Api V3)
* Fixed mailchimp dubble opt in
* Added ajax support (add ajax="yes" to form shortcode)
* Fixed message translations
* Added option to mark submitted form as read by opening the e-mail.
* WP 5.5 Support

= 1.9.0 =
* WP 5.4 Support
* Added radio button

= 1.8.1 =
* Fix bug that crash full wordpress installation at some servers
* Improve database queries and code base.

= 1.8.0 =
* Add option to export results to CSV

= 1.7.0 =
* Add API function wp_form_api_save_input($id, $data);

= 1.6.3 =
* Bug fixes
* HTML event added
* Redirect to specific page event added

= 1.6.0 =
* Bug fixes
* Mailpoet integration

= 1.5.1 =
* Bug fixes

= 1.5 =
* Invisible reCaptcha integration
* Security fixes
* New form editing save fix
* Translation improvments

= 1.4 =
* Checkbox added
* File download
* Email as HTML checkbox

= 1.3 =
* Google analytics Event integration

= 1.2.1 =
* Bug fix database init

= 1.2 =
* Bug fixes
* Mailchimp integration

= 1.1 =
* Small bug fixes

= 1.0 =
* List of all Forms
* Add Text, Email, URL, Tel, Number, Dropdown, textarea fields and a submit button. 
* Form validation
* Custom actions. (Send email)
* List all submitted forms data
* Label to indicate there is a new Form submitted.
