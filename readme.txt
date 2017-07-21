=== Restrict User Access - WordPress Membership Plugin ===
Contributors: intoxstudio, devinstitute, keraweb
Donate link: 
Tags: restrict content, restrict access, access control, membership, capabilities, bbpress, buddypress, polylang, members, subscription, role, restriction
Requires at least: 4.0
Tested up to: 4.8
Stable tag: 0.16
License: GPLv3

Create Access Levels for your users to manage capabilities and conditionally restrict content. Lightweight and powerful.

== Description ==

Restrict content and contexts to control what your users get exclusive access to, or drip content over time. Create an unlimited number of Access Levels and override user and role capabilities.

Use this plugin to quickly set up a membership site where your users can get different levels such as Gold, Silver and Bronze. Then, restrict access to e.g. posts tagged "Premium", articles written by specific authors, or all your free products.

No coding required.

####Unlimited Access Levels

* Multiple levels per user
* Synchronization with User Roles
* Add membership durations
* Unlock (drip) content for new members
* Manage capabilities
* Hide Nav menu items
* Restrict Widget Areas in [Content Aware Sidebars](https://dev.institute/wordpress/sidebars-pro/)
* Redirect unauthorized users to a custom page
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
* Date Archives
* Search Results
* 404 Not Found Page
* Front Page
* Blog Page
* bbPress User Profiles
* BuddyPress Member Pages
* Languages (qTranslate X, Polylang, Transposh, WPML)
* Pods Pages

####Plugin Integrations & Support

Restrict User Access automatically supports Custom Post Types and Taxonomies created by any plugin or theme. Moreover, it comes with built-in support for some of the most popular WordPress plugins.

* bbPress
* BuddyPress
* Easy Digital Downloads
* qTranslate X
* Pods
* Polylang
* Transposh Translation Filter
* WooCommerce
* WPML

= Visibility Shortcodes =

`[restrict role="editor" page="1"]
This content can only be seen by editors.
Other users will see content from Page 1.
[/restrict]

[restrict level="platinum"]
This content can only be seen by users with Platinum level or above.
[/restrict]

[login-form]`

= API for Developers =

`rua_get_user_roles($user_id:int):array
rua_get_user_levels($user_id:int,$hierarchical:bool,$synced_roles:bool,$include_expired:bool):array
rua_get_user_level_start($user_id:int,$level_id:int):int
rua_get_user_level_expiry($user_id:int,$level_id:int):int
rua_is_user_level_expired($user_id:int,$level_id:int):bool
rua_has_user_level($user_id:int,$level_id:int):bool
rua_add_user_level($user_id:int,$level_id:int):int|bool
rua_remove_user_level($user_id:int,$level_id:int):bool
rua_get_level_by_name($name:string):int
rua_get_level_caps($name:string,$hierarchical:bool):array
`

= For more information =

* [Follow development on Github](https://github.com/intoxstudio/restrict-user-access)
* [Intox Studio on Facebook](https://www.facebook.com/intoxstudio)
* [Intox Studio on Twitter](https://twitter.com/intoxstudio)

== Installation ==

1. Upload the full plugin directory to your `/wp-content/plugins/` directory or install the plugin through `Plugins` in the administration 
1. Activate the plugin through `Plugins` in the administration
1. Have fun creating your first Access Level under the menu *User Access > Access Levels > Add New*

== Frequently Asked Questions ==

= How do I restrict some content? =

1. Go to User Access > Add New
1. Click on the "Select content type" dropdown to add a condition
1. Click on the created input field and select the content you want to restrict.
1. To the right you can choose to sync the level with a User Role. All users with the selected role will then get this level. Otherwise, add the level to each user individually under the Members tab or in their profile
1. For unauthorized users, choose whether to redirect to another page or to show the content from another page along with a teaser/excerpt from the restricted content
1. Give your new level a descriptive title and save it

**Tips**
In order to restrict a context, e.g. "All Posts with Category X", simply select a new type of content from the dropdown below the **and** label and repeat Step 3.

You can choose to negate conditions, meaning that if you negate the group "All posts with Category X", the level will get exclusive access to all content but that.

= How do I make an Access Level extend/inherit another level? =

Let us say you have two Access Levels, Gold and Silver. You want your users with the Gold level to be able to see content for the Silver level too.

1. Go to User Access > Access Levels > Edit the Gold level
1. To the right on this screen there is a Extend setting
1. Choose the Silver level as Extend and click Update

Your Gold level now inherits all the conditions and capabilities from your Silver level. You can create as many hierarchical levels as you want, e.g. Bronze -> Silver -> Gold -> Platinum.

= I added a Level to a user, but it can still see other content? =

When you create an Access Level to restrict some content, only users with this level will be able to see that content, but they will also be able to see all other (unrestricted) content on your site.

A quick way to "lock down" and make sure e.g. only Administrators can see all content is to create a new Access Level for Administrators with a negated condition group containing "404 Page". This means that normal users only can see the 404 Page.

By default, Administrators will have access to all content regardless of your levels.

= Restricted content is still being displayed on archive pages or in widgets? =

Restrict User Access does currently not support hiding single items from archive pages, search results, widgets or custom lists.

It is recommended only to show titles and excerpts in these cases.

= Restricted file is still accessible with deep link? =

Restrict User Access does currently not support restricting deep links to files, only attachment urls.

= User still able to edit restricted content in Admin Dashboard? =

Capabilities and Restrictions are separate settings with different functions. Restrictions affect only the frontend, while capabilities work throughout the site (both Admin Dashboard and frontend).

== Screenshots ==

1. Simple Access Levels Overview
2. Conditional Restrictions for Access Level
3. Capability Manager for Access Level

== Upgrade Notice ==

= 0.14 =

* Restrict User Access data in your database will be updated automatically. It is highly recommended to backup this data before updating the plugin.

== Changelog ==

= 0.16 =

* Added: ability to manage more level capabilities
* Added: better support for RTL languages
* Added: restrictions now work for password protected posts
* Added: wordpress 4.8 support
* Fixed: special characters in translations of conditions
* Fixed: post type conditions with no titles
* Fixed: clear user capability cache when its level memberships change
* Fixed: do not show levels when editing network user profile

= 0.15 =

* Added: rewritten admin screens for improved compatibility and ux 
* Added: performance improvements
* Added: updated wp-content-aware-engine
* Added: now requires at least wordpress 4.0
* Fixed: could not redirect to archive pages after login

= 0.14 =

* Added: autosave conditions
* Added: wp filter to add condition metadata
* Added: wp action to add condition actions
* Added: simplify option to autoselect conditions
* Added: ui improvements
* Fixed: type warning on capabilities
* Fixed: adding multiple members to level at once

= 0.13 =

* Added: ability to restrict all buddypress profile sections
* Added: exposure moved to condition groups, now called singulars or archives
* Added: get level capabilities in the API (props Jory Hogeveen)
* Added: wordpress 4.7 support
* Added: now requires at least wordpress 3.9
* Fixed: improved restriction editor UI
* Fixed: improved search when adding members to level
* Fixed: better compatibility with other plugins using nav menu editor (props Jory Hogeveen)

= 0.12.4 =

* Added: more compatibility with plugins adding unneeded scripts
* Fixed: extended capabilities could in rare cases cause white screen

= 0.12.3 =

* Added: counter-measure against plugins that add buggy scripts

= 0.12.2 =

* Fixed: decoding of taxonomy term names in conditions
* Fixed: order of content in conditions dropdowns
* Fixed: compatibility for wp versions older than 4.0

= 0.12.1 =

* Added: select2 dropdowns updated to 4.0.3
* Added: select2 dropdown styles more robust to external changes
* Fixed: dropdowns on user profile, nav menus, members tab

= 0.12 =

* Added: performance improvements
* Added: set visibility per level in content aware sidebars
* Added: drastically reduced database queries when checking taxonomies
* Added: support for buddypress 2.6 members
* Added: infinite scroll for content in level conditions editor
* Added: select2 dropdown styles more robust to external changes
* Added: dialog on unsaved changes in level conditions editor
* Added: wordpress 4.6 support
* Fixed: woocommerce order page inaccessible for users
* Fixed: option to select all authors and bbpress profiles
* Fixed: improved level conditions editor ux

= 0.11.1 =

* Added: remove foreign metadata on level deletion
* Added: use caching when getting user levels synced with role
* Fixed: add guard for plugins using wp_edit_nav_menu_walker filter wrong
* Fixed: levels synced with role selectable in user profile

= 0.11 =

* Added: restrict nav menu items to access levels
* Added: capability column and small improvements in level overview
* Added: easier to manage levels in user profile
* Removed: date column in level overview

= 0.10.1 =

* Fixed: admin toolbar could be hidden for admins and displayed when not logged in

= 0.10 =

* Added: access level pages moved to new menu
* Added: settings page
* Added: option to hide admin toolbar
* Added: option to add level on new user
* Added: api to add and remove user level
* Added: pods pages module, props @sc0ttkclark @herold
* Fixed: auth redirection would in rare cases not work
* Fixed: better compat when other themes or plugins load breaking scripts
* Fixed: condition logic ui improvements

= 0.9.1 =

* Fixed: api should be loaded everywhere

= 0.9 =

* Added: login-form shortcode
* Added: user search input stays open on select
* Added: api to get user roles, user levels, user level start, user level expiry, is user level expired, has user level and get level by name
* Fixed: expiry bug when level had no duration
* Fixed: searching users for level
* Fixed: user search input would in some cases not work


= 0.8 =

* Added: level capability manager
* Added: level editor tabs moved under title
* Fixed: level members pagination
* Fixed: performance improvements

= 0.7 =

* Added: completely rewritten level condition ui
* Added: qtranslate x module
* Added: ability to drip content
* Fixed: bug making attachments not selectable
* Fixed: bumped versions for integrated plugins
* Fixed: bug when saving user profile
* Removed: qtranslate module

= 0.6 =

* Added: ability to add members from members screen
* Added: show level name in overview
* Added: filter for global access
* Added: admins will have global access by default
* Added: level parameter for restrict shortcode
* Added: email link in members list
* Added: expired levels included in user list
* Fixed: hierarchical and synced levels for logged-out users
* Fixed: fix expiry check when getting levels
* Fixed: pagination in members list
* Fixed: levels with members can be saved properly
* Fixed: duration hidden for synced levels

= 0.5 =

* Added: level durations
* Added: users can have more than one level
* Added: levels synced with roles now visible in user list
* Added: ability to remove and bulk remove users in level members list
* Added: status column in level members list
* Fixed: levels synced with roles did not work properly hierarchically
* Fixed: some array used php5.4+ syntax
* Fixed: removed warning for missing parameter in action hook
* Fixed: compatible with wp4.4

= 0.4 = 

* Added: allow list of roles in shortcode
* Added: show number of members in level overview
* Added: list of members in level editor
* Added: draft post status included in post type lists
* Fixed: posts page and front page excluded from page post type list
* Fixed: gui improvements in level editor
* Fixed: corrected the way user level dates are stored
* Fixed: renamed old restriction strings

= 0.3.2 =

* Added: wp4.3 compatibility
* Added: links to support and faq
* Fixed: remove warning when no levels exist
* Fixed: correct link to review page

= 0.3.1 =

* Fixed: access level manager now requires edit_users capability
* Fixed: users without edit_users capability cannot control their own level

= 0.3 =

* Added: restrictions renamed to access levels
* Added: hierarchical level functionality
* Added: levels can be given to individual users or synchronized with roles
* Added: non-synced levels are displayed in users overview screen
* Fixed: content would not be restricted properly if two access levels had overlapping conditions for different roles
* Fixed: actions and filters got new namespaces

= 0.2.2 =

* Fixed: restrictions not working properly for non-logged in users

= 0.2.1 =

* Fixed: if metadata value was 0, default value would be displayed instead
* Fixed: check if admin column key exists before trying to display metadata

= 0.2 =

* Added: ability to select non-logged in user in restriction manager
* Fixed: in some cases content could not be removed from condition group
* Fixed: pagination and search for post types in restriction manager
* Fixed: some code needed php5.3+

= 0.1 =

* First stable release
