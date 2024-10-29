<?php

function bang_fs_settings_links($links) {
  if (BANG_FS_DEBUG >= 2) do_action('log', 'Settings link', $links);
  array_unshift($links, '<a href="options-general.php?page=bang_fs.php">Settings</a>');
  return $links;
}

add_action('admin_menu', 'bang_fs_register_settings');
function bang_fs_register_settings () {
  $settings_page = add_options_page('Faceted Search', 'Faceted Search', 'manage_options', 'bang_fs', 'bang_fs_show_settings');
  add_action('load-'.$settings_page, 'bang_fs_add_help_tabs');
}

function bang_fs_settings_defaults() {
  return apply_filters('bang_fs_defaults', (object) array(
    'locations' => array(
      (object) array(
        'uri' => '',
        'post_types' => array('post', 'page'),
        'template' => 'search.php',
      )
    ),
    'show_count' => false,
    'show_remove' => false,
    'hide_empty' => false,
    'css_empty' => true,
    'disable_empty' => false,
    'empty_class' => '',
    'show_non_search' => false,
    'style' => 'list',
    'auto_style' => false,
    'drop_down_style' => false,
    'ignore' => 'XDEBUG_PROFILE',

    'sticky' => false,
    'unsearch' => false,

    'block_paged' => array('p', 'page', 'paged', 'page_id', 'pagename', 'preview', 'preview_id', 'preview_nonce', 'offset', 'posts_per_page', 'numberposts', 'nopaging'),
    'network' => array(),
  ));
}

function bang_fs_settings_override() {
  return (object) array(
    'escape_params' => array('preview', 'preview_nonce', 'preview_id', 'attachment_id', 'p'),
    );
}

function bang_fs_settings() {
  global $bang_fs_settings;
  if (!empty($bang_fs_settings)) return $bang_fs_settings;

  $settings = get_option('bang-faceted-search');
  if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Settings: Raw', $settings);
  $settings = bang_fs_settings_tidy($settings);
  if (is_multisite())
    $settings->network = bang_fs_network_settings();
  $bang_fs_settings = $settings;
  return $settings;
}

function bang_fs_save_settings($settings) {
  do_action('log', 'fs: Save settings: Raw', $settings);
  // $base = bang_fs_settings();
  // $settings = (object) wp_parse_args((array) $settings, (array) $base);
  $settings = bang_fs_settings_tidy($settings);
  unset($settings->network);

  $settings->auto_style = false;
  $settings->drop_down_style = false;

  do_action('log', 'fs: Save settings: Final', $settings);
  update_option('bang-faceted-search', $settings);

  global $bang_fs_settings;
  $bang_fs_settings = false;
}

function bang_fs_forget_settings() {
  global $bang_fs_settings;
  $bang_fs_settings = null;
}

function bang_fs_settings_tidy($settings, $existing = array()) {
  bang_fs_post_init();
  $defaults = bang_fs_settings_defaults();
  $settings = (object) wp_parse_args((array) $settings, (array) $defaults);

  // search locations

  // if (is_callable('get_blog_details')) {
  //   global $blog_id;
  //   $blog_details = get_blog_details($blog_id);
  //   if (BANG_FS_DEBUG >= 2) do_action('log', 'Post types on site %s %s', $blog_id, trim($blog_details->path, '/'), get_post_types());
  //   if (BANG_FS_DEBUG >= 2) do_action('log', 'Post types at', '@trace');
  // }

  $valid_post_types = array_values(get_post_types('', 'names'));
  $valid_post_types = apply_filters('bang_fs_searchable_post_types', $valid_post_types);
  if (BANG_FS_DEBUG >= 2) do_action('log', 'Valid post types', $valid_post_types);
  $too_early = false;
  if (is_callable('get_page_templates')) {
    $valid_templates = get_page_templates();
    $default_template = 'search.php';
  } else
    $too_early = true;
  $is_multisite = is_multisite();

  $locations = array();
  foreach ($settings->locations as $key => $loc) {
    $loc = (object) $loc;
    if (isset($loc->delete) && $loc->delete == "on")
      continue;
    unset($loc->delete);

    //  Search address
    if (!is_string($loc->uri) || empty($loc->uri))
      $loc->uri = '';
    // $loc->uri = sanitize_file_name($loc->uri);
    $uri = explode('/', $loc->uri);
    $uri = array_map('sanitize_file_name', $uri);
    $uri = array_filter($uri);
    $uri = implode('/', $uri);
    $loc->uri = trim($uri, '/');

    // avoid overwriting home search with blank new search
    if ($key == 'new' && $loc->uri == '' && isset($locations['']))
      continue;

    if (!$too_early) {
      if (!in_array($loc->template, $valid_templates))
        $loc->template = $default_template;
    }

    //  Search post types
    if (!is_array($loc->post_types))
      $loc->post_types = array();

    // if the array is in the form:   'page' => true
    // $ki = array_intersect(array_keys($loc->post_types), $valid_post_types);
    // if (!empty($ki)) {
    //   $loc->post_types = array_keys(array_filter($loc->post_types));
    // }
    if (BANG_FS_DEBUG) do_action('log', 'fs: tidy: Adjusting post types', $loc->post_types, $valid_post_types);
    $loc->post_types = array_intersect((array) $loc->post_types, $valid_post_types);
    if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Tidy: Adjusted post types', $loc->post_types);

    // Multisite search
    $loc->multisite = isset($loc->multisite) && (bool) $loc->multisite && $is_multisite;

    $locations[$loc->uri] = $loc;
  }
  if (BANG_FS_DEBUG) do_action('log', 'fs: tidy: Mapped locations', $locations);
  $settings->locations = array_values($locations);
  if (BANG_FS_DEBUG) do_action('log', 'fs: tidy: Adjusted locations', $settings->locations);

  //  view options
  $settings->show_count = isset($settings->show_count) && (bool) $settings->show_count;
  $settings->sort_count = isset($settings->sort_count) && (bool) $settings->sort_count;
  $settings->hide_empty = isset($settings->hide_empty) && (bool) $settings->hide_empty;

  //  multisite
  if (!isset($settings->network)) $settings->network = array();
  $settings->network = (object) wp_parse_args((array) $settings->network, (array) $defaults->network);
  $settings->network->multisite = isset($settings->network->multisite) && $settings->network->multisite && is_multisite();

  //  override certain parameters
  $override = bang_fs_settings_override();
  foreach ((array) $override as $key => $value) {
    if (isset($settings->key) && is_array($value) && is_array($settings->$key)) {
      $settings->$key = array_values(array_unique(array_merge($value, $settings->$key)));
    } else {
      $settings->$key = $value;
    }
  }
  return $settings;
}

function bang_fs_location($uri = null) {
  $memoisable = false;
  if (is_null($uri)) {
    $memoisable = true;
    global $bang_fs_current_location;
    if (isset($bang_fs_current_location))
      return $bang_fs_current_location;

    // URIs not inside the current site get discarded
    $uri = preg_replace('!\?.*$!', '', $_SERVER['REQUEST_URI']);
  }
  if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: init: raw uri', $uri);

  $location = bang_fs_get_search_location($uri);
  $location = apply_filters('bang_fs_current_location', $location, $uri);

  if ($memoisable) {
    global $bang_fs_current_location;
    $bang_fs_current_location = $location;
  }
  return $location;
}

function bang_fs_get_search_location($uri) {
  //  clean up the URI
  $uri = bang_fs_simplify_url($uri);
  // $uri = trim($uri, '/');
  // $uri = strtolower($uri);

  $base = site_url('/');
  if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: init: raw base', $base);
  $base = bang_fs_simplify_url($base);
  // $base = preg_replace('!^https?://[^/]*/!', '', $base);
  // $base = trim($base, '/');
  if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: init: uri = "%s" base = "%s"', $uri, $base);

  if (!empty($base)) {
    if (substr($uri, 0, strlen($base)) != $base) {
      if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: init: URL Mismatch');
      define('BANG_FACETED_SEARCH', false);
      return null;
    }
    $uri = substr($uri, strlen($base));
    $uri = trim($uri, '/');
  }

  $settings = bang_fs_settings();
  foreach ($settings->escape_params as $param) {
    if (!empty($_GET[$param])) {
      if (BANG_FS_DEBUG) do_action('log', 'fs: init: Stepping aside for preview');
      return null;
    }
  }

  if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: init: finding search location at', $uri, $settings->locations);
  foreach ($settings->locations as $location) {
    $location = apply_filters('bang_fs_location', $location, $uri);
    if ($location->uri == $uri) {
      if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: init: Search location', $location);
      return $location;
    }
  }
  return null;
}

function bang_fs_set_location($uri) {
  global $bang_fs_current_location;
  $bang_fs_current_location = bang_fs_location($uri);
  if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Location set', $bang_fs_current_location);
}

function bang_fs_simplify_url($url) {
  $url = preg_replace('!\?.*$!', '', $url);
  $url = preg_replace('!^https?://[^/]*/!', '', $url);
  $url = trim($url, '/');
  $url = strtolower($url);
  return $url;
}

function bang_fs_empty_class() {
  $settings = bang_fs_settings();
  $cls = $settings->empty_class;
  if (empty($cls))
    $cls = "fs-empty";
  return $cls;
}
