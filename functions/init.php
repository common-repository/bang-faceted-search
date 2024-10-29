<?php

//add_action('post_updated', 'bang_fs_save_meta');
// function bang_fs_save_meta($post_id) {
// 	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
// 		return $post_id;

// 	update_post_meta($post_id, "is_faceted_search", $_REQUEST['is_faceted_search'] ? "yes" : "");
// 	$post_types = array();
// 	update_post_meta($post_id, "fs_post_types", implode(",", $post_types));
// }


function bang_fs_post_init() {
	if (BANG_FS_DEBUG  && !defined('BANG_FS_INIT'))
		do_action('log', 'fs: pre init', '@trace');
}

//  Do our init -- *after* all the plugins are loaded, to make sure post types are registered etc
add_action('wp_loaded', 'bang_fs_init');
function bang_fs_init($args = array()) {
	wp_register_script('faceted-search', plugins_url('scripts/faceted-search.js', BANG_FS_PLUGIN_FILE), array('jquery', 'jquery-ui-autocomplete'));
	wp_register_style('faceted-search', plugins_url('faceted-search.css', BANG_FS_PLUGIN_FILE));

	if (is_admin()) {
		// wp_enqueue_script('faceted-search-admin', plugins_url('scripts/admin/fs-admin.js', BANG_FS_PLUGIN_FILE), array('jquery'));
		// wp_enqueue_style('faceted-search-admin', plugins_url('admin.css', BANG_FS_PLUGIN_FILE));
		if (!defined('BANG_FACETED_SEARCH')) define('BANG_FACETED_SEARCH', false);
		return;
	}
	if (defined('BANG_FACETED_SEARCH')) {
		if (BANG_FS_DEBUG) do_action('log', 'WARNING: You should not call %s again!', 'bang_fs_init');
		return;
	}
	if (!defined('BANG_FS_INIT')) define('BANG_FS_INIT', true);
	if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: init');

	// Load the settings fresh so that we can be sure post types are covered
	bang_fs_forget_settings();

	// Find a search location that matches the current URI
	$settings = bang_fs_settings();
	if ($settings->show_non_search)
		bang_fs_show_facets();

	$loc = bang_fs_location();
	if (is_null($loc)) {
		if (!defined('BANG_FACETED_SEARCH')) define('BANG_FACETED_SEARCH', false);
		if ($settings->unsearch) {
			// add_filter('query_vars', 'bang_fs_unsearch_query_vars');
			// add_action('parse_request', 'bang_fs_unsearch_parse_request');
			add_filter('request', 'bang_fs_unsearch_request');
		}
		return;
	}
	if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: init: Search point', $loc);

	// Check if the query parameters are enough to trigger this as a search
	$get = bang_fs_get();

	// $get = bang_fs_get_unpaged();
	if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: init: Get', $get);
	if (empty(bang_fs_unpaged($get)) && empty($loc->uri)) {
		if (!defined('BANG_FACETED_SEARCH')) define('BANG_FACETED_SEARCH', false);
		return;
	}

	// Yes! This is a faceted search
	if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: init: faceted search at', $loc);

	if (!defined('BANG_FACETED_SEARCH')) define('BANG_FACETED_SEARCH', true);
	global $faceted_search, $bang_fs_current_location;
	$bang_fs_current_location = $loc;

	// make a new faceted search
	// $get = bang_fs_get();
	$options = wp_parse_args((array) $loc, (array) $settings);
	if (BANG_FS_DEBUG) do_action('log', 'fs: init: faceted search', $get, $options);
	$faceted_search = new BangFacetedSearch($get, $options);
	// $faceted_search->install_global_query();

	// add a meta tag to prevent web crawlers indexing search results pages
  if (!empty(bang_fs_unpaged($get))) {
  	add_action('wp_head', function () {
  		echo "<meta name='robots' content='noindex, nofollow'>\n";
  	});
  }

	// retcon the relevant page as the queried object
	global $bang_fs_loc_page;
	if (empty($loc->uri)) {
		// home?
	} else {
		$bang_fs_loc_page = get_page_by_path($loc->uri);
	}

	// if (!empty($page)) {
	// 	if (BANG_FS_DEBUG) do_action('log', 'fs: init: retcon page', '!ID,post_title', $page);
	// 	global $wp_query, $post;
	// 	if (!empty($wp_query)) {
	// 		if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: init: retcon page: $wp_query exists');
	// 		$wp_query->queried_object = $page;
	// 		$wp_query->queried_object_id = $page->ID;
	// 	}
	// 	$post = $page;
	// 	setup_postdata($post);
	// }

	//  prepare for rendering the search
	wp_enqueue_script('faceted-search');
	wp_enqueue_style('faceted-search');
	wp_enqueue_style( 'dashicons' );

	//  ensure our adjusted query is used in place of the standard one
	add_filter('do_parse_request', 'bang_fs_do_parse_request', 99, 3);
	add_filter('request', 'bang_fs_the_request', 99);
	add_action('wp', 'bang_fs_adjust_search_flags');
	if (!empty($bang_fs_loc_page))
		add_action('wp', 'bang_fs_retcon_loc_page');
	add_action('parse_request', 'bang_fs_parse_request', 99, 1);
	add_action('template_redirect', 'bang_fs_template_redirect', 9);
	add_filter('template_include', 'bang_fs_template_include');

	if (BANG_FS_DEBUG) {
		add_filter('send_headers', 'bang_fs_send_headers');
		// add_filter('query_string', 'bang_fs_query_string', 1, 1);
		// add_filter('query_string', 'bang_fs_query_string', 99, 1);
		// add_filter('pre_get_posts', 'bang_fs_pre_get_posts');
	}
	if (BANG_FS_DEBUG >= 3) {
		// add_filter('split_the_query', 'bang_fs_debug_split_the_query', 10, 2);
		add_filter('posts_results', 'bang_fs_debug_posts_results');
	}

	do_action('bang_fs_init', $settings);
	if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: init complete');
}

add_action('admin_init', 'bang_fs_admin_init');
function bang_fs_admin_init() {
	wp_enqueue_script('faceted-search-admin', plugins_url('scripts/admin/fs-admin.js', BANG_FS_PLUGIN_FILE), array('jquery'));
	wp_enqueue_style('faceted-search-admin', plugins_url('admin.css', BANG_FS_PLUGIN_FILE));
}


//  Unsearch: prevent WordPress from trying to search when it shouldn't
function bang_fs_unsearch_query_vars($query_vars) {
	if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Unsearch: Query vars', $query_vars);
	unset($query_vars['s']);
	return $query_vars;
}

function bang_fs_unsearch_request($request) {
	if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Unsearch: Request', $request);
	unset($request['s']);
	return $request;
}


//  adjust the search query... by one means or another
function bang_fs_do_parse_request($yes, $wp, $extra_query_vars) {
	if (!is_faceted_search()) return $yes;
	return true;
}

function bang_fs_the_request($request) {
	if (!is_faceted_search()) return $request;
	global $faceted_search;
	if (empty($faceted_search) || empty($faceted_search->query_args)) return $request;
	if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: request: Old request', $request);
	if (BANG_FS_DEBUG) do_action('log', 'fs: request: Setting request', $faceted_search->query_args);
	return $faceted_search->query_args;
}


function bang_fs_parse_request(&$wp) {
	if (!is_faceted_search()) return;

	// do_action('log', 'fs: parse_request', '@trace');
	global $faceted_search;
	if (!empty($faceted_search) && isset($faceted_search->query_args)) {
		if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: parse_request: About to adjust $wp', '!query_vars', $wp);
		$wp->query_vars = $faceted_search->query_args;
		if (BANG_FS_DEBUG) do_action('log', 'fs: parse_request: query_vars', '!query_vars', $wp);
	}
	bang_fs_adjust_search_flags();

	// paranoia check!
	if (has_filter('query_string')) {
		if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Paranoia check! Query string will flatten parameters!');
		add_action('parse_query', 'bang_fs_parse_query_once');
	}
}

function bang_fs_parse_query_once(&$wp_query) {
	if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Once off - parse_query');
	remove_action('parse_query', 'bang_fs_parse_query_once');
	if (!is_faceted_search()) return;

	global $faceted_search;
	if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Comparing our query', $faceted_search->query_args);
	if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Comparing their query', $wp_query->query);
	$wp_query->parse_query($faceted_search->query_args);
}


//  adjust the search flags
function bang_fs_template_redirect() {
	if (!is_faceted_search()) return;
	if (BANG_FS_DEBUG) do_action('log', 'fs: template_redirect');

	bang_fs_adjust_search_flags();
}

function bang_fs_adjust_search_flags() {
	global $wp_query;
	$wp_query->is_search = true;

	$wp_query->is_404 = false;
	$wp_query->is_home = false;
	$wp_query->is_preview = false;
	$wp_query->is_singular = false;
	$wp_query->is_single = false;
	$wp_query->is_page = false;
	$wp_query->is_attachment = false;
	$wp_query->is_archive = false;

	$wp_query->is_date = false;
	// $wp_query->is_year = false;
	// $wp_query->is_month = false;
	// $wp_query->is_day = false;
	// $wp_query->is_time = false;
	// $wp_query->is_author = false;

	// $wp_query->is_category = false;
	// $wp_query->is_tag = false;
	// $wp_query->is_tax = false;

	$wp_query->is_feed = false;
	$wp_query->is_comment_feed = false;
	$wp_query->is_trackback = false;
	// $wp_query->is_comments_popup = false;
	$wp_query->is_admin = false;
	// $wp_query->is_robots = false;
}

function bang_fs_retcon_loc_page() {
	global $bang_fs_loc_page, $wp_query, $post;

	if (BANG_FS_DEBUG) do_action('log', 'fs: init: retcon page', '!ID,post_title', $bang_fs_loc_page);

	if (!empty($wp_query)) {
		if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: init: retcon page: $wp_query exists');
		$wp_query->queried_object = $bang_fs_loc_page;
		$wp_query->queried_object_id = $bang_fs_loc_page->ID;
	}
	$post = $bang_fs_loc_page;
	setup_postdata($post);
}

//  direct to the right template
function bang_fs_template_include($template) {
	// if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: template_include: %s; is faceted search: %s', $template, is_faceted_search());
	if (!is_faceted_search()) return $template;

	$options = bang_fs_options();
	if (is_string($options->template) && !empty($options->template)) {
		$template = locate_template($options->template);
		if (BANG_FS_DEBUG) do_action('log', 'fs: template_include: Redirecting to', $template);
	}

	return $template;
}



/*
	bang_fs_options()
	Get the options currently in effect.
	If a faceted search has been started, it gets the options from there; otherwise is uses the global settings.
*/


function bang_fs_options($args = array()) {
	$settings = bang_fs_settings();
	global $faceted_search;
	$options = isset($faceted_search) ? (object) $faceted_search->options : bang_fs_settings();
	if (!empty($args))
		$options = (object) wp_parse_args((array) $args, (array) $options);

	$loc = bang_fs_location();
	if (!empty($loc)) {
		$options = (object) wp_parse_args((array) $loc, (array) $options);
	}
	return $options;
}


function bang_fs_get_fresh($args = array(), $options = array()) {
	if (BANG_FS_GET_DEBUG) do_action('log', 'fs: get: Fresh search', $args);
	return bang_fs_get($args, $options, true);
}

function bang_fs_get($args = array(), $options = array(), $fresh = false) {
	global $faceted_search, $bang_fs_override_args;
	$options = bang_fs_options($options);

	// get current parameters
	if (isset($faceted_search) && !$fresh) {
		$get = $faceted_search->get;
		// if (BANG_FS_GET_DEBUG) do_action('log', 'fs: get: Inherited search', $get);
	} else {
		$get = array();
		foreach ($_GET as $key => $value) {
			$key = sanitize_title($key);
			$get[$key] = stripslashes(sanitize_text_field($value));
		}

		if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Checking get against escape params: %s, %s', $options->escape_params, $get);
		foreach ($options->escape_params as $param) {
	    if (isset($get[$param])) {
	    	if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Escaping');
	    	// definitely not a search!
	    	define('BANG_FACETED_SEARCH', false);
				return array();
			}
		}
		// if (isset($_get['s'])) {
		// 	$_get['s'] = preg_replace('/\'(s|re|m)/i', '', $_get['s']);
		// }

		if (BANG_FS_GET_DEBUG) do_action('log', 'fs: get: GET = %s, Defaults = %s, Force = %s', $get, $options->defaults, $options->force);
		if (isset($options->defaults))
			$get = wp_parse_args((array) $get, (array) $options->defaults);
		if (isset($options->force))
			$get = wp_parse_args((array) $options->force, (array) $get);
		if (BANG_FS_GET_DEBUG) do_action('log', 'fs: get: Fresh search', $get);
	}
	$get = wp_parse_args((array) $args, (array) $get);

	if (!empty($bang_fs_override_args)) {
		$get = wp_parse_args((array) $bang_fs_override_args, (array) $get);
	}

	// give themes and other plugins to adjust our parameters
	$get = bang_fs_canonical_get($get);
	$get = apply_filters('bang_fs_get', $get);
	if (BANG_FS_GET_DEBUG) do_action('log', 'fs: get: Filtered', $get);

	// ignore certain parameters
	$settings = bang_fs_settings();
	$ignore = array_values(array_filter(explode("\n", $settings->ignore)));
	$ignore = apply_filters('bang_fs_ignore', $ignore);
	$ignore = array_fill_keys($ignore, true);
	$get = array_diff_key($get, $ignore);

	// tidy up pagination parameters
	$get = bang_fs_paged($get);

	// block parameters
	// foreach ($options->block as $block) unset($get[$block]);
	foreach ($get as $key => $value) if (empty($value)) unset($get[$key]);
	// if (BANG_FS_GET_DEBUG) do_action('log', 'fs: get: Adjusted search', $get);
	return $get;
}

function bang_fs_paged($get, $force = false) {
	if (BANG_FS_GET_DEBUG && BANG_FS_DEBUG >= 2) do_action('log', 'fs: Paged: before', $get);
	$options = bang_fs_settings();
	if (isset($get['nopaging']) && $get['nopaging']) {
		foreach ($options->block_paged as $block) unset($get[$block]);
	} else {
		$page = isset($get['page']) ? (int) $get['page'] : (isset($get['paged']) ? (int) $get['paged'] : null);
		$offset = isset($get['offset']) ? (int) $get['offset'] : null;
		$posts_per_page = isset($get['posts_per_page']) ? (int) $get['posts_per_page'] : (isset($get['numberposts']) ? (int) $get['numberposts'] : null);

		if ($force) {
			if (is_null($posts_per_page))
				$posts_per_page = is_null($options->posts_per_page) ? get_option('posts_per_page') : (int) $options->posts_per_page;
			if (is_null($offset) && is_null($page))
				$page = 1;
		}
		if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: '.($force ? 'Forcing' : 'Setting').' page params: page = %s, offset = %s, size = %s', $page, $offset, $posts_per_page);
		foreach ($options->block_paged as $block) unset($get[$block]);
		if (!is_null($page))
			$get['paged'] = $page;
		if (!is_null($offset))
			$get['offset'] = $offset;
		if (!is_null($posts_per_page))
			$get['posts_per_page'] = $posts_per_page;
	}
	if (BANG_FS_GET_DEBUG && BANG_FS_DEBUG >= 2) do_action('log', 'fs: Paged: after', $get);
	return $get;
}

function bang_fs_paged_only($get) {
	$get = bang_fs_paged($get);
	return array(
		'offset' => isset($get['offset']) ? absint($get['offset']) : 0,
		'posts_per_page' => isset($get['posts_per_page']) ? absint($get['posts_per_page']) : absint(get_option('posts_per_page')),
	);
}

function bang_fs_get_unpaged($args = array()) {
	$args['nopaging'] = true;
	return bang_fs_unpaged(bang_fs_get($args));
}

function bang_fs_unpaged($get) {
	$options = bang_fs_options();
	foreach ($options->block_paged as $block) unset($get[$block]);
	return $get;
}

function bang_fs_canonical_get($get) {
		if (BANG_FS_GET_DEBUG) do_action('log', 'fs: Canonical args', $get);
	// tidy up the dates
	$date_fields = array('year' => true, 'month' => true, 'week' => true, 'day' => true, 'date' => true);
	$dates = array_intersect_key($get, $date_fields);
	if (count($dates) > 1) {
		if (BANG_FS_GET_DEBUG) do_action('log', 'fs: Canonical date args', $dates);
		$get = array_diff_key($get, $date_fields);
		$year = false; $month = false; $week = false; $day = false;

		// extract the values flexibly
		if (!empty($dates['year']))
			$year = intval($dates['year']);

		if (!empty($dates['month'])) {
			if (is_numeric($dates['month'])) {
				$month = intval($dates['month']);
			} else {
				list($year, $month) = explode('-', $dates['month']);
			}
		}

		if (!empty($dates['week'])) {
			if (is_numeric($dates['week'])) {
				$week = intval($dates['week']);
			} else {
				list($year, $week) = explode('-', $dates['week']);
			}
		}

		if (empty($dates['day']) && !empty($dates['date']))
			$dates['day'] = $dates['date'];
		if (!empty($dates['day'])) {
			if (is_numeric($dates['day'])) {
				$day = intval($dates['day']);
			} else {
				list($year, $month, $day) = explode('-', $dates['day']);
			}
		}

		// rebuild the URL fields
		if (BANG_FS_GET_DEBUG) do_action('log', 'fs: Canonical date args: year = %s, month = %s, week = %s, day = %s', $year, $month, $week, $day);
		unset($get['day']);
		unset($get['week']);
		unset($get['month']);
		unset($get['year']);

		if (!empty($year)) {
			if (!empty($month)) {
				if (!empty($day))
					$get['day'] = sprintf('%4d-%02d-%02d', $year, $month, $day);
				else
					$get['month'] = sprintf('%4d-%02d', $year, $month);
			} else if (!empty($week)) {
				$get['week'] = sprintf('%4d-%02d', $year, $week);
			} else {
				$get['year'] = sprintf('%4d', $year);
			}
		}
	}

	return $get;
}




//    DEBUG ONLY

function bang_fs_send_headers($wp) {
	if (BANG_FS_DEBUG) {
		do_action('log', 'fs: send_headers: query vars', $wp->query_vars);
	}
}

function bang_fs_query_string($query_string) {
	do_action('log', 'fs: query_string', $query_string);
	return $query_string;
}

function bang_fs_debug_split_the_query($split_the_query, $wp_query) {
	do_action('log', 'fs: DEBUG', $wp_query->request);
	return $split_the_query;
}

function bang_fs_debug_posts_results($results) {
	do_action('log', 'fs: DEBUG: Found %s results', count($results));
	return $results;
}

function bang_fs_query_vars($query_vars) {
	return $query_vars;
}
