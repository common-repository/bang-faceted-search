<?php

/*
	Special code to make Faceted Search cooperate with the Relevanssi search plugin
*/


if (!defined('BANG_FS_RELEVANSSI_DEBUG'))
  define('BANG_FS_RELEVANSSI_DEBUG', 0);

add_action('init', 'bang_fs_relevanssi_init');
function bang_fs_relevanssi_init() {
	define('BANG_FS_RELEVANSSI', (boolean) function_exists('relevanssi_query'));

	if (BANG_FS_RELEVANSSI) {
		if (BANG_FS_DEBUG) do_action('log', 'fs: Relevanssi is active');
		if (BANG_FS_RELEVANSSI_DEBUG) bang_fs_relevanssi_init_debug();

		add_filter('relevanssi_modify_wp_query', 'bang_fs_relevanssi_modify_wp_query');
		add_filter('relevanssi_default_tax_query_relation', 'bang_fs_relevanssi_default_tax_query_relation');
		add_filter('relevanssi_search_ok', 'bang_fs_relevanssi_search_ok');
		add_filter('relevanssi_where', 'bang_fs_relevanssi_where');
		add_filter('relevanssi_join', 'bang_fs_relevanssi_join');

		add_action('bang_fs_before_query', 'bang_fs_relevanssi_before_query', 99, 1);
		add_action('bang_fs_after_query', 'bang_fs_relevanssi_after_query', 1, 1);

		// swap for Relevanssi results before anything else
		// add_filter('bang_fs_results', 'bang_fs_relevanssi_fs_results_relevanssi_do_query', 1, 4);

		add_filter('relevanssi_match', 'bang_fs_relevanssi_match');
		add_action('relevanssi_results', 'bang_fs_relevanssi_results');
		add_filter('relevanssi_search_filters', 'bang_fs_relevanssi_search_filters');

		add_filter('bang_fs_wpdb', 'bang_fs_relevanssi_wpdb', 10, 3);

		add_filter('bang_fs_count_where', 'bang_fs_relevanssi_count_where', 10, 3);
		add_filter('bang_fs_count_joins', 'bang_fs_relevanssi_count_joins', 10, 3);
		add_filter('bang_fs_count_sql', 'bang_fs_relevanssi_count_sql', 10, 3);

		//  Network search
		// add_filter('relevanssi_index_post_types', 'relevanssi_index_post_types');
		add_filter('option_relevanssi_index_post_types', 'bang_fs_relevanssi_index_post_types');
		add_filter('relevanssi_post_content', 'bang_fs_relevanssi_post_content', 10, 2);
  	// add_filter('relevanssi_excerpt_content', 'bang_fs_relevanssi__excerpt_content', 1, 3);
	}
}

/*
if (!function_exists('relevanssi_set_operator')) {
	function relevanssi_set_operator() {
		return 'OR';
	}
}
*/

function bang_fs_skip_relevanssi() {
	global $bang_fs_relevanssi_latest_wp_query;
	$get = $bang_fs_relevanssi_latest_wp_query;
	if (empty($get))
		$get = bang_fs_get();
	// if (BANG_FS_RELEVANSSI_DEBUG) do_action('log', 'fs: Relevanssi: Skip?  query = %s', $get);
	return empty($get['s']);
}

function bang_fs_relevanssi_modify_wp_query($wp_query) {
	//  adjust page parameters, if relevant
	if (!empty($wp_query->query_vars['page']) && empty($wp_query->query_vars['paged']))
		$wp_query->query_vars['paged'] = (int) $wp_query->query_vars['page'];

	//  adjust post type to include multisite searches
	if (is_multisite()) {
		$settings = bang_fs_settings();
		if ($settings->network->multisite && !is_admin()) {
			$wp_query->query_vars['post_type'] = bang_fs_double_post_types($wp_query->query_vars['post_type']);
		}
	}

	//  lodge the query for later lookup
	if (BANG_FS_RELEVANSSI_DEBUG) do_action('log', 'fs: Relevanssi: Modify WP query', $wp_query);
	global $bang_fs_relevanssi_latest_wp_query;
	$bang_fs_relevanssi_latest_wp_query = $wp_query->query;
	return $wp_query;
}

if (!function_exists('array_equal')) {
	function array_equal($a, $b) {
		return
			is_array($a) && is_array($b) &&
			count($a) == count($b) &&
			array_diff($a, $b) === array_diff($b, $a);
	}
}

// force it use relevanssi_do_query for non-global queries
function bang_fs_relevanssi_fs_results_relevanssi_do_query($posts, $get, $options, $query = null) {
	if (isset($query->query['suppress_filters']) && $query->query['suppress_filters'])
		return $posts;

	if (!empty($query)) {
    if (empty($query->query['s']))
      return $posts;

		global $wp_query;
		if (empty($wp_query) || !array_equal($query->query, $wp_query->query)) {
			if (BANG_FS_RELEVANSSI_DEBUG) do_action('log', 'Calling relevanssi_do_query(%s)', $query->query);
			$wp_query = apply_filters('relevanssi_modify_wp_query', $query);
			$posts = relevanssi_do_query($query);
		}
	}

	return $posts;
}

function bang_fs_relevanssi_default_tax_query_relation($relation) {
	return 'AND';
}


// hoops to jump through to capture the found_posts...
class BangFacetedSearchRelevanssiHook {
	var $fs;
	var $found_posts;
	var $max_num_pages;

	function __construct(&$fs) {
		$this->fs = &$fs;
	}

	function hits_filter ($filter_data) {
		// do_action('log', 'fs: Relevanssi: lodging found_posts', sizeof($filter_data[0]));
		$this->found_posts = sizeof($filter_data[0]);
		return $filter_data;
	}
}

function bang_fs_relevanssi_before_query(&$fs) {
	// do_action('log', 'fs: Relevanssi: adding hook hits_filter');
	if (!isset($fs->relevanssi_hook))
		$fs->relevanssi_hook = new BangFacetedSearchRelevanssiHook($fs);
	add_action('relevanssi_hits_filter', array(&$fs->relevanssi_hook, 'hits_filter'), 999);
	return true;
}

function bang_fs_relevanssi_after_query(&$fs) {
	// do_action('log', 'fs: Relevanssi: removing hook hits_filter');
	remove_action('relevanssi_hits_filter', array(&$fs->relevanssi_hook, 'hits_filter'), 999);

	// do_action('log', 'fs: Relevanssi: reinstating found_posts', $fs->relevanssi_hook->found_posts);
	$fs->query->found_posts = $fs->relevanssi_hook->found_posts;
}


function bang_fs_relevanssi_search_ok($ok) {
	global $relevanssi_active;
	// if (BANG_FS_RELEVANSSI_DEBUG) do_action('log', 'fs: Relevanssi: search ok? %s, relevanssi_active = %s', $ok, $relevanssi_active);

	if (bang_fs_skip_relevanssi())
		return false;

	if (bang_fs_in_progress()) {
		$relevanssi_active = false; // allow Relevanssi to start now
		return true;
	}
	return $ok;
}

function bang_fs_relevanssi_match($match, $idf = null) {
  static $stickies;
  if (!is_array($stickies)) {
    $stickies = get_option( 'sticky_posts' );
    if (!is_array($stickies))
      $stickies = array();
  }

	if (in_array($match->doc, $stickies)) {
		$weight = $match->weight + 5;
		$match->weight = $weight * $weight;
	}
	return $match;
}

function bang_fs_relevanssi_search_filters($values_to_filter) {
 	if (BANG_FS_RELEVANSSI_DEBUG) do_action('log', 'fs: Relevanssi: Adjusting values to filter', $values_to_filter);

 	//  the $values_to_filter query contains Relevanssi-specific fields,
 	//  so we also grab the originating query to compare against
	global $bang_fs_relevanssi_latest_wp_query;
	$q2 = $bang_fs_relevanssi_latest_wp_query;
 	if (BANG_FS_RELEVANSSI_DEBUG >= 2) do_action('log', 'fs: Relevanssi: Latest query', $q2);

	$options = bang_fs_options();
	if (!empty($options->force['post_type'])) {
		$values_to_filter['post_type'] = $options->force['post_type'];
	} else if (!empty($options->post_type)) {
		$values_to_filter['post_type'] = $options->post_type;
	}

	//  pre-process date args
	if (!empty($values_to_filter['year'])) {
		$values_to_filter['year'] = (int) $values_to_filter['year'];
	} else if (!empty($q2['year'])) {
		do_action('log', 'YEAR = %s', $q2['year']);
		$values_to_filter['year'] = (int) $q2['year'];
	}

	if (!empty($values_to_filter['month'])) {
		if (preg_match('/([0-9]{4})-([0-9]+)/', $values_to_filter['month'], $match)) {
			$values_to_filter['year'] = (int) $match[1];
			$values_to_filter['monthnum'] = (int) $match[2];
		} else if (is_numeric($values_to_filter['month'])) {
			$values_to_filter['monthnum'] = (int) $values_to_filter['month'];
		}
		unset($values_to_filter['month']);
	} else if (!empty($q2['month'])) {
		do_action('log', 'MONTH = %s', $q2['month']);
		if (preg_match('/([0-9]{4})-([0-9]+)/', $q2['month'], $match)) {
			do_action('log', 'MATCH', $match);
			$values_to_filter['year'] = (int) $match[1];
			$values_to_filter['monthnum'] = (int) $match[2];
		} else if (is_numeric($q2['month'])) {
			do_action('log', 'NUMBER', $q2['month']);
			$values_to_filter['monthnum'] = (int) $q2['month'];
		} else {
			do_action('log', 'NO MATCH :(');
		}
	} else if (!empty($q2['monthnum'])) {
		do_action('log', 'MONTH = %s', $q2['monthnum']);
		if (preg_match('/([0-9]{4})-([0-9]+)/', $q2['monthnum'], $match)) {
			do_action('log', 'MATCH', $match);
			$values_to_filter['year'] = (int) $match[1];
			$values_to_filter['monthnum'] = (int) $match[2];
		} else if (is_numeric($q2['monthnum'])) {
			do_action('log', 'NUMBER', $q2['monthnum']);
			$values_to_filter['monthnum'] = (int) $q2['monthnum'];
		} else {
			do_action('log', 'NO MATCH :(');
		}
	}

	if (!empty($values_to_filter['date'])) {
		if (preg_match('/([0-9]{4})-([0-9]+)-([0-9]+)/', $values_to_filter['date'], $match)) {
			$values_to_filter['year'] = (int) $match[1];
			$values_to_filter['monthnum'] = (int) $match[2];
			$values_to_filter['day'] = (int) $match[3];
		} else if (is_numeric($values_to_filter['date'])) {
			$values_to_filter['day'] = (int) $values_to_filter['date'];
		}
		unset($values_to_filter['date']);
	} else if (!empty($values_to_filter['day'])) {
		if (preg_match('/([0-9]{4})-([0-9]+)-([0-9]+)/', $values_to_filter['day'], $match)) {
			$values_to_filter['year'] = (int) $match[1];
			$values_to_filter['monthnum'] = (int) $match[2];
			$values_to_filter['day'] = (int) $match[3];
		} else if (is_numeric($values_to_filter['day'])) {
			$values_to_filter['day'] = (int) $values_to_filter['day'];
		} else {
			unset($values_to_filter['day']);
		}
	} else if (!empty($q2['date'])) {
		if (preg_match('/([0-9]{4})-([0-9]+)-([0-9]+)/', $q2['date'], $match)) {
			$values_to_filter['year'] = (int) $match[1];
			$values_to_filter['monthnum'] = (int) $match[2];
			$values_to_filter['day'] = (int) $match[3];
		} else if (is_numeric($q2['date'])) {
			$values_to_filter['day'] = (int) $q2['date'];
		}
	} else if (!empty($q2['day'])) {
		if (preg_match('/([0-9]{4})-([0-9]+)-([0-9]+)/', $q2['day'], $match)) {
			$values_to_filter['year'] = (int) $match[1];
			$values_to_filter['monthnum'] = (int) $match[2];
			$values_to_filter['day'] = (int) $match[3];
		} else if (is_numeric($q2['day'])) {
			$values_to_filter['day'] = (int) $q2['day'];
		}
	}

	if (isset($values_to_filter['year']) || isset($values_to_filter['monthnum']) || isset($values_to_filter['w']) || isset($values_to_filter['day'])) {
		$date_query = array(
			'year' => $values_to_filter['year'],
			'month' => $values_to_filter['monthnum'],
			'week' => $values_to_filter['w'],
			'day' => $values_to_filter['day'],
		);
		unset($values_to_filter['year']);
		unset($values_to_filter['monthnum']);
		unset($values_to_filter['w']);
		unset($values_to_filter['day']);
		$date_query = array_filter($date_query);
		if (!empty($date_query))
			$values_to_filter['date_query'] = new WP_Date_Query($date_query);
	}

	// Build a tax query
	if (!empty($values_to_filter['tax_query']))
		$values_to_filter['tax_query'] = new WP_Tax_Query($values_to_filter['tax_query']);

 	if (BANG_FS_RELEVANSSI_DEBUG >= 2) do_action('log', 'fs: Relevanssi: Adjusted values to filter', $values_to_filter);
	global $bang_fs_relevanssi_latest_values_to_filter;
	$bang_fs_relevanssi_latest_values_to_filter = $values_to_filter;

	// if (isset($values_to_filter['tax_query']) && count($values_to_filter['tax_query']) > 1 && empty($values_to_filter['tax_query']['relation']))
		// $values_to_filter['tax_query']['relation'] = 'AND';
	return $values_to_filter;
}

function bang_fs_relevanssi_join($join) {
	if (BANG_FS_RELEVANSSI_DEBUG >= 2) do_action('log', 'fs: Relevanssi: Join', $join);

	global $bang_fs_relevanssi_latest_values_to_filter, $wpdb, $bang_fs_relevanssi_tx;
	$q = $bang_fs_relevanssi_latest_values_to_filter;
	if (BANG_FS_RELEVANSSI_DEBUG >= 2) do_action('log', 'fs: Relevanssi: Join: Latest query', $q);
	if (!empty($q)) {
		// if (is_callable('link_flow__optimise_tax_query'))
		// 	$q = link_flow__optimise_tax_query($q);

	  if ($q['tax_query'] instanceof WP_Tax_Query) {
	  	if (!isset($bang_fs_relevanssi_tx))
	  		$$bang_fs_relevanssi_tx = $q['tax_query']->get_sql('relevanssi', 'doc');
	  	if (BANG_FS_RELEVANSSI_DEBUG >= 3) do_action('log', 'fs: Relevanssi: Join: from WP_Tax_Query', $bang_fs_relevanssi_tx);
	  	else if (BANG_FS_RELEVANSSI_DEBUG >= 2) do_action('log', 'fs: Relevanssi: Join: from WP_Tax_Query', $bang_fs_relevanssi_tx['join']);
	  	$join = $join.' '.$bang_fs_relevanssi_tx['join'];
	    // foreach ($q['tax_query'] as $tax_query) {
	    // 	if (!is_array($tax_query))
	    // 		continue;
	    //   $name = $tax_query['name'];
	    //   $join = "$join INNER JOIN $wpdb->term_relationships AS $name ON relevanssi.doc = $name.object_id";
	    // }
	  }

	  // if (is_callable('link_flow__optimise__query_join'))
	  // 	$join = link_flow__optimise__query_join($join, $q);
	}


	if (BANG_FS_RELEVANSSI_DEBUG >= 2) do_action('log', 'fs: Relevanssi: Joined', $join);
	return $join;
}

function bang_fs_relevanssi_where($where) {
	global $bang_fs_relevanssi_latest_values_to_filter;
	$q = $bang_fs_relevanssi_latest_values_to_filter;
	if (BANG_FS_RELEVANSSI_DEBUG >= 2) do_action('log', 'fs: Relevanssi: Where', $where);
	if (BANG_FS_RELEVANSSI_DEBUG >= 2) do_action('log', 'fs: Relevanssi: Where: Latest query', $q);

	if (!empty($q)) {
		global $wpdb, $bang_fs_relevanssi_tx;
		$conditions = array();

		$posts = $wpdb->posts;
		if (preg_match('/'.$wpdb->posts.'\s+as\s+([a-zA-Z0-9_-]+)/i', $where, $match)) {
			$posts = $match[1];
		}
		if (BANG_FS_RELEVANSSI_DEBUG >= 2) do_action('log', 'fs: Relevanssi: Where: Table name', $posts);

		if (!empty($q['year'])) {
			$year = (int) $q['year'];
			$conditions[] = "YEAR($posts.post_date)='$year'";
		}

		if (!empty($q['monthnum'])) {
			$month = (int) $q['monthnum'];
			$conditions[] = "MONTH($posts.post_date)='$month'";
		}

		if (!empty($q['day'])) {
			$day = (int) $q['day'];
			$conditions[] = "DAYOFMONTH($posts.post_date)='$day'";
		}

		if (!empty($q['hour'])) {
			$hour = (int) $q['hour'];
			$conditions[] = "HOUR($posts.post_date)='$hour'";
		}

		if (!empty($q['minute'])) {
			$minute = (int) $q['minute'];
			$conditions[] = "MINUTE($posts.post_date)='$minute'";
		}

		if (!empty($q['second'])) {
			$second = (int) $q['second'];
			$conditions[] = "SECOND($posts.post_date)='$second'";
		}

		$conditions = array_filter($conditions);
		if (!empty($conditions)) {
			if (BANG_FS_RELEVANSSI_DEBUG >= 2) do_action('log', 'fs: Relevanssi: Where: Conditions', $conditions);
			// if (BANG_FS_RELEVANSSI_DEBUG >= 2) do_action('log', 'fs: Relevanssi: Where: Replace', '/('.$wpdb->posts.'.*WHERE)/');
			$conditions = '('.implode(') AND (', $conditions).')';
			$where = preg_replace('/('.$wpdb->posts.'.*WHERE)/s', '$1 '.$conditions.' AND', $where);
			// $where = $where.' AND '.$conditions;
			if (BANG_FS_RELEVANSSI_DEBUG >= 2) do_action('log', 'fs: Relevanssi: Where: Adjusted', $where);
		}

		//  taxonomy
		if ($q['tax_query'] instanceof WP_Tax_Query) {
			if (!isset($bang_fs_relevanssi_tx))
				$bang_fs_relevanssi_tx = $q['tax_query']->get_sql('relevanssi', 'doc');
			if (BANG_FS_RELEVANSSI_DEBUG >= 3) do_action('log', 'fs: Relevanssi: Where: from WP_Tax_Query', $bang_fs_relevanssi_tx);
	  	else if (BANG_FS_RELEVANSSI_DEBUG >= 2) do_action('log', 'fs: Relevanssi: Where: from WP_Tax_Query', $bang_fs_relevanssi_tx['where']);

	  	if (!empty($bang_fs_relevanssi_tx['where']))
	  		$where = $where.' '.$bang_fs_relevanssi_tx['where'];
		}

		// if (!empty($q['tax_query'])) {
		// 	if (BANG_FS_RELEVANSSI_DEBUG >= 2) do_action('log', 'fs: Relevanssi: Where: Join tax query', $q['tax_query']);
		// 	$conditions = array();
		// 	$wp_tax_query = new WP_Tax_Query($q['tax_query']);
		// 	$wp_tax_query->transform_query('term_taxonomy_id');
		// 	$
		// 	foreach ($wp_tax_query->queries as $tax_query) {
	 //    	// if ($key == 'relation')
	 //    	// 	continue;
	 //    	$name = $tax_query['name'];
	 //      $field = $tax_query['field'];
	 //      if ($field == 'id') $field = 'term_id';
	 //      $terms = array_values(array_filter($tax_query['terms']));
	 //      $terms =

		// 		if (BANG_FS_RELEVANSSI_DEBUG >= 2) do_action('log', 'fs: Relevanssi: Where: Join: %s.%s = %s', $name, $field, $terms);
	 //      if (empty($terms))
	 //      	continue;
	 //      if (count($terms) == 1) {
	 //      	$term = $terms[0];
	 //      	$conditions[] = "$name.term_taxonomy_id = $term";
	 //      } else {
		//       $terms = implode(',', $terms);
		//       $conditions[] = "$name.term_taxonomy_id IN ($terms)";
		//     }
		// 	}
		// 	if (BANG_FS_RELEVANSSI_DEBUG >= 2) do_action('log', 'fs: Relevanssi: Where: Join conditions', $conditions);
		// 	$conditions = array_filter($conditions);
		// 	if (!empty($conditions)) {
		// 		$conditions = '('.implode(') AND (', $conditions).')';
		// 		$where = "$where AND $conditions";
		// 		if (BANG_FS_RELEVANSSI_DEBUG >= 2) do_action('log', 'fs: Relevanssi: Where: With joins', $where);
		// 	}
		// }
	}

	return $where;
}

function bang_fs_relevanssi_wpdb($db) {
	if (bang_fs_skip_relevanssi()) return $db;

	global $wpdb, $bang_fs_db;
	$db->posts2 = $db->posts;
	$db->posts = $wpdb->prefix."relevanssi";
	$db->id = "doc";
	$bang_fs_db = $db;
	return $db;
}

function bang_fs_relevanssi_count_where($where, $db, $joins) {
	if (bang_fs_skip_relevanssi()) return $where;

	if (BANG_FS_RELEVANSSI_DEBUG >= 2) do_action('log', 'fs: Relevanssi: Adjusting where', $where);
	$w2 = array();
	$wterms = array();
	foreach ($where as $w) {
		if (preg_match('/\.post_title like \'%([^ ]*)%\'/', $w, $match)) {
			$terms = preg_split('/[^a-zA-Z0-9]/', $match[1]);
			$terms = array_filter($terms);
			$terms = array_map('strtolower', $terms);
			foreach ($terms as $term)
				$wterms[] = "$db->posts.term = '$term'";
		} else
			$w2[] = $w;
	}
	$where = $w2;
	if (!empty($wterms)) {
		$where[] = '('.implode(' or ', $wterms).')';
	}
	$where = array_map('bang_fs_relevanssi_count_replace_where', $where);
	return $where;
}

function bang_fs_relevanssi_count_replace_where($where) {
	global $bang_fs_db;
	return preg_replace('/('.$bang_fs_db->posts.').(post_status|post_author|post_title|post_content|post_date|post_name|post_type)/', $bang_fs_db->posts2.'.\2', $where);
}

function bang_fs_relevanssi_count_joins($joins, $db, $where) {
	if (bang_fs_skip_relevanssi()) return $joins;

	array_unshift($joins, "inner join {$db->posts2} on {$db->posts}.{$db->id} = {$db->posts2}.ID");
	// $joins[] = "inner join {$db->posts2} on {$db->posts}.{$db->id} = {$db->posts2}.ID";
	// $joins = array_map('bang_fs_relevanssi_count_replace_where', $joins);
	// if (BANG_FS_DEBUG) do_action('log', 'fs: Relevanssi: Adjusting joins', $joins);
	return $joins;
}

function bang_fs_relevanssi_count_sql($sql, $db) {
	if (bang_fs_skip_relevanssi()) return $sql;

	$sql = preg_replace('/count\(/', 'count(distinct ', $sql);
	$sql = preg_replace('/('.$db->posts.').(post_status|post_author|post_title|post_content|post_date|post_name)/', $db->posts2.'.\2', $sql);
	return $sql;
}


function bang_fs_relevanssi_results($results) {
	global $bang_fs_relevanssi_lodge_latest_query;
	$q = $bang_fs_relevanssi_lodge_latest_query;
	// if (!empty($q)) {
	// 	do_action('log', 'fs: Relevanssi: Filtering results', $q);
	// }

	// $filters = false;
	// if (!empty($q['year']))
	// 	$filters['year'] = $q['year'];
	// if (!empty($q['month']))
	// 	$filters['month'] = $q['month'];

	// // if we really must do this...
	// if (!empty($filters)) {
	// 	do_action('log', 'fs: Relevanssi: Filtering results', $filters);
	// 	$approved = array();
	// }

	return $results;
}
