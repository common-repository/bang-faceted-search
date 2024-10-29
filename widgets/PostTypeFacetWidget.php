<?php

class PostTypeFacetWidget extends WP_Widget {
  function __construct() {
    parent::__construct('bang-post-type-widget', 'Post type', array(
      'classname' => 'bang-post-type-widget',
      'description' => 'Post type options for the faceted search sidebar.',
    ));
  }

  function PostTypeFacetWidget() {
    return self::__construct();
  }

  function form ($instance) {
    $title = esc_attr($instance['title']);
    $titleID = $this->get_field_id('title');
    $titleName = $this->get_field_name('title');

    $orders = bang_fs_taxonomy_orders($taxonomy);
    $orderby = isset($instance['orderby']) ? esc_attr($instance['orderby']) : '';
    if (!isset($orders[$orderby])) $orderby = 'menu_order';
    $orderbyID = $this->get_field_id('orderby');
    $orderbyName = $this->get_field_name('orderby');

    // display it
    ?><span class='bang-indicator search-indicator'></span><?php

    // echo "<label for='$titleID'>Title</label>";
    echo "<input class='search-title' id='$titleID' name='$titleName' type='text' placeholder='Title' value='$title' />";

    //  sort
    echo "<p><label for='$orderbyID'>Sorted by</label> &nbsp; <select id='$orderbyID' name='$orderbyName'>";
    foreach ($orders as $code => $name) {
      echo "<option value='$code'";
      if ($code == $orderby) echo " selected";
      echo ">$name</option>";
    }
    echo "</select></p>";
  }

  function update($new_instance, $old_instance) {
    $instance = $old_instance;
    $instance['title'] = strip_tags($new_instance['title']);
    $instance['orderby'] = strip_tags($new_instance['orderby']);
    return $instance;
  }

  function widget($args, $instance) {
    if (!bang_fs_facets_visible()) return;
    bang_fs_post_type_facet_widget($args, $instance);
  }
}


function bang_fs_post_type_facet_widget($args, $instance) {
  $args = bang_fs_widget_args($args);
  extract($args, EXTR_SKIP);

  $instance = bang_fs_widget_instance($instance, array(
    'title' => 'Post type',
    'fieldname' => 'post_type',
    'orderby' => 'az',
    'name_field' => 'name',
  ));
  if (BANG_FS_DEBUG) do_action('log', 'fs: Post type facet widget', $instance);

  $settings = bang_fs_settings();

  $title = $instance['title'];
  $fieldname = $instance['fieldname'];
  $name_field = $instance['name_field'];
  $orderby = $instance['orderby'];
  $show_count = (boolean) $settings->show_count;
  $sort_count = $orderby == 'count';
  $hide_empty = (boolean) $settings->hide_empty;

  $post_type = sanitize_title($_REQUEST[$fieldname]);
  $post_type = get_post_type_object($post_type);
  if (empty($post_type)) $post_type = '';

  //  get post types
  $post_types1 = get_post_types(array(
    'public' => true,
    '_builtin' => true,
  ), 'objects');
  $post_types2 = get_post_types(array(
    'public' => true,
    '_builtin' => false,
  ), 'objects');
  $post_types = array_merge($post_types1, $post_types2);
  global $bang_fs_current_location;
  if (isset($bang_fs_current_location->post_types)) {
    $post_types = array_intersect_key($post_types, array_fill_keys($bang_fs_current_location->post_types, 1));
    do_action('log', 'fs: Post type facet widget: Intersecting', $post_types);
  }

  // filter post types by 'exclusion'
  $exclude = $instance['exclude'];
  if (is_string($exclude)) $exclude = array($exclude);
  if (!is_array($exclude)) $exclude = array();
  foreach ($exclude as $x) unset($post_types[$x]);

  // don't bother listing if it would only ever show one item
  if (count($post_types) <= 1)
    return;

  // count posts
  if ($show_count || $sort_count || $hide_empty) {
    $get = bang_fs_get();
    $counts = bang_fs_post_type_counts($get);
    if (BANG_FS_DEBUG >= 2) do_action('log', "fs post_type: Counts", $counts);

    foreach ($post_types as $type) {
      $type->count = $counts[$type->name]['cnt'];
    }

    if ($hide_empty)
      $post_types = array_filter($post_types, 'bang_fs_filter_count');
    if ($sort_count) {
      if (BANG_FS_DEBUG) do_action('log', 'fs post_type: sort by count');
      usort($post_types, 'bang_fs_cmp_count');
    }
  }

  //do_action('log', 'Post types', $post_types);
  if (empty($post_types)) return;

  echo $before_widget.$before_title.esc_html($title).$after_title;
  $with = empty($post_type) ? '' : ' with-selection';
  echo "<div class='fs-out'><ul class='fs$with'>";
  foreach ($post_types as $type) {
    $link = esc_attr(bang_fs_set_facet_url($fieldname, $type->name));
    $selected = ($post_type->name == $type->name) ? "class='selected'" : '';
    $name = $type->labels->$name_field;
    if ($show_count) {
      $name = bang_fs_facet_name_with_count($name, $type->count);
    }

    echo "<li><a rel='nofollow' href='$link' $selected>{$name}</a></li>";
  }
  echo "</ul></div>".$after_widget;

  if (!empty($post_type)) return $post_type;
  return $post_types;
}
