=== Restrict User Access - Membership Plugin with Force ===
Contributors: intoxstudio, devinstitute, keraweb, freemius
Donate link: #
Tags: restrict content, membership, access control, capabilities, members, bbpress, buddypress
Requires at least: 5.0
Requires PHP: 5.6
Tested up to: 5.9
Stable tag: 2.2.2
License: GPLv3

Create Access Levels and restrict any post, page, category, etc. Supports bbPress, BuddyPress, WooCommerce, WPML, and more.

== Description ==

**Restrict User Access is a fast and simple Membership Plugin for WordPress. Restrict your content in minutes, NOT hours.**

Quickly set up a membership site where your users can get different levels such as Platinum, Gold, or Free. Then, grant thoses levels when a user purchases a product in WooCommerce.

###Unlimited Access Levels

Users can have multiple access levels, and you control how long memberships should last. When an unauthorized user tries to access restricted content, you can redirect them to another URL or display a teaser.

###Level Membership Automations [NEW]

Automatically add levels to your users based on something they do (Triggers) or something they are (Traits).

* When a user purchases a WooCommerce product - get paid for memberships!
* When their Role changes
* Whether they are Logged-in or a Guest

###Contextual Content Protection

Restrict access to your posts, pages, or categories. You can even combine the conditions: protect all posts tagged "Premium" written by a select author. 

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
* Languages (WPML, Polylang, qTranslate X, TranslatePress, Transposh)
* Pods Pages

###Restrict Content from Other Plugins

Restrict User Access autodetects Custom Post Types and Taxonomies created by any plugin or theme. Built-in support for some of the most popular WordPress plugins means that you e.g. can restrict access to bbPress forums or multilingual content.

* bbPress
* BuddyPress
* Easy Digital Downloads
* qTranslate X
* Pods
* Polylang
* TranslatePress
* Transposh Translation Filter
* WooCommerce
* WPML

###Permit & Deny Capabilities

When creating Access Levels, you also get full control over the capabilities the members should or shouldn't have. Access Level Capabilities will override the capabilities set by roles or other plugins.

###Hide Widget Areas & Nav Menus

Completely hide navigation menu items or Widget Areas created with [Content Aware Sidebars](https://dev.institute/wordpress-sidebars/) from users without select level memberships.

###Restrict Content with Shortcodes

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

###Developer-friendly API

Restrict User Access makes it super easy for developers to programatically customize WordPress access control by adding a few lines of code to theme templates.

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

 [View full documentation](https://dev.institute/docs/restrict-user-access/developer-api/)

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

You can choose to negate conditions, meaning that if you negate the group "All posts with Category X", the level will get exclusive access to all content but that.

= I added a Level to a user, but it can still see other content? =

When you create an Access Level to restrict some content, only members of this level will be able to see that content, but they can still access other content too.

You can change this behavior from the Options tab by toggling "Can Access Unrestricted Content" to OFF.

With this option disabled, members can only access the content that has been restricted for this level.

To prevent lockout, Administrators will have access to all content regardless of your levels.

= Restricted content is still being displayed on archive pages or in widgets? =

By default, Restrict User Access will not hide single items from archive pages, search results, widgets or custom lists.

[Check out the Visibility Control add-on](https://dev.institute/products/category/restrict-user-access/)

= Restricted file is still accessible with deep link? =

Restrict User Access does currently not support restricting deep links to files, only attachment urls.

= User still able to edit restricted content in Admin Dashboard? =

Capabilities and Access Conditions serve different purposes and are not combined. Access Conditions are applied only to the frontend, while capabilities work throughout the site (both Admin Dashboard and frontend).

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

== Changelog ==

[Follow development and see all changes on GitHub](https://github.com/intoxstudio/restrict-user-access)

####Highlights

= 2.2.2 =

* [new] wordpress 5.9 support
* [updated] option to fully use role synchronization again
* [updated] freemius sdk

= 2.2.1 =

* [fixed] nav menu editor not accessible (regression from 2.2)
* [fixed] in some cases all pages became restricted due to changes in taxonomy condition (regression from 2.2)
* [fixed] some sites with modsecurity enabled could not add/edit levels due to a false positive by the waf

= 2.2 =

* [new] membership automations - add user levels from role change, login state, woocommerce purchase
* [new] taxonomy condition added to cache system (all condition types supported now)
* [new] ui and performance improvements
* [new] wordpress 5.8 support
* [new] minimum wordpress version 5.0
* [updated] simplified "default access" option to "can access unrestricted content"
* [updated] level management now uses "list_users" and "promote_users" capabilites
* [updated] wp-content-aware-engine library
* [updated] freemius sdk
* [fixed] multiple taxonomy conditions now use AND properly on singular pages (long-standing bug)
* [fixed] restrict shortcode with negation would not work for users with no levels
* [fixed] tease option does not support archive pages, fallback to redirect
* [deprecated] user role synchronizations in favor of automations

= 2.1.3 =

* [new] wordpress 5.6 support
* [updated] wp-content-aware-engine library
* [updated] freemius sdk

= 2.1.2 =

* [new] identical taxonomy names are now displayed with their post type
* [fixed] error when attempting to add member to non-existing level
* [fixed] non-member redirection for custom links
* [fixed] taxonomy and attachment condition suggestions would not display all results

= 2.1.1 =

* [fixed] users could not be added to levels, regression from v2.1

= 2.1 =

* [new] intelligent search by id in post type condition
* [new] intelligent search by id, email in author condition
* [new] ui and performance improvements
* [new] wordpress 5.5 support
* [new] restrict shortcode supports multiple levels
* [new] restrict shortcode drip_days parameter
* [new] RUA_User_Level_Interface and RUA_Level_Interface interfaces
* [updated] wp-content-aware-engine library
* [updated] freemius sdk
* [updated] RUA_User_Interface interface
* [updated] improved non-member redirection
* [fixed] condition option to auto-select new children

= 2.0 =

* [new] default access option to lockdown levels
* [new] exception conditions
* [new] ability to unset capabilities on extended levels
* [new] level manager shows inherited capabilities
* [new] compatibility with wooselect
* [updated] optimized and reduced plugin size with 26%
* [updated] improved non-member redirection
* [fixed] nav menu editor in wp5.4+ showing duplicate level options
* [fixed] level member list would in some cases always redirect to page 1
* [deprecated] negated conditions
* [deprecated] simple date archive condition

See changelog.txt for previous changes.