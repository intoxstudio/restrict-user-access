=== Plugin Name ===
Contributors: intoxstudio
Donate link: 
Tags: restrict content, restrict access, members only, access control, bbpress, buddypress, qtranslate x, polylang, transposh, wpml, woocommerce, members, membership, subscription, capabilities, role, restrict, restriction, access, teaser, pods
Requires at least: 3.8
Tested up to: 4.5
Stable tag: 0.11.1
License: GPLv3

Create Access Levels for your users to conditionally restrict content and manage capabilities. Lightweight and powerful.

== Description ==

Restrict content and contexts to control what your users get exclusive access to - and when. Create an unlimited number of Access Levels and override user and role capabilities without the need of code.

Use this plugin to quickly set up a membership site where your users can get different levels such as Gold, Silver and Bronze. Then, restrict access to e.g. posts tagged "Premium", articles written by specific authors, or all your free products.

= Lots of Awesome Features =

* Add multiple Access Levels to your users
* Synchronize Access Levels with User Roles
* Restrict content and contexts to specific Access Levels
* **[NEW]** Restrict nav menu items to specific Access Levels
* Durations for Access Levels
* Drip content and contexts
* **[NEW]** Capabilities for Access Levels
* Redirect unauthorized users to a custom page
* Tease content for unauthorized users and show custom message 
* Schedule Access Levels
* Shortcode to restrict content in your posts or pages more granular

= Conditional Restrictions =

Add restrictions to your Access Levels for the following contexts, in any combination:

* Singulars - e.g. posts or pages
* (Custom) Post Types
* Singulars with given (custom) taxonomies or taxonomy terms - e.g. categories or tags
* Singulars by a given author
* Page Templates
* Post Formats
* Post Type Archives
* Author Archives
* (Custom) Taxonomy Archives or Taxonomy Term Archives
* Date Archives
* Search Results
* 404 Page
* Front Page
* bbPress User Profiles
* BuddyPress Member Pages
* Languages (qTranslate X, Polylang, Transposh, WPML)
* **[NEW]** Pods Pages

= Integrated Support for Popular Plugins =

* bbPress (v2.5+)
* BuddyPress (v2.0+)
* qTranslate X (v3.4.6.4+)
* Pods (v2.6+)
* Polylang (v1.7+)
* Transposh Translation Filter (v0.9.5+)
* WPML Multilingual Blog/CMS (v2.4.3+)

= Useful Shortcodes =

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

1. Go to User Access > Access Levels > Add New
1. Select a type of content from the "Select content type" dropdown to add a condition group
1. Click on the input field next to the new content type and select the content you want to restrict. This content will be available for users with this Access Level (or higher) only. Remember to save changes on each condition group
1. To the right you can choose to synchronize the Access Level with a User Role. This means that all users with that Role will automatically get this Level. If you choose not to synchronize, you can add the Level to each user individually under the Members tab or their profile
1. For unauthorized users you can choose whether to redirect to another page or to show the content from another page along with a teaser/excerpt from the restricted content
1. Give your new Access Level a descriptive title and save it
1. **Optional** If you want to restrict a context, e.g. "All Posts with Category X", simply select a new type of content from the dropdown in the condition group and repeat Step 3
1. **Optional** You can choose to negate conditions, meaning that if you negate the group "All posts with Category X", you will restrict all content but that

= How do I make an Access Level extend/inherit another level? =

Let us say you have two Access Levels, Gold and Silver. You want your users with the Gold level to be able to see content for the Silver level too.

1. Go to User Access > Access Levels > Edit the Gold level
1. To the right on this screen there is a Extend setting
1. Choose the Silver level as Extend and click Update

Your Gold level now inherits all the conditions from your Silver level. You can create as many hierarchical levels as you want, e.g. Bronze -> Silver -> Gold -> Platinum.

= I added a Level to a user, but it can still see other content? =

When you create an Access Level to restrict some content, only users with this level will be able to see that content, but they will also be able to see all other (unrestricted) content on your site.

A quick way to "lock down" and make sure e.g. only Administrators can see all content is to create a new Access Level for Administrators with a negated condition group containing "404 Page". This means that normal users only can see the 404 Page.

By default, Administrators will have access to all content regardless of your levels.

= Restricted content is still being displayed on archive pages or in widgets? =

Restrict User Access does currently not support hiding single items from archive pages, search results, widgets or custom lists.

It is recommended only to show titles and excerpts in these cases.

== Screenshots ==

1. Simple Access Levels Overview
2. Conditional Restrictions for Access Level
3. Capability Manager for Access Level

== Upgrade Notice ==

= 0.4 =

* Restrict User Access data in your database will be updated automatically. It is highly recommended to backup this data before updating the plugin.

= 0.1 =

* Hello World

== Changelog ==

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
