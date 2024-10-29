<?php

class BangFacetedSearch {
	var $get;
	var $options;
	var $query_args;
	var $query;

	var $posts;
	var $count;

	var $is_global;

	function __construct($get, $options) {
		if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Create', '@trace');
		if (BANG_FS_DEBUG) do_action('log', 'fs: Query get', $get);
		$this->get = $get;
		$this->options = (object) $options;

		// defaults and constant args
		$query_args = $this->get;
		if (!empty($this->options->defaults))
			$query_args = wp_parse_args($query_args, $this->options->defaults);
		if (!empty($this->options->force))
			$query_args = wp_parse_args($this->options->force, $query_args);
		if (BANG_FS_GET_DEBUG) do_action('log', 'fs: Query args (forced)', $query_args);

		if (!empty($this->options->post_types)) {
			if (isset($query_args['post_type']) && is_string($query_args['post_type']))
				$query_args['post_type'] = array($query_args['post_type']);
			if (BANG_FS_DEBUG) do_action('log', 'fs: Post types', $query_args['post_type'], $this->options->post_types);
			if (!empty($query_args['post_type']))
				$query_args['post_type'] = array_intersect($query_args['post_type'], $this->options->post_types);

			if (empty($query_args['post_type']))
				$query_args['post_type'] = $this->options->post_types;
		}

		// special cases and overrides
		if (BANG_FS_GET_DEBUG && BANG_FS_DEBUG >= 2) do_action('log', 'fs: Query', '@filter', 'bang_fs_query');
		$query_args = apply_filters('bang_fs_query', $query_args, $this->get, $this->options);
		if (BANG_FS_GET_DEBUG) do_action('log', 'fs: Query args (filtered)', $query_args);
		$query_args = bang_fs_paged($query_args, true);
		if (BANG_FS_GET_DEBUG) do_action('log', 'fs: Query args (paged)', $query_args);
		if (empty($query_args['tax_query']))
			unset($query_args['tax_query']);

		if (isset($options->block) && is_array($options->block))
			foreach ($options->block as $block) unset($query_args[$block]);

		if (BANG_FS_DEBUG) do_action('log', 'fs: Query args', $query_args);
		$this->query_args = $query_args;
	}

	function options() {
		return $this->options;
	}

	function get_args() {
		return $this->get;
	}

	function get_query_args() {
		return $this->query_args;
	}

	function install_global_query() {
		if (BANG_FS_DEBUG >= 2) do_action('log', 'DEBUG fs: Install global');

		if (!isset($this->query)) {
			do_action_ref_array('bang_fs_before_query', array(&$this));
			$this->query = new WP_Query($this->query_args);
		}

		global $wp_the_query, $wp_query;
		$wp_the_query = &$this->query;
		$wp_query = &$this->query;
	}

	function get_posts($need_query = false) {
		if (BANG_FS_DEBUG >= 2) {
			do_action('log', 'fs: get_posts: need query = %s, is set query = %s, is set posts = %s, is global = %s/%s', $need_query, isset($this->query), isset($this->posts), isset($this->is_global), $this->is_global);
		}

		/*if (!isset($this->posts) && isset($this->query->posts)) {
			global $bang_fs_in_progress;
			if (BANG_FS_DEBUG) do_action('log', 'fs: get_posts: pulling from query: %s posts, %s found posts, %s results shown', count($this->query->posts), $this->query->found_posts, $this->query->post_count);
			if (BANG_FS_DEBUG >= 2) {
				global $wp_query;
				do_action('log', 'fs: get_posts: Global query: %s posts, %s found posts, %s results shown', count($wp_query->posts), $wp_query->found_posts, $wp_query->post_count);
			}
			$posts = $this->query->posts;
			if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: get_posts: %s initial results', count($posts), '!ID,post_title', $posts);

			do_action_ref_array('bang_fs_after_query', array(&$this));

			$this->posts = apply_filters('bang_fs_results', $posts, $get, $options);
			if (BANG_FS_DEBUG) do_action('log', 'fs: get_posts: %s results', count($this->posts));
			$bang_fs_in_progress = false;

			return $this->posts;
		}*/

		if (!isset($this->posts) || ($need_query && !isset($this->query))) {
			//  try using the global query, but only try once
			if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: get_posts: Try global query? %s/%s', isset($this->is_global), $this->is_global);
			if (!isset($this->is_global)) {
				global $wp_query;

				//  coerce our own query and the global query into a matching format so they can be reliably compared
				$ourq = bang_fs_unpaged(array_filter((array) $this->query_args));
				$theirq = bang_fs_unpaged(array_filter((array) $wp_query->query));
				if (isset($ourq['post_type']) && !isset($theirq['post_type'])) {
					if (isset($wp_query->query_vars['post_type']))
						$theirq['post_type'] = $wp_query->query_vars['post_type'];
					if ($theirq['post_type'] == 'any') {
						unset($theirq['post_type']);
					}
				}
				if (isset($ourq['post_type']) && is_string($ourq['post_type']))
					$ourq['post_type'] = array($ourq['post_type']);
				if (isset($theirq['post_type']) && is_string($theirq['post_type']))
					$theirq['post_type'] = array($theirq['post_type']);

				if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: get_posts: Comparing to global query', $ourq, $theirq);
				if ($ourq == $theirq) {
					$ourp = bang_fs_paged_only($this->query_args);
					$theirp = bang_fs_paged_only($wp_query->query_vars);
					if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: get_posts: Comparing pagination to global query', $ourp, $theirp);
					if ($ourp == $theirp) {
						if (BANG_FS_DEBUG) do_action('log', 'fs: get_posts: matches global query', $theirq);
						$this->query = &$wp_query;
						$this->is_global = true;

            if (BANG_FS_RELEVANSSI) {
              if (BANG_FS_DEBUG) do_action('log', 'fs: get_posts: global results found, skipping execution, using Relevanssi');

              $query = apply_filters('relevanssi_modify_wp_query', $this->query_query);
              $this->posts = relevanssi_do_query($query);
              if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: get_posts: %s initial results', count($posts), '!ID,post_title', $posts);
              $this->posts = apply_filters('bang_fs_results', $posts, $this->query_args, $this->options);

              if (BANG_FS_DEBUG) do_action('log', 'fs: get_posts: %s results', count($this->posts));
              $bang_fs_in_progress = false;
              return $this->posts;
            }

						if (!empty($wp_query->posts)) {
							if (BANG_FS_DEBUG) do_action('log', 'fs: get_posts: global results found, skipping execution');

							$posts = $wp_query->posts;
							if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: get_posts: %s initial results', count($posts), '!ID,post_title', $posts);
							$this->posts = apply_filters('bang_fs_results', $posts, $this->query_args, $this->options);

							if (BANG_FS_DEBUG) do_action('log', 'fs: get_posts: %s results', count($this->posts));
							$bang_fs_in_progress = false;
							return $this->posts;
						}
					}
				} else {
					$this->is_global = false;
				}
			}


			global $bang_fs_in_progress;
			$bang_fs_in_progress = true;

			do_action_ref_array('bang_fs_before_query', array(&$this));
			if (BANG_FS_DEBUG) do_action('log', 'fs: get_posts: executing query', $this->query_args);

			if (BANG_FS_RELEVANSSI && !empty($this->query_args['s'])) {
        if (!isset($this->query)) {
          $this->query = new WP_Query('');
          $this->query->init();
          $this->query->query = $this->query->query_vars = wp_parse_args($this->query_args);
        }
				do_action('log', 'fs: Calling relevanssi_do_query(%s)', $this->query);
				relevanssi_do_query($this->query);
				$posts = $this->query->posts;
			} else {
        if (!isset($this->query))
          $this->query = new WP_Query($this->query_args);
				$posts = $this->query->get_posts();
			}
			if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: get_posts: %s initial results', count($posts), '!ID,post_title', $posts);

			do_action_ref_array('bang_fs_after_query', array(&$this));

			$this->posts = apply_filters('bang_fs_results', $posts, $this->get, $this->options, $this->query);

			if (BANG_FS_DEBUG) do_action('log', 'fs: get_posts: %s results', count($this->posts));
			$bang_fs_in_progress = false;
		}

		return $this->posts;
	}

	function have_posts() {
		$this->get_posts();
		if (BANG_FS_DEBUG) do_action('log', 'fs: have_posts', !empty($this->posts));
		return !empty($this->posts);
	}

	function count() {
		if (empty($this->count)) {

			//  try the easy way first - only viable if nobody's filtering the results
			if (!has_filter('bang_fs_results')) {
				if (BANG_FS_DEBUG) do_action('log', 'fs: Counting');
				// if (BANG_FS_DEBUG) do_action('log', 'fs: Counting: Query', $this->query);
				// do_action('log', 'fs: Counting: found_posts (pre)', '!found_posts,post_count', $this->query);
				$this->get_posts(true);
				// do_action('log', 'fs: Counting: found_posts (post)', '!found_posts,post_count', $this->query);
				$count = $this->query->found_posts;
				if ($count > 0 || $this->query->post_count == 0) {
					$this->count = apply_filters_ref_array('bang_fs_count', array($count, &$this));
					if (BANG_FS_DEBUG) do_action('log', 'fs: count: %s results', $this->count);
					return $this->count;
				}
			}

      if (BANG_FS_DEBUG >= 2) {
        global $wp_filter;
        $filters = $wp_filter['bang_fs_results'];
        do_action('log', 'fs: Filters', $filters);
      }

			//  do it the hard way  :(
			$cqa = $this->query_args;
			$cqa['posts_per_page'] = -1;
			$cqa['lazy_load_term_meta'] = false;
			$cqa['update_post_term_cache'] = false;
			$cqa['update_post_meta_cache'] = false;
			$cqa['ignore_sticky_posts'] = true;
			unset($cqa['offset']);
			unset($cqa['paged']);
			if (BANG_FS_DEBUG) do_action('log', 'fs: Counting the hard way', $cqa);

			$cq = new WP_Query($cqa);

			if (BANG_FS_RELEVANSSI && !empty($cqa['s'])) {
				do_action('log', 'fs: Count: Calling relevanssi_do_query()');
				relevanssi_do_query($cq);
				$cposts = $cq->posts;
			} else {
				if (isset($cq->posts))
					$cposts = $cq->posts;
				else
					$cposts = $cq->get_posts();
			}
			if (BANG_FS_DEBUG) do_action('log', 'fs: Counting the hard way: initial count', count($cposts));
			$cposts = apply_filters('bang_fs_results', $cposts, $cqa, $this->options);
			$count = count($cposts);
			if (BANG_FS_DEBUG) do_action('log', 'fs: Counting the hard way: filtered count', $count);

			$this->count = apply_filters_ref_array('bang_fs_count', array($count, &$this));
		}

		if (BANG_FS_DEBUG) do_action('log', 'fs: count: %s results', $this->count);
		return $this->count;
	}

	function num_pages() {
		if (!isset($this->num_pages)) {
			// if (BANG_FS_DEBUG) do_action('log', 'fs: num_pages: counting');
			$count = $this->count();
			if (!has_filter('bang_fs_results') && isset($this->query->max_num_pages) && ($this->query->max_num_pages != 0 || $count == 0)) {
				if (BANG_FS_DEBUG) do_action('log', 'fs: num_pages: count = %s, max num pages = %s', $count, $this->query->max_num_pages);
				$this->num_pages = $this->query->max_num_pages;
			} else {
				$posts_per_page = isset($this->query_args->posts_per_page) ? $this->query_args->posts_per_page : get_option('posts_per_page');
				if ($posts_per_page != -1) {
					$this->num_pages = (int) ceil((double) $count / (double) $posts_per_page);
					if (BANG_FS_DEBUG) do_action('log', 'fs: num_pages: count = %s, posts_per_page = %s, num pages = %s', $count, $posts_per_page, $this->num_pages);
				} else {
					if (BANG_FS_DEBUG) do_action('log', 'fs: num_pages: :(');
					$this->num_pages = 1;
				}
			}
		}
		if (BANG_FS_DEBUG) do_action('log', 'fs: num_pages', $this->num_pages);
		return $this->num_pages;
	}

	function write_feedback($args = array()) {
		bang_fs_write_feedback($args);
	}

	function paginate($args = array()) {
		$original = $args;

		// build a base
    if (BANG_FS_DEBUG) do_action('log', 'fs: Pagination: Base URI', bang_fs_base_uri());
		$get = bang_fs_base_params(bang_fs_unpaged($this->get));
		$get = apply_filters('bang_fs_paginate_get', $get);
		// if (!empty($get['s'])) $get['s'] = urlencode($get['s']);
		if (BANG_FS_DEBUG) do_action('log', 'fs: Pagination: Get', $get);
		foreach ($get as $key => $value) {
			if (is_array($value))
				unset($get[$key]);
		}
		$base = add_query_arg(array_map('urlencode', $get), bang_fs_base_uri());
    $base = add_query_arg(array('paged' => false, 'page' => false), $base);
    $base = site_url($base).'%_%';
		if (BANG_FS_DEBUG) do_action('log', 'fs: Pagination: Base', $base);

		// arguments
		$d = strstr($base, '?') ? '&' : '?';
		$current = $this->query_args['paged'];
		$num_pages = $this->num_pages();
    if (BANG_FS_DEBUG) do_action('log', 'fs: Pagination: %s / %s', $current, $num_pages);

		$args = wp_parse_args($args, array(
			'base' => $base,
			'format' => $d.'page=%#%',
			'current' => $current,
			'total' => $num_pages,
			// 'add_args' => $get,
		));
		$args = wp_parse_args($args, array(
			'first_last' => true,
			'first_text' => __('« First'),
			'last_text' => __('Last »'),
			));
		$args = apply_filters('bang_fs_paginate_args', $args);
		$args = wp_parse_args(array(
			'type' => 'array',
			), $args);

		if (BANG_FS_DEBUG) do_action('log', 'fs: Pagination', $args);

		// build the array
		$pagination = paginate_links($args);
		do_action('log', 'fs: Pagination (pre-filter)', $pagination);
		$pagination = str_replace('<a ', '<a rel="nofollow" ', $pagination);
		if ($args['first_last']) {
			if ($current != 1) {
				$first_url = str_replace('%_%', '', $base);
				$first_text = $args['first_text'];
				$first = "<a rel='nofollow' class='page-numbers first' href='$first_url'>$first_text</a>";
				array_unshift($pagination, $first);
			}
			if ($current < $num_pages) {
				$last_url = str_replace('%_%', $args['format'], $base);
				$last_url = str_replace('%#%', $num_pages, $last_url);
				$last_text = $args['last_text'];
				$last = "<a rel='nofollow' class='page-numbers last' href='$last_url'>$last_text</a>";
				array_push($pagination, $last);
			}
		}
		$pagination = apply_filters('bang_fs_paginate', $pagination, $args);

		//
		do_action('log', 'fs: Pagination', $pagination);
		if (isset($original['type']) && $original['type'] == 'array')
			return $pagination;
		if (is_null($pagination) || empty($pagination))
			return '';
		return implode('', $pagination);
	}

	function filter($filter) {
		return new BangFacetedSearchFiltered($this, $filter);
	}

	function setFoo() {
		$this->foo = 1;
	}

	function foo() {
		return $this->foo;
	}
}

function bang_fs_paginate($args = array()) {
	global $faceted_search;
	if (isset($faceted_search))
		echo $faceted_search->paginate($args);
}


add_filter('get_pagenum_link', 'bang_fs_remove_pagenum');
function bang_fs_remove_pagenum ($link) {
	$link = remove_query_arg('page', $link);
	return $link;
};


function bang_fs_safe_wrapper(&$faceted_search) {
	// if (BANG_FS_DEBUG) do_action('log', 'fs: Wrapping', '@trace');
	if ($faceted_search instanceof BangFacetedSearchSafeWrapper)
		return $faceted_search;
	return new BangFacetedSearchSafeWrapper($faceted_search);
}

class BangFacetedSearchSafeWrapper {
	var $faceted_search;

	function __construct(&$faceted_search) {
		// if ($faceted_search instanceof BangFacetedSearchSafeWrapper) {
		// 	do_action('log', 'THE FUZZUCK?!?!?');
		// 	exit;
		// }
		$this->faceted_search = &$faceted_search;
	}

	function options() {
		return $this->faceted_search->options();
	}

	function get_args() {
		return $this->faceted_search->get_args();
	}

	function get_query_args() {
		return $this->faceted_search->get_query_args();
	}

	function get_posts($need_query = false) {
		return $this->faceted_search->get_posts($need_query);
	}

	function have_posts() {
		return $this->faceted_search->have_posts();
	}

	function count() {
		return $this->faceted_search->count();
	}

	function num_pages() {
		return $this->faceted_search->num_pages();
	}

	function write_feedback($args = array()) {
		$this->faceted_search->write_feedback($args);
	}

	function paginate($args = array()) {
		return $this->faceted_search->paginate($args);
	}

	function filter($filter) {
		return new BangFacetedSearchFiltered($this, $filter);
	}

	function setFoo() {
		$this->faceted_search->setFoo();
	}

	function foo() {
		return $this->faceted_search->foo();
	}
}

class BangFacetedSearchFiltered extends BangFacetedSearchSafeWrapper {
	var $posts;
	var $count;
	var $current_page;
	var $num_pages;
	var $offset;

	function __construct(&$faceted_search, $filter) {
		parent::__construct($faceted_search);
		$args = $faceted_search->get_args();
		$posts_per_page = (isset($args->posts_per_page) && $args->posts_per_page != -1) ? $args->posts_per_page : get_option('posts_per_page');

		// todo rewrite the base faceted search to make sure it gets ALL posts.

		$posts = $faceted_search->get_posts();
		if (BANG_FS_DEBUG) do_action('log', 'fs: Filtered search: filtering %s posts', count($posts));
		$posts = array_filter($posts, $filter);
		if (BANG_FS_DEBUG) do_action('log', 'fs: Filtered search: allowed %s posts', count($posts));
		$this->count = count($posts);

		// paginate
		$this->current_page = (isset($args['paged']) && is_numeric($args['paged']) ) ? intval($args['paged']) : 1;
		$this->num_pages = (int) ceil((double) $this->count / (double) $posts_per_page);
		if ($this->current_page > $this->num_pages) $this->current_page = $this->num_pages;

		$this->offset = ($this->current_page - 1) * $posts_per_page;
		$this->posts = array_slice($posts, $this->offset, $posts_per_page);
		if (BANG_FS_DEBUG) do_action('log', 'fs: Filtered search: page %s of %s; showing %s of %s posts', $this->current_page, $this->num_pages, count($this->posts), $this->count);
	}

	function count() {
		return $this->count;
	}

	function get_posts($need_query = false) {
		return $this->posts;
	}

	function num_pages() {
		return $this->num_pages;
	}

	function paginate($args = array()) {
		$args['total'] = $this->num_pages;
		return $this->faceted_search->paginate($args);
	}
}

/*
Foo used to test the whether the safe wrapper is working.
*/
function bang_fs_foo() {
	global $faceted_search;
	return $faceted_search->foo();
}

// function bang_fs_pre_get_posts(&$wp_query) {
// 	$wp_query->is_home = false;
// 	$wp_query->is_search = true;
// }

function bang_fs_in_progress() {
	global $bang_fs_in_progress;
	return $bang_fs_in_progress;
}
