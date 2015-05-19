=== Plugin Name ===
Contributors: intoxstudio
Donate link: 
Tags: restrict content, restrict access, limit access, member only, access control, bbpress, buddypress, qtranslate, polylang, transposh, wpml, woocommerce
Requires at least: 3.8
Tested up to: 4.2
Stable tag: 0.2.2
License: GPLv3

Easily restrict content and contexts to provide exclusive access for specific User Roles.

== Description ==

Easily control which user roles to get exclusive access to selected content or contexts on your site. Create an unlimited number of restrictions without the need of code.

Use the plugin to quickly set up a membership site and restrict access to e.g. posts tagged "Premium", articles written by specific authors or all your free products.

= Lots of Awesome Features =

* Restrict access to content or contexts for specific User Roles
* Easy-to-use Restriction Manager
* Schedule restrictions
* Redirect unauthorized users to a custom page
* Tease content for unauthorized users and show custom message 
* Shortcode to restrict content in your posts or pages more granular:

`[restrict role="editor" page="1"]
This content can only be seen by editors. Other users will see content from Page 1, if the page attribute is present.
[/restrict]`

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
* Languages (qTranslate, Polylang, Transposh, WPML)

= Integrated Support for Popular Plugins =

* bbPress (v2.0.2+)
* BuddyPress (v1.6.2+)
* qTranslate (v2.5.29+)
* Polylang (v1.2+)
* Transposh Translation Filter (v0.9.5+)
* WPML Multilingual Blog/CMS (v2.4.3+)

= For more information =

* [Follow development on Github](https://github.com/intoxstudio/restrict-user-access)
* [Intox Studio on Facebook](https://www.facebook.com/intoxstudio)
* [Intox Studio on Twitter](https://twitter.com/intoxstudio)

== Installation ==

1. Upload the full plugin directory to your `/wp-content/plugins/` directory or install the plugin through `Plugins` in the administration 
1. Activate the plugin through `Plugins` in the administration
1. Have fun creating your first restriction under the menu *Restrictions > Add New*

== Frequently Asked Questions ==

None yet.

== Screenshots ==

== Upgrade Notice ==

= 0.1 =

* Hello World

== Changelog ==

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
