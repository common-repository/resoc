=== Social Images ===
Contributors: resoc
Donate link: https://resoc.io/
Tags: social, opengraph, resoc
Requires at least: 5.0
Tested up to: 5.8.2
Stable tag: 1.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


Generate branded, eye catching images for social networks. Automatically illustrate your content with images your audience will notice.

== Description ==

Do you use stock photos as featured images for your posts? Unfortunately, so does everyone else. When your visitors share your content on Facebook, LinkedIn or WhatsApp, it is soon lost in a sea of similar articles.

Take advantage of the Resoc online service to automatically generate images with great impact. An image otpimized by Resoc includes the post title, so your audience immediately sees what it is about. Plus, the image embeds your logo and colors. Even people quickly scrolling through their news feed have the opportunity to notice your brand.

Take a look at the first screenshot for a quick overview of how Resoc improves your content on social networks.

First, you choose a template among the one offered by Resoc. Then, you select your logo and colors. Done yet? Congratulations, your job is over! Resoc takes care of the rest. From now on, when you edit a post, the plugin automatically uses the Resoc API to create the corresponding social image.

== Installation ==

1. Activate the plugin through the 'Plugins' menu in WordPress.
2. Go to 'Appearance > Social Images' and configure the plugin, once for all.

== Frequently Asked Questions ==

= What does the plugin do exactly? =

When you edit a post, the plugin uses the Resoc API to create the corresponding image. It then saves it in the media gallery.

Later, when the post is visited, the plugin adds OpenGraph markups so social networks and apps discover your post metadata (title, excerpt, etc.) and the image Resoc created.

= Does the plugin modify my featured images? =

No. The plugin creates new images. You can find then by browsing the media gallery.

= Does the plugin automatically updates all my existing posts? =

No. The plugin might offer this option in the future, but this is not the case yet.

For a post to get a branded social image, you need to create or update it after the Resoc plugin setup.

= What happens if I deactivate/uninstall the plugin? =

Long story short: everything will be fine.

As the plugin injects OpenGraph markups dymanically, these markups disappear as soon as the plugin is deactivated.

Two things to note:

- Generated social images are not removed on uninstall. You will still find them in your media gallery.
- Because Facebook and other services use caching, you might still see the Resoc social images when your content is shared. They will eventualy take the change into account, though.

= Does the plugin use an extenal service? =

Yes. The plugin relies on the Resoc API to convert your featured images to branded social images.

= Why does the plugin need an extenal service? =

If you are a developer, you might think the Resoc plugin could achieve its task locally, without an external service.

While it could be possible to do so, this is definitely not convenient. For flexibility, Resoc is using HTML templates which are converted to images with Chromium/Puppeteer, something most WordPress sites don't have locally. Plus, the templates are rich and use edge CSS features, such as `clip-path`, that local solutions such as html2canvas do not support.

= Is it free? =

Yes.

Resoc might get premium features in the future, such as custom templates. However, whatever happens, you will be able to continue to use it for free. Features and templates that are free today will remain free.

== Screenshots ==

1. Without Resoc, your content is mundane when it is shared on Facebook. With Resoc, your audience notices it.
2. After activation, visit 'Appearance > Social Images' to select the template, colors and logo.
3. In addition to the classic featured image, the editor now shows your social image.
4. When a visitor shares your post on Facebook, his/her friends immediately notice your post title and your logo.

== Changelog ==

= 1.1.0 =
* Ability to switch to a new image engine
* Show social image preview in real time in the appearance page
* Plugin tested up to WordPress 5.8.2

= 1.0.13 =
* Social images are created asynchronously
* Use Resoc's free engine

= 1.0.12 =
* Social image preview
* Fix: different file name for each image
* Fix: regenerate social image on featured image change

= 1.0.11 =
* New Basic04 template

= 1.0.10 =
* Plugin tested up to WordPress 5.8

= 1.0.9 =
* Support right-to-left languages

= 1.0.8 =
* Improvements for the plugins list page
* Compatibility with Blog2Social

= 1.0.7 =
* Compatibility with SEOPress

= 1.0.6 =
* More explicit message on settings change

= 1.0.5 =
* Use the actual post title

= 1.0.4 =
* Notice to guide user on activation

= 1.0.3 =
* Fix issue in the previous release

= 1.0.2 =
* Compatibility with Yoast SEO and All in One SEO
* Detect conflicts with other plugins
* Generate image on auto-save
* Generate image only on change

= 1.0.1 =
* Minor fix in post save

= 1.0.0 =
* Initial version
