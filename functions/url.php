<?php

/*
  Functions for handling URLs in faceted search
*/

function bang_fs_local_uri ($uri = false) {
  $site_url = home_url('/');
  $site_uri_base = preg_replace('!^https?://[^/]+/!i', '', $site_url);
  $site_uri_base = trim($site_uri_base, '/');

  $loc = ($uri === false) ? $_SERVER['REQUEST_URI'] : $uri;
  $loc = preg_replace("!^(https?://[^/]+)?/$site_uri_base/?!i", "", $loc);
  $loc = preg_replace("!\?.*$!i", "", $loc);
  $loc = trim($loc, '/');
  return $loc;
}

function bang_fs_root_local_uri($uri = false) {
  $site_url = home_url('/');
  $site_uri_base = preg_replace('!^https?://[^/]+/!', '', $site_url);
  $site_uri_base = trim($site_uri_base, '/');

  $loc = ($uri === false) ? $_SERVER['REQUEST_URI'] : $uri;
  $loc = preg_replace("!^(https?://[^/]+)?/$site_uri_base/?!i", "", $loc);
  $loc = preg_replace("!\?.*$!i", "", $loc);
  $loc = trim($loc, '/');

  $prefix = empty($site_uri_base) ? '/' : '/'.$site_uri_base.'/';
  return $prefix.$loc;
}

function bang_fs_absolute_url($uri = false) {
  $site_url = home_url('/');
  $site_uri_base = preg_replace('!^https?://[^/]+/!', '', $site_url);
  $site_uri_base = trim($site_uri_base, '/');

  $loc = ($uri === false) ? $_SERVER['REQUEST_URI'] : $uri;
  $loc = preg_replace("!^(https?://[^/]+)?/$site_uri_base/?!i", "", $loc);
  $loc = preg_replace("!\?.*$!i", "", $loc);
  $loc = trim($loc, '/');

  return home_url('/'.$loc);
}

// find the base URI (not including domain) to which searches URLs are relative
function bang_fs_base_uri() {
  $prefix = '/';

  // if there's a search location active, use that
  $location = bang_fs_location();
  if (isset($location->uri) && $location->uri !== false) {
    return bang_fs_local_uri($location->uri);
  }

  // if there's a faceted search in progress and it has a URI set, use that
  $fs = bang_fs_instance();
  if (!empty($fs)) {
    $options = $fs->options();
    if (isset($options->uri) && $options->uri !== false) {
      return bang_fs_local_uri($options->uri);
    }
  }

  // use the current request URI
  return bang_fs_local_uri();
}

function bang_fs_base_url() {
  return home_url(bang_fs_base_uri());
}

function bang_fs_base_params($args = null) {
  // if there's a faceted search in progress, use its params
  $fs = bang_fs_instance();
  if (!empty($fs)) {
    $get = $fs->get_args();
  } else {
    $get = bang_fs_get_unpaged();
  }

  if (is_array($args))
    $get = wp_parse_args($args, $get);
  $get = apply_filters('bang_fs_base_params', $get);
  return $get;
}


function bang_fs_set_facet_url($key, $value, $basepath = false, $remove = false) {
  $get = bang_fs_base_params();

  if (!$basepath) $basepath = bang_fs_base_url();
  if ($remove) foreach ($remove as $r) unset($get[$r]);
  $get[$key] = $value;
  if (!isset($get['s'])) $get['s'] = '';
  return add_query_arg(array_map('urlencode', $get), $basepath);
}

function bang_fs_remove_facet_url($key, $basepath = false) {
  $get = bang_fs_base_params();
  // do_action('log', 'fs URLs: Removing %s from', $key, $get);

  unset($get[$key]);
  if (empty($get['s']))
    $get['s'] = '';
  if (!$basepath) $basepath = bang_fs_base_url();
  return add_query_arg(array_map('urlencode', $get), $basepath);
}

function bang_fs_set_multi_facet_url($key, $value, $basepath = false, $remove = false) {
  $get = bang_fs_base_params();

  if (!$basepath) $basepath = bang_fs_base_url();
  if ($remove) foreach ($remove as $r) unset($get[$r]);

  $old_value = isset($get[$key]) ? $get[$key] : '';
  $vs = explode(',', $old_value);
  $vs = array_fill_keys(array_filter(array_map('trim', $vs)), true);
  if ($remove) foreach ($remove as $r) unset($vs[$r]);

  if (!empty($value))
	  $vs[$value] = true;
	$vs = array_keys(array_filter($vs));
  sort($vs);
	$new_value = implode(',', $vs);
  $get[$key] = $new_value;

  if (!isset($get['s'])) $get['s'] = '';
  return add_query_arg(array_map('urlencode', $get), $basepath);
}

function bang_fs_remove_multi_facet_url($key, $value, $basepath = false) {
  $get = bang_fs_base_params();
  // do_action('log', 'fs URLs: Removing %s value %s from', $key, $value, $get);

  $old_value = isset($get[$key]) ? $get[$key] : '';
  $vs = explode(',', $old_value);
  $vs = array_filter(array_map('trim', $vs));
  $vs = array_fill_keys($vs, true);

  if (!empty($value))
	  $vs[$value] = false;
  // do_action('log', 'fs URLs: Adjusted parameters', $vs);
	$vs = array_keys(array_filter($vs));
	$new_value = implode(',', $vs);

  if (empty($new_value))
  	unset($get[$key]);
  else
	  $get[$key] = $new_value;

  if (empty($get['s']))
    $get['s'] = '';
  if (!$basepath) $basepath = bang_fs_base_url();
  // do_action('log', 'fs URLs: Reconstructing URL params', $basepath, $get);
  return add_query_arg(array_map('urlencode', $get), $basepath);
}

/*
  Link to the prev/next page of the search
*/

function bang_fs_first_page_url ($args = null, $basepath = false, $pagesize = 0) {
  $get = bang_fs_get($args);
  $args = wp_parse_args($args, $get);

  if ($pagesize == 0 || !$pagesize) {
    if (isset($args['posts_per_page']))
      $pagesize = (integer) $args['posts_per_page'];
    else if (isset($faceted_search->args))
      $pagesize = (integer) $faceted_search->args['posts_per_page'];
    else $pagesize = get_option('posts_per_page');
  }

  $offset = (integer) $args['offset'];
  if ($offset <= 0)
    return false;

  unset($args['offset']);
  if (!$basepath) $basepath = bang_fs_base_url();
  return add_query_arg(array_map('urlencode', $args), $basepath);
}

function bang_fs_prev_page_url ($args = null, $basepath = false, $pagesize = 0) {
  $get = bang_fs_get($args);
  $args = wp_parse_args($args, $get);

  if ($pagesize == 0 || !$pagesize) {
    if (isset($args['posts_per_page']))
      $pagesize = (integer) $args['posts_per_page'];
    else if (isset($faceted_search->args))
      $pagesize = (integer) $faceted_search->args['posts_per_page'];
    else $pagesize = get_option('posts_per_page');
  }

  $offset = (integer) $args['offset'];
  if ($offset <= 0)
    return false;

  $args['offset'] = $offset - $pagesize;
  if ($args['offset'] <= 0) unset($args['offset']);
  if (!$basepath) $basepath = bang_fs_base_url();
  return add_query_arg(array_map('urlencode', $args), $basepath);
}

function bang_fs_next_page_url ($args = null, $basepath = false, $pagesize = 0) {
  $get = bang_fs_get($args);
  $args = wp_parse_args($args, $get);

  if ($pagesize == 0 || !$pagesize) {
    if (isset($args['posts_per_page']))
      $pagesize = (integer) $args['posts_per_page'];
    else if (isset($faceted_search->args))
      $pagesize = (integer) $faceted_search->args['posts_per_page'];
    else $pagesize = get_option('posts_per_page');
  }

  $args['offset'] = ((integer) $args['offset']) + $pagesize;
  $count = bang_fs_count();
  if ($count !== false && $args['offset'] >= $count)
    return false;

  if (isset($args['rem'])) $args['rem'] = ((integer) $args['rem']) - $pagesize;
  if (!$basepath) $basepath = bang_fs_base_url();
  return add_query_arg(array_map('urlencode', $args), $basepath);
}

function bang_fs_last_page_url ($args = null, $basepath = false, $pagesize = 0) {
  $get = bang_fs_get($args);
  $args = wp_parse_args($args, $get);

  if ($pagesize == 0 || !$pagesize) {
    if (isset($args['posts_per_page']))
      $pagesize = (integer) $args['posts_per_page'];
    else if (isset($faceted_search->args))
      $pagesize = (integer) $faceted_search->args['posts_per_page'];
    else $pagesize = get_option('posts_per_page');
  }

  $count = bang_fs_count();
  if ($count == false) return false;
  while ($offset < $count) {
    $offset += $pagesize;
  }
  $offset -= $pagesize;

  if ($offset < 0) return false;
  if ($args['offset'] >= $offset) return false;
  $args['offset'] = $offset;

  if (isset($args['rem'])) $args['rem'] = ((integer) $args['rem']) - $pagesize;
  if (!$basepath) $basepath = bang_fs_base_url();
  return add_query_arg(array_map('urlencode', $args), $basepath);
}
