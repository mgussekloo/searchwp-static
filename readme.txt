=== Searchwp Static ===
Contributors: mgussekloo
Tags: search, searchwp
Requires at least: 4.5
Tested up to: 5.7.2
Requires PHP: 5.6
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Makes dummy pages in Wordpress in order to let SearchWP (or any other Wordpress-onsearch engine) index them.

== Description ==

Normally, SearchWP only indexes Wordpress content: posts and pages. Indexing other things
(a page built with ACF fields on options pages, or content from an external API) is non-trivial (to me).
This plugin visits frontend URLS on your site, and adds their entire page content to Wordpress.
SearchWP can index this dummy page just like any other page, and display it in the search results.
Any user visiting this new dummy page will be redirected to the original page.
You can customize the behaviour a bit through actions and filters.
Use at your own peril, etc.