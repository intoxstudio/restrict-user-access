=== Plugin Name ===
Contributors: intoxstudio
Donate link: 
Tags: restrict content, restrict access, members only, access control, bbpress, buddypress, qtranslate x, polylang, transposh, wpml, woocommerce, members, membership, subscription, capabilities, role, restrict, restriction, access, teaser
Requires at least: 3.8
Tested up to: 4.4
Stable tag: 0.8
License: GPLv3

Create Access Levels for your users to conditionally restrict content and manage capabilities. Lightweight and powerful.

== Description ==

Restrict select content and contexts to control what your users get exclusive access to and when. Create an unlimited number of Access Levels and override user and role capabilities without the need of code.

Use this plugin to quickly set up a membership site where your users can get different levels such as Gold, Silver and Bronze. Then, restrict access to e.g. posts tagged "Premium", articles written by specific authors, or all your free products.

= Lots of Awesome Features =

* Easy-to-use Access Level Manager
* Add (multiple) levels to registered users
* Synchronize Access Levels with User Roles
* Restrict content and contexts for specific Access Levels
* Make Access Levels extend each other
* **[NEW]** Durations for Access Levels
* **[NEW]** Drip content and contexts
* **[NEW]** Capabilities for Access Levels
* Redirect unauthorized users to a custom page
* Tease content for unauthorized users and show custom message 
* Schedule Access Levels
* Shortcode to restrict content in your posts or pages more granular

= Conditional restrictions =

Create restrictions for the following contexts, in any combination:

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

= Integrated Support for Popular Plugins =

* bbPress (v2.5+)
* BuddyPress (v2.0+)
* qTranslate X (v3.4.6.4+)
* Polylang (v1.7+)
* Transposh Translation Filter (v0.9.5+)
* WPML Multilingual Blog/CMS (v2.4.3+)

= Flexible Restriction Shortcode =

`[restrict role="editor" page="1"]
This content can only be seen by editors.
Other users will see content from Page 1, if the page attribute is present.
[/restrict]

[restrict level="platinum"]
This content can only be seen by users with Platinum level or above.
[/restrict]`

= For more information =

* [Follow development on Github](https://github.com/intoxstudio/restrict-user-access)
* [Intox Studio on Facebook](https://www.facebook.com/intoxstudio)
* [Intox Studio on Twitter](https://twitter.com/intoxstudio)

== Installation ==

1. Upload the full plugin directory to your `/wp-content/plugins/` directory or install the plugin through `Plugins` in the administration 
1. Activate the plugin through `Plugins` in the administration
1. Have fun creating your first Access Level under the menu *Access Levels > Add New*

== Frequently Asked Questions ==

= I added a Level to a user, but it can still see other content? =

When you create an Access Level to restrict some content, only users with this level will be able to see that content, but they will also be able to see all other (unrestricted) content on your site.

A quick way to "lock down" and make sure e.g. only Administrators can see all content is to create a new Access Level for Administrators with a negated condition group containing "404 Page". This means that normal users only can see the 404 Page.

By default, Administrators will have access to all content regardless of your levels.

= How do I restrict some content? =

1. Go to Users > Access Levels > Add New
1. On this screen, select a type of content from the "Select content type" dropdown
1. Click on the input field next to the new content type and select the content you want to restrict. This content will be available for users with this Access Level (or higher) only
1. If you want to restrict a context, e.g. "All posts with Category X", simply select a new type of content from the dropdown just below and repeat the previous step
1. You can choose to negate the conditions, meaning that if you negate "All posts with Category X", you will restrict all content but that
1. Now, to the right, you can choose to synchronize the Access Level with a User Role. This means that all users with that Role will automatically get this Level. If you choose not to synchronize, you can add the Level to each user individually under the Members tab or their profile
1. For unauthorized users, you can choose whether to redirect to another page or to show the content from another page along with a teaser/excerpt from the restricted content
1. Finally, give your new Access Level a descriptive title and save it

= How do I make an Access Level extend/inherit another level? =

Let us say you have two Access Levels, Gold and Silver. You want your users with the Gold level to be able to see content for the Silver level too.

1. Go to Users > Access Levels > Edit the Gold level
1. To the right on this screen there is a Extend setting
1. Choose the Silver level as Extend and click Update

Your Gold level now inherits all the conditions from your Silver level. You can create as many hierarchical levels as you want, e.g. Bronze -> Silver -> Gold -> Platinum.

== Screenshots ==

== Upgrade Notice ==

= 0.4 =

* Restrict User Access data in your database will be updated automatically. It is highly recommended to backup this data before updating the plugin.

= 0.1 =

* Hello World

== Changelog ==

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
