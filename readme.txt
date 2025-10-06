=== Restrict User Access - Ultimate Membership & Content Protection ===
Contributors: intoxstudio, devinstitute, keraweb, freemius
Donate link: #
Tags: content-restriction, membership, access-control, capabilities, bbpress
Requires at least: 5.8
Requires PHP: 7.2
Tested up to: 6.8
Stable tag: 2.8
License: GPLv3

Create Access Levels and restrict any post, page, category, etc. Supports bbPress, BuddyPress, WooCommerce, WPML, and more.

== Description ==

**Restrict User Access is a fast and simple Membership Plugin for WordPress. Restrict your content in minutes, NOT hours.**

Quickly set up a paid membership site where your users can get different levels such as Platinum, Gold, or Free. Then, grant those levels when a user purchases a product in WooCommerce.

###👥 Unlimited Access Levels

Users can have multiple levels, and you control how long memberships should last. When unauthorized users try to access restricted content, you can redirect them to another URL or display a teaser.

###⚡ Level Membership Automations

Automatically add levels to your users based on something they do (Triggers) or something they are (Traits):

* User Registration
* User Roles
* Logged-in or Guests
* WooCommerce Purchases
* Easy Digital Downloads Purchases
* BuddyPress Member Types
* GiveWP Donations

###🔒 Contextual Content Protection

Prevent unauthorized users from visiting your posts, pages, or categories. You can even combine the conditions: protect all posts tagged "Premium" written by a select author.

The following Access Conditions are available out of the box:

* Posts, Pages & Custom Post Types
* Content with Tags, Categories, or Custom Taxonomies
* Content written by select Authors
* Page Templates
* Blog Page & Post Type Archives
* Author Archives
* Taxonomy Archives
* Front Page, Search Results, 404 Not Found Page
* bbPress Profiles, Forums & Topics
* BuddyPress Profile Sections
* Languages (Polylang, qTranslate X, TranslatePress, Transposh, Weglot, WPML)
* Pods Pages

Note that Access Conditions do not apply to content displayed in lists.

###✅ Grant & Deny Capabilities

The easy-to-use WordPress User Manager gives you full control over the capabilities the members should or shouldn't have. Access Level Capabilities will override the permissions set by roles or other plugins.

###👁️ Hide Admin Bar & Nav Menu Visibility

Disable the admin bar for select levels and control what menu items members can see. You can even hide any widget area created with [Content Aware Sidebars](https://dev.institute/wordpress-sidebars/?utm_source=readme&utm_medium=referral&utm_content=section&utm_campaign=rua)

###🤖 Restrict Content from Other Plugins

Restrict User Access autodetects Custom Post Types and Taxonomies created by any plugin or theme. Built-in support for some of the most popular WordPress plugins means that you e.g. can restrict access to bbPress forums or multilingual content.

* bbPress
* BuddyPress
* Easy Digital Downloads
* Pods
* Polylang
* TranslatePress
* WooCommerce
* Weglot
* WPML
* and more ...

###🛡️ WordPress Security Enhancements

* **WP REST API Content Protection**
Enforces PoLA to minimize attack surfaces and stop threat actors from harvesting your data
* **How to display content in lists**
Display excerpts only or hide content when post types are displayed in blog, archives, search results, lists, etc.

###📑 Restrict Content with Shortcodes

Fine-tune content visibility in your posts or pages by adding simple shortcodes:

`
[restrict level="platinum"]
This content can only be seen by users with Platinum level or above.
[/restrict]

[restrict level="!platinum"]
This content can only be seen by users without Platinum level or above.
[/restrict]

[restrict role="editor,contributor" page="1"]
This content can only be seen by editors and contributors.
Other users will see content from page with ID 1.
[/restrict]

[login-form]
`

###👋 Developer-friendly API

Restrict User Access makes it super easy for developers to programmatically customize WordPress access control by adding a few lines of code to theme templates.

####Example - Add level to current user

`
rua_get_user()->add_level($level_id);
`

####Example - Check if current user has an active level membership

`
if(rua_get_user()->has_level($level_id)) {
    //show restricted content
} else {
    //show content if unauthorized
}
`

[View full RUA PHP API documentation here.](https://dev.institute/docs/restrict-user-access/developer-api/?utm_source=readme&utm_medium=referral&utm_content=section&utm_campaign=rua)

###🎛️ Premium Add-ons for Restrict User Access

Complete your WordPress membership site with these powerful extensions

* **[ACF Restriction](https://dev.institute/products/category/restrict-user-access/?utm_source=readme&utm_medium=referral&utm_content=acf&utm_campaign=rua)**
Restrict content that contain data from Advanced Custom Fields plugin
* **[Date Restriction](https://dev.institute/products/category/restrict-user-access/?utm_source=readme&utm_medium=referral&utm_content=date&utm_campaign=rua)**
Restrict content based on the time it was published
* **[Meta Box Restriction](https://dev.institute/products/category/restrict-user-access/?utm_source=readme&utm_medium=referral&utm_content=metabox&utm_campaign=rua)**
Restrict content that contain data from Meta Box plugin
* **[Timelock](https://dev.institute/products/category/restrict-user-access/?utm_source=readme&utm_medium=referral&utm_content=timelock&utm_campaign=rua)**
Determine when to enable or disable select Access Conditions
* **[URL Restriction](https://dev.institute/products/category/restrict-user-access/?utm_source=readme&utm_medium=referral&utm_content=url&utm_campaign=rua)**
Restrict content based on the WordPress URL, with wildcard support
* **[Visibility Control](https://dev.institute/products/category/restrict-user-access/?utm_source=readme&utm_medium=referral&utm_content=visibility&utm_campaign=rua)**
Hide content from blog, search results, archives, custom lists, WP REST API, and more

== Installation ==

1. Upload the full plugin directory to your `/wp-content/plugins/` directory or install the plugin through `Plugins` in the administration
1. Activate the plugin through `Plugins` in the administration
1. Have fun creating your first Access Level under the menu *User Access > Add New*

== Frequently Asked Questions ==

= How do I prevent admin lockout? =

Restrict User Access has built-in lockout prevention. All administrators will by default have access to all content regardless of the Access Levels you create.

If the plugin is deactivated, any restricted content will become accessible to everyone again; Restrict User Access does not permanently alter Roles or Capabilities in any way.

= How do I restrict some content? =

1. Go to User Access > Add New
1. Click on the "New condition group" dropdown to add a condition
1. Click on the created input field and select the content you want to restrict
1. Go to the Members tab to add users who should have access the restricted content
1. Go to the Options tab to set the Non-Member Action and other options
1. Give your new level a descriptive title and save it

**Tips**
In order to restrict a context, e.g. "All Posts with Category X", simply select a new type of content from the dropdown below the **AND** label and repeat Step 3.

= I added a level to a user, but it can still access other content? =

When you use Access Conditions to restrict some pages, only members of this level will be able to visit those pages, but they can still visit other pages too.

You can change this behavior from the Options tab by toggling "Deny Access to Unprotected Content" to ON.

With this option enabled, members can only visit the pages selected as Access Conditions.

To prevent lockout, Administrators will have access to all content regardless of your levels.

= Restricted content is still displayed in blog, archives, search results, etc? =

By default, Access Conditions do not apply to items in archive pages, search results, widgets, WP REST API, or custom lists.

[Learn more about how to completely hide content here.](https://dev.institute/docs/restrict-user-access/faq/restricted-content-not-hidden/?utm_source=readme&utm_medium=referral&utm_content=faq&utm_campaign=rua)

= Restricted file is still accessible with deep link? =

Restrict User Access does currently not support restricting deep links to files, only attachment urls.

= User still able to edit restricted content in Admin Dashboard? =

Capabilities and Access Conditions serve different purposes and are not combined. Access Conditions are applied only to the frontend, while capabilities work throughout the site (both Admin Dashboard and frontend).

= How can I report security bugs? =

You can report security bugs through the Patchstack Vulnerability Disclosure Program. The Patchstack team help validate, triage and handle any security vulnerabilities. [Report a security vulnerability.](https://patchstack.com/database/vdp/restrict-user-access)

= I have other questions, can you help? =

Of course! Check out the links below:

* [Getting Started with Restrict User Access](https://dev.institute/docs/restrict-user-access/getting-started/?utm_source=readme&utm_medium=referral&utm_content=faq&utm_campaign=rua)
* [Documentation and FAQ](https://dev.institute/docs/restrict-user-access/?utm_source=readme&utm_medium=referral&utm_content=faq&utm_campaign=rua)
* [Support Forums](https://wordpress.org/support/plugin/restrict-user-access)

== Screenshots ==

1. Simple Access Levels Overview
2. Easy-to-use Access Conditions
3. Capability Manager for Access Level

== Upgrade Notice ==

Plugin data will be updated automatically. It is strongly recommended to take a backup of your site.

== Changelog ==

[Follow development and see all changes on GitHub](https://github.com/intoxstudio/restrict-user-access)

####Highlights

= 2.8 =

* [new] member trigger - user registration
* [new] handle login redirect for non-admin access
* [new] performance and ui improvements
* [new] wordpress 6.8 support
* [new] minimum wordpress version 5.8
* [new] minimum php version 7.2
* [updated] freemius sdk
* [updated] wp-content-aware-engine library
* [fixed] prevent other plugins from erroneously manipulating membership queries

= 2.7.1 =

* [new] performance improvements

= 2.7 =

* [new] ui and performance improvements
* [new] wordpress 6.5 support
* [new] minimum wordpress version 5.5
* [new] minimum php version 7.1
* [fixed] memberships now removed on user deletion
* [fixed] improved compatibility with wpml plugin
* [updated] wp-content-aware-engine library
* [updated] freemius sdk

= 2.6.1 =

* [new] ui and performance improvements
* [updated] freemius sdk
* [fixed] timezone discrepancies in level memberships
* [fixed] conflict with other plugins erroneously manipulating membership queries

= 2.6 =

* [new] rest api content protection
* [new] control how content is displayed in lists
* [updated] buddypress 12 compatibility
* [fixed] compatibility with polylang and wpml
* [fixed] xss vulnerability when editing levels

= 2.5 =

* [new] admin ability to extend, search, and sort memberships
* [new] member trigger - givewp donation
* [new] wp multisite network support
* [new] greatly improved membership data storage
* [new] wordpress 6.4 support
* [new] minimum wordpress version 5.1
* [new] minimum php version 7.0
* [new] ui and performance improvements
* [updated] wp-content-aware-engine library
* [updated] freemius sdk

See changelog.txt for previous changes.