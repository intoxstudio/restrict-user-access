=== Restrict User Access - Membership Plugin with Force ===
Contributors: intoxstudio, devinstitute, keraweb, freemius
Donate link: #
Tags: restrict content, membership, access control, capabilities, members, bbpress, buddypress
Requires at least: 4.8
Requires PHP: 5.6
Tested up to: 5.5
Stable tag: 2.1.1
License: GPLv3

Create Access Levels and restrict any post, page, category, etc. Supports bbPress, BuddyPress, WooCommerce, WPML, and more.

== Description ==

Restrict content and contexts to control what your users get exclusive access to, or drip content over time. Create an unlimited number of Access Levels and override user and role capabilities.

Use this plugin to quickly set up a membership site where your users can get different levels such as Gold, Silver and Bronze. Then, restrict access to e.g. posts tagged "Premium", articles written by specific authors, or all your free products.

No coding required.

####Unlimited Access Levels

* Multiple levels per user
* Sync with User Roles, Logged in, or Logged out
* Add membership durations
* Unlock (drip) content for new members
* Permit & deny level capabilities
* Hide nav menu items
* Restrict Widget Areas in [Content Aware Sidebars](https://dev.institute/wordpress-sidebars/)
* Redirect unauthorized users to a page or custom link
* Tease content for unauthorized users and show custom message
* Shortcode to fine-tune restrictions in your posts or pages

####Unlimited Content Restrictions

Conditionally restrict all your posts, pages, categories, or any content you want. Restrict User Access even allows you to combine conditions. This means that you e.g. can restrict all posts in Category X written by author Y.

For each level you can restrict content with the following conditions:

* Singulars, eg. each post, page, or custom post type
* Content with select taxonomies, eg. categories or tags
* Content written by a select author
* Page Templates
* Post Type Archives
* Author Archives
* (Custom) Taxonomy Archives
* Search Results
* 404 Not Found Page
* Front Page
* Blog Page
* bbPress User Profiles
* BuddyPress Member Pages
* Languages (qTranslate X, Polylang, Transposh, WPML, TranslatePress)
* Pods Pages

####Plugin Integrations & Support

Restrict User Access automatically supports Custom Post Types and Taxonomies created by any plugin or theme. Moreover, it comes with built-in support for some of the most popular WordPress plugins.

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

####Shortcodes

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

####Developer API

`
rua_get_user($user_id): RUA_User_Interface;
rua_get_level_by_name(string $name): WP_Post;

RUA_User_Interface {
    get_id(): int;
    get_attribute(string $name, mixed $default_value = null): mixed;
    has_global_access(): bool;
    level_memberships(): RUA_User_Level_Interface[];
    get_level_ids(): int[];
    add_level(int $level_id): bool
    remove_level(int $level_id): bool
    has_level(int $level): bool;
}

RUA_User_Level_Interface {
    get_user_id(): int;
    user(): RUA_User_Interface;
    get_level_id(): int;
    get_level_extend_ids(): int[];
    level(): RUA_Level_Interface;
    get_status(): string;
    get_start(): int;
    get_expiry(): int;
    is_active(): bool;
}

`

####More Information

* [Documentation](https://dev.institute/docs/restrict-user-access/?utm_source=readme&utm_medium=referral&utm_content=info&utm_campaign=rua)
* [GitHub](https://github.com/intoxstudio/restrict-user-access)
* [Twitter](https://twitter.com/intoxstudio)

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

When you create an Access Level to restrict some content, only users with this level will be able to see that content.

An Access Level has two Default Access modes that can be changed from the Options tab:

1. All unrestricted content (default): Members can also access all content that has not been restricted by other levels
1. Restricted content only: Members can only access the content that has been restricted for this level

To prevent lockout, Administrators will have access to all content regardless of your levels.

= Restricted content is still being displayed on archive pages or in widgets? =

Restrict User Access does currently not support hiding single items from archive pages, search results, widgets or custom lists.

It is recommended only to show titles and excerpts in these cases.

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

= 1.3 =

* [new] translatepress access condition
* [new] wordpress 5.4 support
* [new] minimum wordpress version 4.8
* [updated] wp-content-aware-engine library
* [updated] freemius sdk

= 1.2.1 =

* [fixed] condition type cache would in some cases be primed with bad data
* [fixed] edge case where negated conditions would be ignored

= 1.2 =

* [new] condition type cache for improved performance
* [new] categories and search in dropdown for access condition types
* [new] filter to modify [restrict] shortcode
* [new] filter to disable nav menu restrictions
* [new] wordpress 5.3 support
* [new] minimum wordpress version 4.6
* [updated] ui improvements
* [updated] wp-content-aware-engine library
* [updated] wp-db-updater library
* [updated] freemius sdk

See changelog.txt for previous changes.