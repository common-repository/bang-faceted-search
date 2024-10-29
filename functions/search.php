<?php

/*
	Actually do the search
*/

function bang_faceted_search($get = array(), $options = array()) {
	global $faceted_search, $wp_query;
	// no parameters means they're just asking for a copy of the current search
	if (empty($get) && empty($options) && isset($faceted_search))
		return bang_fs_safe_wrapper($faceted_search);

	$options = bang_fs_options($options);
	$options->get = $get;
	$get = bang_fs_get_fresh($get, $options);
	if (BANG_FS_DEBUG) do_action('log', 'fs: Faceted search', $get, $options);

	bang_fs_show_facets();
	if (!empty($faceted_search)) {
		if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Comparing against previous search', $faceted_search->get, $get);
		if ($faceted_search->get == $get)
			return bang_fs_safe_wrapper($faceted_search);
	} else if (!empty($wp_query)) {
		$cquery = apply_filters('bang_fs_query', $get, $get, $options);
		if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Comparing against $wp_query', $wp_query->query, $cquery);
		if ($wp_query->query == $cquery) {
			$faceted_search = bang_fs_fabricate($get, $options, $wp_query);
			return bang_fs_safe_wrapper($faceted_search);
		}
	}

	if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Create new', $get, $options);
	$faceted_search = new BangFacetedSearch($get, $options);
	do_action_ref_array('bang_fs_create', array(&$faceted_search));
	return bang_fs_safe_wrapper($faceted_search);
}

function bang_fs_count() {
	global $faceted_search;
	if (!isset($faceted_search))
		$faceted_search = bang_faceted_search();
	return $faceted_search->count();
}

function is_faceted_search() {
	if (defined('BANG_FACETED_SEARCH') && BANG_FACETED_SEARCH)
		return true;

	global $faceted_search;
	if (isset($faceted_search))
		return true;

	$get = bang_fs_get_unpaged();
	unset($get['s']);
	return !empty($get);
}

function bang_fs_instance() {
	global $faceted_search, $wp_query;
	if (!empty($faceted_search)) {
		// if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Instance: Wrapping', $faceted_search);
		return bang_fs_safe_wrapper($faceted_search);
	}

	if (!empty($wp_query)) {
		$get = $wp_query->query;
		$options = bang_fs_options();
		if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Instance: Fabricating');
		$faceted_search = bang_fs_fabricate($get, $options, $wp_query);
		// if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Instance: Wrapping', $faceted_search);
		return bang_fs_safe_wrapper($faceted_search);
	}

	return null;
}



function bang_fs_fabricate($get, $options, $wp_query) {
	if (BANG_FS_DEBUG) do_action('log', 'fs: Fabricating based on $wp_query', $wp_query->query);
	$get = bang_fs_deconstruct_get($get);
	$faceted_search = new BangFacetedSearch($get, $options);
	$faceted_search->query =& $wp_query;
	$faceted_search->count = $wp_query->found_posts;

	// fabricate results
	if (isset($wp_query->posts)) {
		if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Fabricating results too', '!ID,post_title,relevance_score', $wp_query->posts);

		$fq = (object) $faceted_search->query_args;
		if (!$fq->posts_per_page)
			$fq->posts_per_page = get_option('posts_per_page');
		if ($fq->paged > 0)
			$fq->start = ($fq->paged - 1) * $fq->posts_per_page;
		else if (isset($fq->offset))
			$fq->start = $fq->offset;
		else
			$fq->start = 0;
		$fq->end = $fq->start + $fq->posts_per_page;

		$wq = (object) $wp_query->query_vars;
		if (!$wq->posts_per_page)
			$wq->posts_per_page = get_option('posts_per_page');
		if ($wq->paged > 0)
			$wq->start = ($wq->paged - 1) * $wq->posts_per_page;
		else if (isset($wq->offset))
			$wq->start = $wq->offset;
		else
			$wq->start = 0;
		$wq->end = $wq->start + $wq->posts_per_page;

		if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Fabricating: Subset %s from %s', '!start,end', $fq, '!start,end', $wq);

		if ($fq->start == $wq->start && $fq->end == $wq->end) {
			if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Fabricating: All posts');
			$faceted_search->posts =& $wp_query->posts;
		} else if ($fq->start >= $wq->start && $fq->end <= $wq->end) {
			$start = $fq->start - $wq->start;
			if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Fabricating: Start at %s', $start);
			$faceted_search->posts = array_slice($wp_query->posts, $start, $fq->posts_per_page);
		} else if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Fabricating: Cannot subset');

		if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Fabricating: selected posts', '!ID', $faceted_search->posts);
	}
	return $faceted_search;
}

function bang_fs_deconstruct_get($get) {
	if (BANG_FS_DEBUG) do_action('log', 'fs: Deconstructing get', $get);
	return apply_filters('bang_fs_deconstruct_get', $get);
}


/*
	Check if a faceted search is in effect
*/

function bang_fs_has_facets () {
	$options = bang_fs_options();
	$get = bang_fs_get();
	return !empty($get);
}


/*
	Parse the current search parameters, extracting terms and custom fields
*/
function bang_fs_active_facets () {
	$options = bang_fs_options();
	$get = bang_fs_get_unpaged();

	foreach ($get as $key => $value) {
		if ($key == 's') continue;

		$tax = get_taxonomy($key);
		if (!empty($tax)) {
			$terms = get_terms($key, array('hide_empty' => 0, 'slug' => $value));
			if (!is_wp_error($terms) && !empty($terms)) {
				$term = $terms[0];
				$get[$key] = $term;
			}
		}
	}
	return $get;
}



/*
	Turn the search parameters into arguments for a WP_Query
*/

add_filter('query_vars', 'bang_fs_public_query_vars');
function bang_fs_public_query_vars($vars) {
	$vars[] = 'week';
	$vars[] = 'month';
	if (BANG_FS_DEBUG >= 3) do_action('log', 'fs: Setting public query vars', $vars);
	return $vars;
}

// by defaut: tidy up the date args
add_filter('bang_fs_query', 'bang_fs_filter_force_args', 1, 3) ;
function bang_fs_filter_force_args($query, $original, $options) {
	if (BANG_FS_DEBUG) do_action('log', 'fs: bang_fs_filter_force_args: get', $query);
	if (BANG_FS_DEBUG && !empty($options->force)) do_action('log', 'fs: Forcing args', $options->force);
	if (!empty($options->force))
		$query = wp_parse_args((array) $query, (array) $options->force);
	if (empty($query['posts_per_page'])) $query['posts_per_page'] = (integer) get_option('posts_per_page');
	if (BANG_FS_DEBUG >= 3) do_action('log', 'fs: bang_fs_query/bang_fs_filter_force_args', $query);
	return $query;
}

add_filter('bang_fs_query', 'bang_fs_filter_process_date_args', 5, 3);
function bang_fs_filter_process_date_args($query, $original, $options) {
	if (isset($query['day'])) {
		$day = $query['day'];
		list($year, $monthnum, $daynum) = explode("-", $day);
		if (isset($daynum)) {
			if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Process date args (day = %s): year = %s, month = %s, daynum = %s', $day, $year, $monthnum, $daynum);
			$query['year'] = $year;
			$query['monthnum'] = $monthnum;
			$query['day'] = $daynum;
		}
	}

	else if (isset($query['week'])) {
		$week = $query['week'];
		list($year, $monthnum, $daynum) = explode("-", $week);
		unset($query['week']);

		global $wpdb;
		$rows = $wpdb->get_results("select week('$week', 1);");
		$row = (array) $rows[0];
		$weeknum = array_pop($row);

		if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Process date args (week = %s): year = %s, week = %s', $week, $year, $w);
		$query['year'] = $year;
		$query['w'] = $weeknum;
	}

	if (isset($query['month'])) {
		$month = $query['month'];
		list($year, $monthnum) = explode("-", $month);
		unset($query['month']);
		if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Process date args (month = %s): year = %s, month = %s', $month, $year, $monthnum);
		$query['year'] = $year;
		$query['monthnum'] = $monthnum;
	}

	if (BANG_FS_DEBUG) do_action('log', 'fs: Process date args: year = %s, month = %s, week = %s, day = %s', '!year,monthnum,w,day', $query);
	if (BANG_FS_DEBUG >= 3) do_action('log', 'fs: bang_fs_query/bang_fs_filter_process_date_args', $query);
	if (BANG_FS_GET_DEBUG) do_action('log', 'fs: Process date args: get', $query);
	return $query;
}

// by default: tidy up the taxonomy args
add_filter('bang_fs_query', 'bang_fs_filter_process_taxonomy_args', 15, 3);
function bang_fs_filter_process_taxonomy_args($query, $original, $options) {
	if (BANG_FS_DEBUG >= 3) do_action('log', 'fs: bang_fs_query/bang_fs_filter_process_taxonomy_args (before)', $query);
	$tq = isset($query['tax_query']) ? $query['tax_query'] : array();
	if (!is_array($tq)) $tq = array();
	$taxonomies = get_taxonomies(array(), 'objects');
	foreach ($taxonomies as $tax) {
		$slug = $tax->name;
		if (isset($query[$slug])) {
			if (BANG_FS_DEBUG >= 3) do_action('log', 'fs: bang_fs_query/bang_fs_filter_process_taxonomy_args: applying to field', $slug);
			$values = explode(',', $query[$slug]);
			$values = array_values(array_filter(array_map('trim', $values)));
			if (!empty($values)) {
				if (count($values) > 1)
					$tq[] = array('taxonomy' => $slug, 'field' => 'slug', 'terms' => $values, 'operator' => 'IN');
				else {
          $value = $values[0];
          if ($value == 'any')
            $tq[] = array('taxonomy' => $slug, 'operator' => 'EXISTS');
          else
  					$tq[] = array('taxonomy' => $slug, 'field' => 'slug', 'terms' => $value);
        }
			}
			unset($query[$slug]);
		}
	}
	if (count($tq) > 1) {
		$tq['relation'] = 'AND';
	}
	$query['tax_query'] = $tq;

	if (BANG_FS_DEBUG && isset($query['tax_query'])) do_action('log', 'fs: Process taxonomy args', $query['tax_query']);
	if (BANG_FS_DEBUG >= 3) do_action('log', 'fs: bang_fs_query/bang_fs_filter_process_taxonomy_args', $query);
	if (BANG_FS_GET_DEBUG) do_action('log', 'fs: Process taxonomy args: get', $query);
	return $query;
}

// by default: sticky
add_filter('bang_fs_query', 'bang_fs_filter_process_sticky_args', 30, 3);
function bang_fs_filter_process_sticky_args($query, $original, $options) {
	// if ($options->sticky)
	// 	$query['nopaging'] = true;
	if (BANG_FS_DEBUG) do_action('log', 'fs: Process sticky args');
	if (BANG_FS_DEBUG >= 3) do_action('log', 'fs: bang_fs_query/bang_fs_filter_process_sticky_args', $query);
	if (BANG_FS_GET_DEBUG) do_action('log', 'fs: Process date args: get', $query);
	return $query;
}

// by default: tidy up paging arguments
add_filter('bang_fs_query', 'bang_fs_paged', 50);



/*
	The opposite: restore a get from some query args
*/

add_filter('bang_fs_deconstruct_get', 'bang_fs_deconstruct_tax_query');
function bang_fs_deconstruct_tax_query($get) {
	if (isset($get['tax_query']) && is_array($get['tax_query'])) {
		foreach ($get['tax_query'] as $key => $tax_query) {
			if (!is_array($tax_query)) continue;
			$taxonomy = $tax_query['taxonomy'];
			$field = $tax_query['field'];
			$terms = $tax_query['terms'];
			$operator = $tax_query['operator'];
			if (is_array($terms)) $terms = implode(',', $terms);

			$get[$taxonomy] = $terms;
		}
	}
	unset($get['tax_query']);
	return $get;
}

add_filter('bang_fs_deconstruct_get', 'bang_fs_deconstruct_meta_query');
function bang_fs_deconstruct_meta_query($get) {
	if (isset($get['meta_query']) && is_array($get['meta_query'])) {
		foreach ($get['meta_query'] as $key => $meta_query) {
			if (!is_array($meta_query)) continue;
			$fieldname = $meta_query['key'];
			$field = $meta_query['field'];
			$value = $meta_query['value'];
			$operator = $meta_query['operator'];
			if (is_array($value)) $value = implode(',', $value);

			$get[$fieldname] = $terms;
		}
	}
	unset($get['meta_query']);
	return $get;
}

add_filter('bang_fs_deconstruct_get', 'bang_fs_deconstruct_date');
function bang_fs_deconstruct_date($get) {
	if (BANG_FS_DEBUG) do_action('log', 'fs: Deconstructing date', $get);
	$year = false; $monthnum = false; $w = false; $day = false;
	if (isset($get['year']) && is_numeric($get['year'])) $year = (int) $get['year'];
	if (isset($get['monthnum']) && is_numeric($get['monthnum'])) $monthnum = (int) $get['monthnum'];
	if (isset($get['w']) && is_numeric($get['w'])) $w = (int) $get['w'];
	if (isset($get['day']) && is_numeric($get['day'])) $day = (int) $get['day'];
	if (BANG_FS_DEBUG) do_action('log', 'fs: Deconstructing date: day = %s, week = %s, month = %s, year = %s', $day, $w, $monthnum, $year);

	if ($day) {
		$get['day'] = "$year-$monthnum-$day";
		unset($get['year']);
		unset($get['monthnum']);
	} else if ($w) {
		$get['week'] = "$year-$monthnum-$w";
		unset($get['year']);
		unset($get['monthnum']);
		unset($get['w']);
	} else if ($monthnum) {
		$get['month'] = "$year-$monthnum";
		unset($get['year']);
		unset($get['monthnum']);
	} else if ($year) {
		$get['year'] = $year;
	}

	if (BANG_FS_DEBUG) do_action('log', 'fs: Deconstructed date: day = %s, week = %s, month = %s, year = %s', $get['day'], $get['week'], $get['month'], $get['year']);
	return $get;
}
