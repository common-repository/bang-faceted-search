<?php

class YearFacetWidgetDisabled extends WP_Widget {
  function __construct() {
    parent::__construct('year-facet-widget', 'Year', array(
      'classname' => 'year-facet-widget',
      'description' => 'Year options for the faceted search sidebar.',
    ));
  }

  function YearFacetWidget() {
    return self::__construct();
  }

  function form ($instance) {
    $title = esc_attr($instance['title']);
    $titleID = $this->get_field_id('title');
    $titleName = $this->get_field_name('title');
    $since = (integer) $instance['since'];
    $sinceID = $this->get_field_id('since');
    $sinceName = $this->get_field_name('since');

    $show_count = (boolean) $instance['show_count'];
    $show_countID = $this->get_field_id('show-count');
    $show_countName = $this->get_field_name('show_count');
    $sort_count = (boolean) $instance['sort_count'];
    $sort_countID = $this->get_field_id('sort_count');
    $sort_countName = $this->get_field_name('sort_count');

    ?><span class='bang-indicator search-indicator'></span><?php

    echo "<label for='$titleID'>Title</label>";
    echo "<input class='widefat' id='$titleID' name='$titleName' type='text' placeholder='Title' value='$title' />";
    echo "<label for='$sinceID'>Since year</label>";
    echo "<input class='widefat' id='$sinceID' name='$sinceName' type='text' placeholder='Since' value='$since' />";

    echo "<div><label for='$show_countID'><input type='checkbox' id='$show_countID' name='$show_countName'";
    if ($show_count) echo " checked";
    echo "> Show post counts</label></div>";
  }

  function update($new_instance, $old_instance) {
    $instance = $old_instance;
    $instance['title'] = strip_tags($new_instance['title']);
    $instance['since'] = (integer) $new_instance['since'];
    $instance['show_count'] = (boolean) $new_instance['show_count'];
    $instance['hide_empty'] = (boolean) $new_instance['hide_empty'];
    return $instance;
  }

  function widget($args, $instance) {
    if (!bang_fs_facets_visible()) return;
    bang_fs_year_facet_widget($args, $instance);
  }
}

function bang_fs_year_facet_widget($args, $instance) {
  $args = bang_fs_widget_args($args);
  extract($args, EXTR_SKIP);

  $instance = bang_fs_widget_instance($instance, array(
    'title' => '',
    'since' => '',
    'fieldname' => 'year',
  ));
  if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Year facet widget', $instance);

  $get = bang_fs_get();

  $fieldname = $instance['fieldname'];
  if (empty($fieldname)) $fieldname = 'year';

  $settings = bang_fs_settings();

  $title = $instance['title'];
  $show_count = (boolean) $settings->show_count;
  $sort_count = (boolean) $instance['sort_count'];
  $hide_empty = (boolean) $settings->hide_empty;
  $since = (integer) $instance['since'];
  $now = (integer) date("Y");
  if (empty($instance['since']))
    $since = $now - 10;
  if ($since < $now - 20)
    $since = $now - 20;

  $reset = $instance['reset'];
  $reset_position = $instance['reset_position'];
  $selected_year = (int) $get[$fieldname];

  $years = array();
  if ($show_count || $sort_count || $hide_empty) {
    $counts = bang_fs_year_counts($get, $since, $now);
    if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Year counts', $counts);

    for ($year = $now; $year >= $since; $year--) {
      $label = apply_filters('bang_fs_year_label', $year, $year, $count);
      $count = (int) isset($counts[$year]) ? $counts[$year]['cnt'] : 0;
      $years[] = (object) array(
        'label' => $label,
        'year' => $year,
        'count' => $count,
        'selected' => $year == $selected_year
      );
    }

    if ($hide_empty)
      $years = array_filter($years, 'bang_fs_filter_count');
    if ($sort_count)
      usort($years, 'bang_fs_cmp_count');
  } else {
    for ($year = $now; $year >= $since; $year--) {
      $label = apply_filters('bang_fs_year_label', $year, $year, $count);
      $years[] = (object) array(
        'label' => $label,
        'year' => $year,
        'selected' => $year == $selected_year
      );
    }
  }
  if (BANG_FS_DEBUG >= 1) do_action('log', 'fs: Year facet', $years);

  //  sort the terms
  if ($hide_empty)
    $years = array_filter($years, 'bang_fs_year_filter_count');

  if (!empty($years)) {
    $with_selection = empty($selected_year) ? '' : ' with-selection';
    echo $before_widget.$before_title.esc_html($title).$after_title."<div class='fs-out'><ul class='fs$with_selection' id='fs-year'>";
    if ($reset && $reset_position == 'first' && !empty($selected_year)) {
      $link = esc_attr(bang_fs_remove_facet_url($fieldname));
      echo "<li><a rel='nofollow' href='$link'>$reset</a></li> ";
    }
    foreach ($years as $year) {
      $name = $year->year;
      if ($show_count)
        $name = bang_fs_facet_name_with_count($year->year, $year->count);
      $link = esc_attr(bang_fs_set_facet_url($fieldname, $year->year));
      $selected = ($year->selected) ? "class='selected'" : '';
      echo "<li><a rel='nofollow' href='$link' $selected>$name</a></li> ";
    }
    if ($reset && $reset_position == 'last' && !empty($selected_year)) {
      $link = esc_attr(bang_fs_remove_facet_url($fieldname));
      echo "<li><a rel='nofollow' href='$link'>$reset</a></li> ";
    }
    echo "</ul></div>".$after_widget;
  }
}

function bang_fs_year_filter_count ($option) {
  return $option->count > 0 || $option->selected;
}
