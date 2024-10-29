=== bb: Usage Stats ===
Contributors: baobabko
Tags: stats,usage,page,post,views,system,tracking
Requires at least: 2.0.2
Tested up to: 2.8
Stable tag: 1.0

Track post, page, category and homepage views. Build usage statistics, display usage graphs, top pages, posts, categories.

== Description ==

bb-usage-stats collects information about page, post, category and homepage views. Builds daily and monthly usage statistics.

To preserve database storage space, old usage information is purged from the database.

Graphical display of the daily usage statistics. Additional bar-graph chart that displays top 15 pages, posts and categories.

Adds dasboard widget that displays daily usage statistics chart and top 15 pages, post and categories.

== Installation ==

The bb-usage-stats plugin installation follows the standard WordPress plugin installation procedure.

1. Upload the content of the plugin archive to to the `/wp-content/plugins/bb-usage-stats` directory.
1. Activate the `bb:Usage Stats` plugin through the 'Plugins' menu in WordPress administrative interface.

When activated, the plugin automatically creates necessary database tables and adds a dashboard widget for usage statistics chart.

== Frequently Asked Questions ==

This section doesn't contain any questions yet. Please ask your questions.

== Screenshots ==

1. Sample daily usage chart.
2. Sample "Top 15" bar-graph chart.
3. Thumbnail view for the full statistics display.
4. Full statistics display.

== Changelog ==

= 1.0.1 =
* Fix: activation hook doesn't create track table.

= 1.0 =
* Dashboard widget with summary chart and Top 15 bar-graph chart
* Display Top 15 Bar-graph chart
* Display summary statistics graph chart
* Administrative page
* Track home page views
* Track category views
* Track post views
* Track page views
* Initial plugin release.

== TODO ==
* Make wp-cache compatible
* Exclude robots
* Performance optimization
* Configuration form
  - Enable/Disable dashboard widget (currently possible through dashboard settings).
  - Top N
* Browse daily statistics by month
* Create translation catalog.
* Exclude (configurable) repeatable views
