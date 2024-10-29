<?php

class SearchFacetWidget extends WP_Widget {
  function __construct () {
    parent::__construct('search-facet-widget', 'Search field', array(
      'classname' => 'search-facet-widget',
      'description' => 'Text search box for the faceted search sidebar.',
    ));
  }

  function SearchFacetWidget() {
    return self::__construct();
  }

  function form ($instance) {
    $title = esc_attr($instance['title']);
    $titleID = $this->get_field_id('title');
    $titleName = $this->get_field_name('title');
    $placeholder = esc_attr($instance['placeholder']);
    $placeholderID = $this->get_field_id('placeholder');
    $placeholderName = $this->get_field_name('placeholder');
    $buttonLabel = esc_attr($instance['button-label']);
    $buttonLabelID = $this->get_field_id('button-label');
    $buttonLabelName = $this->get_field_name('button-label');

    ?><span class='bang-indicator search-indicator'></span><?php
    // echo "<label for='$titleID'>Title</label>";
    echo "<input class='search-title' id='$titleID' name='$titleName' type='text' placeholder='Title' value='$title' />";
    // echo "<label for='$placeholderID'>Placeholder</label>";
    echo "<input class='search-placeholder' id='$placeholderID' name='$placeholderName' type='text' placeholder='Placeholder' value='$placeholder' />";
    echo "<input class='search-button' id='$buttonLabelID' name='$buttonLabelName' type='text' placeholder='Button' value='$buttonLabel' />";
    echo "<div class='clear empty'></div>";
  }

  function update($new_instance, $old_instance) {
    $instance = $old_instance;
    $instance['title'] = strip_tags($new_instance['title']);
    $instance['placeholder'] = strip_tags($new_instance['placeholder']);
    $instance['button-label'] = strip_tags($new_instance['button-label']);
    return $instance;
  }

  function widget($args, $instance) {
    if (!bang_fs_facets_visible()) return;
    bang_fs_search_widget($args, $instance);
  }
}

function bang_fs_search_widget($args, $instance) {
  $args = bang_fs_widget_args($args);
  extract($args, EXTR_SKIP);
  
  $instance = bang_fs_widget_instance($instance, array(
    'title' => '',
    'placeholder' => 'Search',
    'basepath' => 'auto',
    'button-label' => '',
  ));
  if (BANG_FS_DEBUG) do_action('log', 'fs: Search facet widget', $instance);

  $title = $instance['title'];
  $placeholder = esc_attr($instance['placeholder']);
  $button_label = $instance['button-label'];
  if ($instance['basepath'] == 'auto')
    $basepath = esc_attr($_SERVER['REQUEST_URI']);
  else
    $basepath = esc_attr($instance['basepath']);

  $get = bang_fs_get();
  $s = isset($get['s']) ? esc_attr($get['s']) : '';

  unset($get['post_type']);
  unset($get['s']);
  unset($get['offset']);

  echo $before_widget;
  if (!empty($title)) echo $before_title.$title.$after_title;
  echo "<form name='fs' method='get' action='$basepath' class='fs'>";

  foreach ($get as $key => $value) {
    if (is_array($value))
      $value = implode(',', $value);
    $key = esc_attr($key);
    $value = esc_attr($value);
    echo "<input type='hidden' name='$key' value='$value'/>";
  }
  echo "<input type='search' placeholder='$placeholder' name='s' class='s' value='$s'>";
  if (!empty($button_label)) {
    $button_label = esc_attr($button_label);
    echo "<input type='submit' value='$button_label'>";
  }
  echo "</form>";
  echo $after_widget;
}
