=== SVGator - Add Animated SVG Easily ===

Contributors: alucaci, dzsamper, lzudor, lgorbe
Donate link: http://www.svgator.com/
Tags: svg, vector image
Requires at least: 5.0
Requires PHP: 5.2
Tested up to: 6.6
Stable tag: 1.3.2
License: GPLv2

The easiest way to add SVG animations to your website right from your SVGator account.

== Description ==

Import your SVG files created in [SVGator](https://www.svgator.com) to your WordPress media library and use them as normal image files. This plugin lets you comfortably access all your animation projects and makes it super-easy to add them to your page. You are free to choose an SVG with either CSS or JavaScript as the animation type.

Scalable Vector Graphics (SVG) are constantly growing in popularity due to their scalability, small size, and responsiveness. They are ideal for illustrations like logos, icons, buttons, and graphs. Animated SVG files make your page unique, interactive, and look crisp on any device.

SVGator is an incredibly useful and innovative SVG animation tool that lets you create stunning SVG animations without any coding skills. Import your SVG and choose from a series of advanced animator tools that let you generate amazing animations in just minutes. Spice up your website or blog with impressive SVG animations to increase user engagement. The user-friendly drag and drop interface simplifies the animation process and the code is automatically generated either in CSS or in Javascript.

== Usage ==

You can check out our documentation with screenshots on our help page: [How to use SVGator Plugin for WordPress](https://www.svgator.com/help/getting-started/wordpress-svgator-plugin)

Before starting to use the WordPress plug-in, you will have to sign up for the SVGator app. After creating an account, you can import your SVG files and start working on them to turn your static designs into dynamic and interactive animations.

When you are done with the animation, you can fully benefit from the plugin. There is no need to export or convert your work, just follow these simple steps:
1.	Find and install the SVGator plugin
2.	Activate the plugin
3.	Log in to load your projects from SVGator and authorize the app
4.	Select an animation project from your SVGator library and add it to the media library
5.	Add an animated SVG block to your post or page by clicking the SVGator icon on the `new block` tool.
6.  Select the animated SVG from the media library and scale it to any size without losing quality
7.  Alternatively, you can import your SVGs directly on the media library box, under "Import from SVGator" tab

You can also use the animations inside widgets by simply adding the `SVGator` widget to your sidebars or footers.

**Note:** When you import your animation to the page, you may find it in a <img> tag in the editor, but you don’t have to worry about this. Although theoretically, this would mean that the animation might not work the same as in SVGator, (especially if it's an interactive SVG), in preview mode you will already find it inline and it will work perfectly.

== Security ==

By authorizing the plugin you acknowledge that WordPress will have access to your SVGator library and is enabled to list and export the animations created in your account.

You can remove the plugin at any time by clicking on SVGator’s Account settings under your name, then choosing the 3rd Party Apps tab and deleting the plugin. If you would like to add it back, you will have to follow the steps presented above.

== Feedback ==

We are always open to your feedback, questions, and suggestions. Email us at [contact@svgator.com](mailto:contact@svgator.com)

We hope you find this plugin useful. Please take a moment to rate it [here](https://wordpress.org/plugins/svgator/).

== Installation ==

Detailed instructions about SVGator's WordPress plugin usage can be found in our Help center: [How to use SVGator Plugin for WordPress](https://www.svgator.com/help/getting-started/wordpress-svgator-plugin)

This section describes how to install the plugin and get it working, from the first step to the last one.

= I. Installation =

1. Download and unpack the archive (svgator.zip)
2. Upload `/svgator/` to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress

= II. Basic setup =

To connect your account with WordPress, you will have to allow access to your projects for your WordPress installation.

1. Go to the SVGator plugin's settings page
2. Press the Login button
3. Authenticate into SVGator (if you are not authenticated yet)
4. Allow the app to access your projects

= III. Usage =

1. Go to the SVGator plugin's settings page
2. Pick a project and click "Import to media"
3. Go to your post's edit page & use the SVG from your media library

== Frequently Asked Questions ==

= Where do I report security bugs? =

You can report any security bugs found in the source code of this plugin through the [Patchstack Vulnerability Disclosure Program](https://patchstack.com/database/vdp/svgator). The Patchstack team will assist you with verification, CVE assignment and take care of notifying the developers of this plugin.


== Screenshots ==

1. SVGator's configuration page before logging in

== Changelog ==

= 1.3.2 =
* Bugfix - Issue introduced in 1.3.1 fixed: Cannot select SVG for SVGator Legacy Widget

= 1.3.1 =
* Bugfix - Issue present since original release fixed: From time to time while adding a new SVG, another one on the page got replaced instead

= 1.3.0 =
* Resolved vulnerability issue regarding Cross-Site Scripting (XSS) - disabled the ability to upload SVG format files from Media library (SVGs can still be imported straight from SVGator.com)
* Fixed lottie projects to import as SVG
* Fixed deleted projects showing up in SVGator project list
* Fixed placeholder not showing up for plugin instance without SVG selected
* Other minor fixes
* Resolved issues shown up on [Plugin Check](https://github.com/WordPress/plugin-check/) WordPress plugin (version 1.0.2)
* Update Readme with license information and some more - validated with [Readme Validator](https://wordpress.org/plugins/developers/readme-validator/)
* Functionality retested for version 6.6

= 1.2.6 =
* Fixed a JS error on admin UI - post edit page

= 1.2.5 =
* Applied for Patchstack Vulnerability Disclosure Program (VDP)
* Sign Out nonce patch added
* Code Update (Option DB entry name centralized into a class constant)
* functionality retested for version 6.4.2

= 1.2.4 =
* SVGs over 1M+ failed to load inline; moved into object tag
* functionality retested for version 6.2.1

= 1.2.3 =
* Error handling updated

= 1.2.2 =
* SVGator SDK updated
* Platform forced for web

= 1.2.1 =
* Background Color support for exported SVGs

= 1.2.0 =
* Improved error message handling
* Export counter added to the UI

= 1.1.1 =
* Fixing a compatibility issue with Elementor WP plugin

= 1.1.0 =
* Custom Media Library for animated SVGs added
* Ability to add animated SVGs as widgets
* Plugin compatibility: Elementor

= 1.0.2 =
* Ability to add a project directly to a post via block editor

= 1.0.1 =
* Bugs reported by users fixed
* Conflict with other SVGs resolved
* Disambiguation between Login & Connect resolved by renaming Connect & Disconnect buttons
* Project ID added to SVG attribute
* Post preview fixed for some SVG types
* ES5 compatibility fixes (Older Safari browser support)

= 1.0.0 =
* Initial public release.

== Upgrade Notice ==

For full functionality update to version 1.3.2

== Disclosure ==

This plugin is relying on SVGator.com as a 3rd party service.
All SVGs loaded using this plugin will be done via API requests to SVGator.com.

Service URL: [https://www.svgator.com/]

Service API: [https://github.com/SVGator/SDK/]

Terms of service: [https://www.svgator.com/terms-of-service]

Privacy policy: [https://www.svgator.com/privacy-policy]

== License ==

SVGator.com WordPress Plugin
Copyright (C) 2020,2021,2022,2023,2024 SVGator.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>
or write to the Free Software Foundation, Inc.,
51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA.
