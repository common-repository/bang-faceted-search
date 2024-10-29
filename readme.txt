=== Bang Faceted Search ===
Contributors: marcus.downing, diddledan
Tags: search
Requires at least: 3.7
Tested up to: 5.5.0
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create a faceted search interface for any post type.

== Description ==

A faceted search is one that allows you to drill down by various fields: price, size, colour, shipping location etc. It complements a free text search rather than replaces it.

Using this plugin, you can create a faceted search interface for your pages, posts, or any other custom post type. You can add "facets" for:

*  Taxonomies
*  Custom fields
*  Dates (day, week, month or year)
*  Author

These facets are widgets that can be easily changed in the admin interface.

You can have multiple search pages for different post types. If your WordPress installation uses multi-site, you can search across the different sites.


== Installation ==

1. Upload the `bang-faceted-search` directory to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Create a template `search.php` in your theme
1. Go to Settings > Faceted Search to set up your search pages.

= Creating a search page =

By default, you'll have a single search address, at the site root `/`. This lines up with WordPress search.

In the WordPress admin adrea, go to Settings > Faceted Search. At the top is



= Creating a search template =

You need to create a search template in your theme called `search.php`, if you don't already have one. This template will be used whenever the user searches your site. You can have more than one search template, each.

Add this code to your `functions.php` to create a search-specific sidebar:

`register_sidebar(array(
  'name' => 'Search',
  'id' => 'search'
));`

Your search template should include that sidebar:

`<?php dynamic_sidebar('search'); ?>`

Then in the admin interface, drag Facet widgets into that sidebar to add, customise and re-order the search widgets for your site.

= Customising the faceted search =

Your site can have more than the default search page, and you can use the faceted search plugin to search custom post types.

To make a custom search, create a page with a custom template in your theme. For example, if you have a page called `/products`, then create a template in your theme called `products.php`. Near the start of this template, call this code:

`$faceted_search = bang_faceted_search(null, array(
  'force' => array(
    'post_type' => 'product'
  )
));`

Later on, display the results with something like:

`if ($faceted_search->has_posts()) {
  echo "<h1>Search results</h1>";
  $faceted_search->write_feedback();

  $posts = $faceted_search->get_posts();
  global $post;
  $original_post = $post;
  foreach ($posts as $post) {
    setup_postdata($post);
    // ... write the post ...
  }
  $post = $original_post;
  setup_postdata($post);
  echo $faceted_search->paginate();
}`


== Frequently Asked Questions ==

= What's wrong with a simple search field? =

Nothing, but faceted search makes your search richer.

Faceted search doesn't replace the normal search field, it complements it. You can have both, without any boundary between them.

= What's a "facet"? =

It's a means of narrowing down search results. A user might want to narrow down by categories or tags, by the author of a post, by the date it was published or by the contents of a taxonomy or custom field.

= Why don't I see any faceted search links? =

Make sure to create a search sidebar, and put some widgets into it.

If a facet widget has the **Hide empty terms** option checked, then that facet may easily disappear from a view when all its links would produce zero results.

= Can I put other widgets into the same sidebar as the facet widgets? =

Yes. It's a normal sidebar, you can put any widgets into it.

= Can I put facet widets into other sidebars? =

If the page you're on isn't a search page, facet widgets will be invisible.

= Does this work with Relevanssi? =

Yes. If you have the Relevanssi plugin switched on at the same time, they'll cooperate to deliver the best search results.

= Does this work with my eCommerce/calendar/reviews/other plugin? =

You can make a faceted search on any post type, but it won't understand any special fields like 'price' or 'score' if they're implemented in a non-standard way.

= Can I make my own facets? Can I filter the results? Can I change how the plugin works? =

Yes, yes and yes.

If you use a plugin that defines its own post types, such as an ecommerce or events calendar plugin, then you might want to make your own facet widgets. Create a WordPress widget and register it in the normal way. Look at the code of the included widgets to get an idea how to implement the links.

There are a lot more actions and filters you can hook into - have a look through the code to find them. Here are just a few of them:

*  `bang_fs_query` - The parameters to `WP_Query`
*  `bang_fs_results` - The posts returned
*  `bang_fs_count` - The number of results
*  `bang_fs_feedback_args` - The fields to display in the feedback
*  `bang_fs_options` - The plugin settings

Be aware that the internal workings of the plugin are subject to change, so the names and details of these hooks may change in future version.

== Changelog ==

= 2.0 =
* Settings page makes this plugin suitable for non-developers
* Simplified widgets by moving common options to the settings page
* Auto switch between a list and a dropdown based on number of options
* Updates to work with WordPress 3.8+
* Better integration with Relevanssi and other plugins
* WPMU: Network Search allows you to search between the sites on your network

= 1.0 =
* First version

