<?php

class DateFacetWidget extends WP_Widget {
  function __construct() {
    parent::__construct('date-facet-widget', 'Date', array(
      'classname' => 'date-facet-widget',
      'description' => 'Search by day, month or year',
    ));
  }

  function DateFacetWidget() {
    return self::__construct();
  }

  function form ($instance) {
    $title = esc_attr($instance['title']);
    $titleID = $this->get_field_id('title');
    $titleName = $this->get_field_name('title');

    $levels = array("year" => "Years", "month" => "Months", "week" => "Weeks", "day" => "Days");
    $defaultFormats = array("year" => "Y", "month" => "F Y", "week" => "'Week of 'j M Y", "day" => "D j M Y");
    $level = esc_attr($instance['level']);
    if (!isset($levels[$level])) $level = "day";
    $levelID = $this->get_field_id('level');
    $levelName = $this->get_field_name('level');

    $since = esc_attr($instance['since']);
    $sinceID = $this->get_field_id('since');
    $sinceName = $this->get_field_name('since');

    $max = (integer) $instance['max'];
    if ($max == 0) $max = "";
    $maxID = $this->get_field_id('max');
    $maxName = $this->get_field_name('max');

    $format = esc_attr($instance['format']);
    $formatID = $this->get_field_id('format');
    $formatName = $this->get_field_name('format');

    $group = (boolean) $instance['group'];
    $groupID = $this->get_field_id('group');
    $groupName = $this->get_field_name('group');

    $groups = array("year" => "Years", "month" => "Months", "week" => "Weeks");
    switch ($level) {
      case 'year': $groups = array(); break;
      case 'month': $groups = array("year" => "Years"); break;
      case 'week': $groups = array("year" => "Years", "month" => "Months"); break;
      case 'day': $groups = array("year" => "Years", "month" => "Months", "week" => "Weeks"); break;
    }
    $groupby = esc_attr($instance['groupby']);
    if (!isset($groups[$groupby])) $groupby = "month";
    $groupbyID = $this->get_field_id('groupby');
    $groupbyName = $this->get_field_name('groupby');

    $grouptags = array("h1", "h2", "h3", "h4", "h5", "h6");
    $grouptag = esc_attr($instance['grouptag']);
    if (!in_array($grouptag, $grouptags)) $grouptag = "h4";
    $grouptagID = $this->get_field_id('grouptag');
    $grouptagName = $this->get_field_name('grouptag');

    // subwidget options
    $subwidgetName = $this->get_field_name('subwidget');
    $subwidgetID = $this->get_field_id('subwidget');

    $showAll = (boolean) $instance['showall'];
    // $showAllName = $this->get_field_name('showall');
    // $showAllYesID = $this->get_field_id('showAllYes');
    // $showAllNoID = $this->get_field_id('showAllNo');

    $require = (boolean) $instance['require'];
    // $requireName = $this->get_field_name('require');
    // $requireID = $this->get_field_id('require');

    $requireField = (boolean) $instance['requireField'];
    // $requireFieldName = $this->get_field_name('requireField');
    // $requireFieldID = $this->get_field_id('requireField');


    // form
    ?><span class='bang-indicator search-indicator'></span><?php

    echo "<input class='search-title' id='$titleID' name='$titleName' type='text' placeholder='Title' value='$title' />";

    echo "<div><label for='$levelID'>Show</label> &nbsp; <select name='$levelName' id='$levelID'>";
    foreach ($levels as $code => $name)
      echo "<option value='$code'".($code == $level ? " selected" : "").">$name</option>";
    echo "</select>";
    echo " &nbsp; <span class='nowrap'><label for='$sinceID'>Since</label> <input type='text' name='$sinceName' id='$sinceID' value='$since' placeholder='2008-04-17' size='9' />";
    echo " &nbsp;<b>/</b>&nbsp; <label for='$maxID'>Last</label> <input type='text' name='$maxName' id='$maxID' value='$max' placeholder='10' size='2' /></span></div>";

    if ($level != "year") {
      $plural = lcfirst($levels[$level]);
      echo "<p><select class='widefat' id='$subwidgetID' name='$subwidgetName'>";
      echo "<option value='all'"; selected($showAll); echo ">Always show all $plural</option>";
      foreach ($groups as $key => $value) {
        echo "<option value='current-$key'"; selected(!$showAll && !$require && $requireField == $key); echo ">Show $plural for the current $key, if one is selected</option>";
      }
      foreach ($groups as $key => $value) {
        echo "<option value='require-$key'"; selected(!$showAll && $require && $requireField == $key); echo ">Only show $plural when a $key is selected</option>";
      }
      echo "</select></p>";

      /*
      echo "<p><label for='$groupID'><input type='checkbox' name='$groupName' id='$groupID' ".($group ? " checked" : "")."> Group by";
      if (count($groups) > 1) {
        echo "</label> &nbsp; <select name='$groupbyName' id='$groupbyID'>";
        foreach ($groups as $code => $name)
          echo "<option value='$code'".($code == $groupby ? " selected" : "").">$name</option>";
        echo "</select> <label for='$grouptagID'>as</label> ";
      } else {
        echo " years as</label> ";
      }
      echo "<select name='$grouptagName' id='$grouptagID'>";
      foreach ($grouptags as $tag)
        echo "<option value='$tag'".($tag == $grouptag ? " selected" : "").">$tag</option>";
      echo "</select></p>";

      echo "<p>When a year is selected, show <span class='nowrap'> &nbsp; ";
      echo "<label for='$showAllYesID'><input type='radio' name='$showAllName' value='on' id='$showAllYesID'".($showAll ? ' checked' : '')."> all years</label> &nbsp; ";
      echo "<label for='$showAllNoID'><input type='radio' name='$showAllName' value='' id='$showAllNoID'".($showAll ? '' : ' checked')."> current year only</label>";
      echo "</span></p>";

      echo "<p><label for='$requireID'><input type='checkbox' name='$requireName' id='$requireID'".($require ? ' checked' : '').">";
      echo " Only show when a ";
      if (count($groups) > 1) {
        echo "<select id='$requireFieldID' name='$requireFieldName'>";
        foreach ($groups as $key => $name) {
          echo "<option value='$key' ".selected($key, $requireField).">$key</option>";
        }
        echo "</select>";
      } else {
        foreach ($groups as $key => $value) {
          echo "<input type='hidden' id='$requireFieldID' name='$requireFieldName' value='$key'/>";
          echo $key;
        }
      }
      echo " has been selected</label></p>";
      */
    }

    $defaultFormat = $defaultFormats[$level];
    echo "<p><label for='$formatID'><a rel='nofollow' href='http://php.net/manual/en/function.date.php' target='_new'>Date format</a> &nbsp; </label> ";
    echo "<input id='$formatID' name='$formatName' type='text' placeholder='$defaultFormat' value='$format' size='8' />";
    echo " &nbsp; eg, <span class='search-preview'>".date(empty($format) ? $defaultFormat : $format)."</span>";
    echo "<br/>&nbsp; (Leave blank to use the default format)</p>";
  }

  function update($new_instance, $old_instance) {
    $instance = $old_instance;
    do_action('log', 'Saving date widget options', $new_instance);
    $new_instance = wp_parse_args($new_instance, array(
      'title' => '',
      'level' => 'year',
      'since' => '',
      'max' => '',
      'format' => '',
      'group' => false,
      'groupby' => 'year',
      'grouptag' => 'h4',
      'subwidget' => 'all',
      'showall' => false,
      'require' => false,
      'requireField' => 'year',
      ));
    $instance['title'] = strip_tags($new_instance['title']);
    $instance['level'] = strip_tags($new_instance['level']);
    $instance['format'] = strip_tags($new_instance['format']);
    $instance['group'] = (boolean) $new_instance['group'];
    // $instance['showall'] = (boolean) $new_instance['showall'];
    // $instance['require'] = (boolean) $new_instance['require'];
    // $instance['requireField'] = strip_tags($new_instance['requireField']);
    $instance['groupby'] = strip_tags($new_instance['groupby']);
    $instance['grouptag'] = strip_tags($new_instance['grouptag']);
    $instance['since'] = strip_tags($new_instance['since']);
    $instance['max'] = (integer) $new_instance['max'];

    if (preg_match('/^require-(.*)$/', $new_instance['subwidget'], $match)) {
      $instance['showall'] = false;
      $instance['require'] = true;
      $instance['requireField'] = $match[1];
    } else if (preg_match('/^current-(.*)$/', $new_instance['subwidget'], $match)) {
      $instance['showall'] = false;
      $instance['require'] = false;
      $instance['requireField'] = $match[1];
    } else {
      $instance['showall'] = true;
      $instance['require'] = false;
    }
    return $instance;
  }

  function widget($args, $instance) {
    if (!bang_fs_facets_visible()) return;
    bang_fs_date_facet_widget($args, $instance);
  }
}

function bang_fs_date_facet_widget($args, $instance) {
  $args = bang_fs_widget_args($args);
  extract($args, EXTR_SKIP);

  $_w = apply_filters('measure', 'date widget');
  $_p = apply_filters('measure', 'arguments');
  if (BANG_FS_DEBUG) do_action('log', 'fs: Date facet widget', $instance);

  //  parse parameters
  $settings = bang_fs_settings();
  $show_count = (boolean) $settings->show_count;
  $hide_empty = (boolean) $settings->hide_empty;
  $css_empty = (boolean) $settings->css_empty;
  $disable_empty = (boolean) $settings->disable_empty;

  $instance = bang_fs_widget_instance($instance, array(
  	'hide_prehistory' => true,
    'format' => '',
    'group' => false,
    'groupby' => '',
    'grouptag' => '',
    'showall' => false,
    'require' => false,
    'requireField' => '',
    'since' => '',
  	));
  $hide_prehistory = (boolean) $instance['hide_prehistory'];
  $title = $instance['title'];
  $level = $instance['level'];

  $format = $instance['format'];
  $format = trim($format);
  $defaultFormats = array("year" => "Y", "month" => "F Y", "week" => "\W\e\e\k \of j M Y", "day" => "D j M Y");
  if (is_null($format) || empty($format))
    $format = $defaultFormats[$level];

  $group = (boolean) $instance['group'];
  $groupby = $instance['groupby'];
  if ($group && empty($groupby)) {
    if ($level == 'month') $groupby = 'year';
    else $groupby = 'month';
  }
  $grouptag = $instance['grouptag'];
  if ($group && empty($grouptag)) $grouptag = "h4";
  $showAll = (boolean) $instance['showall'];
  $require = (boolean) $instance['require'];
  $requireField = $instance['requireField'];

  //  limit the number of options, especially if there's no date limit set
  if (empty($instance['max']))
    $max = false;
  else {
    $max = (integer) $instance['max'];
    // if (!is_numeric($max) || empty($max) || $max == 0) {
    //   if ($level == 'year') $max = 10;
    //   if ($level == 'month') $max = 120;
    //   if ($level == 'week') $max = 530;
    //   if ($level == 'day') $max = 3652;
    // }
    if ($max <= 10) $max = 10;
    if ($max > 200) $max = 200;
  }

  //  find the present day and process the 'since' field to find the start and end dates
  $since = $instance['since'];
  if (empty($since) || $since == 0)
    $since = strtotime(date('Y-m-d')." -".$max.$level);
  else
    $since = strtotime($since);

  $until = isset($instance['until']) ? $instance['until'] : 0;
  if (empty($until) || $until == 0) {
    $until = strtotime(date('Y-m-d'));
  }
  if (BANG_FS_DEBUG) do_action('log', 'fs: Date facet widget %s: since = %s, until = %s', $level, date('Y-m-d', $since), date('Y-m-d', $until));

  //  the hard parameters
  $get = wp_parse_args(bang_fs_get(), array('year' => '', 'month' => '', 'week' => '', 'day' => ''));
  $year = (isset($get['year']) && is_numeric($get['year'])) ? (int) $get['year'] : null;
  $month = isset($get['month']) ? $get['month'] : null;
  $week = isset($get['week']) ? $get['week'] : null;
  $day = isset($get['day']) ? $get['day'] : null;
  if ($day && !$month)
    $month = date('Y-m', strtotime($day));
  if ($month && !$year)
    $year = date('Y', strtotime($month));
  if (BANG_FS_DEBUG) do_action('log', 'fs: Date facet widget %s: year = %s, month = %s, week = %s, day = %s', $level, $year, $month, $week, $day);

  if ($require) {
    $set_fields = array(
      'year' => $year,
      'month' => $month,
      'week' => $week
      );
    if (BANG_FS_DEBUG) do_action('log', 'fs: Date facet widget %s: Required field', $level, $requireField, $set_fields);
    if (empty($set_fields[$requireField])) {
      if (BANG_FS_DEBUG) do_action('log', 'fs: Date facet widget %s: Required field missing: %s, skipping widget', $level, $requireField);
      return;
    }
  }

  $selection = false;
  $has_selection = false;

  //  establish the date range (from and to)

  switch ($level) {
    case "year":
      $from = strtotime(date('Y-01-01', $since));
      $to = strtotime(date('Y-01-01', $until).' +1 year');
      $selection = $year;
      break;

    case "month":
      if ($year && !$showAll) {
        $from = strtotime(date("$year-01-01"));
        $to = strtotime(date("$year-01-01").' +1 year');
      } else if ($group && $groupby == 'year') {
        $from = strtotime(date('Y-01-01', $since));
        $to = strtotime(date('Y-01-01', $until).' +1 year');
      } else {
        $from = strtotime(date('Y-m-01', $since));
        $to = strtotime(date('Y-m-01', $until).' +1 month');
      }
      $selection = $month;
      break;

    case "week":
      if ($month && !$showAll) {
        $from = strtotime($month);
        $to = strtotime(date('Y-m-d', $from)." +1 month");
      } else if ($year && !$showAll) {
        $from = strtotime(date("$year-01-01"));
        $to = strtotime(date("$year-01-01").' +1 year');
      } else {
        $from = strtotime(date('Y-m-d', $since));
        $to = strtotime(date('Y-m-d', $until).' +1 week');
      }
      // shuffle back to Mondays
      while (date("w", $from) != 1)
        $from = strtotime(date('Y-m-d', $from)." -1 day");
      while (date("w", $to) != 1)
        $to = strtotime(date('Y-m-d', $to).' -1 day');

      $selection = $week;
      break;

    case "day":
      if ($week && !$showAll) {
        $from = strtotime($week);
        $to = strtotime($week.' +7 days');
      } else if ($month && !$showAll) {
        $from = strtotime($month);
        $to = strtotime(date('Y-m-d', $from).' +1 month');
      } else if ($year && !$showAll) {
        $from = strtotime(date("$year-01-01"));
        $to = strtotime(date("$year-01-01").' +1 year');
      } else {
        $from = strtotime(date('Y-m-d', $since));
        $to = strtotime(date('Y-m-d', $until).' +1 day');
      }
      $selection = $day;
      break;
  }
  $selection = apply_filters('bang_faceted_search_date_widget_selected_value', $selection, $instance);
  if (empty($selection) && !empty($instance['default']))
  	$selection = $instance['default'];
  if (BANG_FS_DEBUG) {
    if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Date facet widget %s: since = %s, until = %s', $level, date('Y-m-d', $since), date('Y-m-d', $until));
    do_action('log', 'fs: Date facet widget %s: from: %s to %s', $level, date('Y-m-d', $from), date('Y-m-d', $to));
    if (!empty($selection)) do_action('log', 'fs: Date facet widget: Selection', $selection);
  }

  //do_action('log', 'fs Date: since', date('Y-m-d', $since));
  do_action('measure-end', $_p);

  //  list style
  $_l = apply_filters('measure', "list style");
  $options = array();
  $increment = " +1 ".$level." -1 day";
  $decrement = " -1 ".$level;

  $_o = apply_filters('measure', "options");
  $date = $to;
  $date = strtotime(date('Y-m-d', $date).$decrement); // initial decrement
  $count = 0;
  $instance['inputkey'] = $inputkey = empty($instance['name']) ? $level : $instance['name'];
  while ($date >= $from && ($max == false || $count < $max)) {
    $end = strtotime(date('Y-m-d', $date).$increment);

    switch ($level) {
      case "year":   $value = date("Y", $date);      break;
      case "month":  $value = date("Y-m", $date);    break;
      case "week":   $value = date('Y-m-d', $date);  break;
      case "day":    $value = date('Y-m-d', $date);  break;
    }
    $link = bang_fs_set_facet_url($inputkey, $value, false, array('year', 'month', 'week', 'day'));
    $name = date($format, $date); //bang_fs_date_name($style, $level, $format, $date, $since);
    $str = date('Y-m-d', $date);
    if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Date facet widget %s: Comparing to selection: %s == %s?', $level, $value, $selection, $value == $selection);
    $selected = $value == $selection;
    if ($selected) $has_selection = true;
    $options[] = (object) array(
      'date' => $date, 'end' => $end, 'str' => $str,
      'key' => $inputkey, 'value' => $value,
      'name' => $name, 'link' => $link,
      'selected' => $selected);

    $date = strtotime(date('Y-m-d', $date).$decrement);
    $count++;
  }
  $instance['has_selection'] = $has_selection;
  if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Date facet widget %s: Options', $level, '!value', $options);
  $options_args = array(
    'max' => $max,
    'since' => $since,
    // 'now' => $now,
    'level' => $level,
    'increment' => $increment,
  );
  $options = apply_filters('bang_faceted_search_date_widget_options', $options, $options_args, $instance);
  if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Date facet widget %s: Filtered options', $level, '!value', $options);
  if ($max !== false && count($options) > $max)
    $options = array_slice($options, 0, $max);
  do_action('measure-end', $_o);

  //  find counts and filter out empty options
  if ($hide_empty || $css_empty || $show_count) {
    $_c = apply_filters('measure', "count");
    if ($hide_empty) $accepted = array();

    $counts = bang_fs_date_counts($get, date('Y-m-d', $from), date('Y-m-d', $to), $level);
    if (BANG_FS_DEBUG) do_action('log', 'fs: Date facet widget %s: Counts', $level, $counts);

    foreach ($options as $option) {
      if (isset($counts[$option->value])) $option->count = $counts[$option->value]['cnt'];
      else $option->count = 0;
    }
    if (BANG_FS_DEBUG) do_action('log', 'fs: Date facet widget %s: Counted options', $level, '!value,selected,count', $options);

    $_f = apply_filters('measure', "bulk");
    $options = apply_filters('bang_faceted_search_date_widget_options_counts', $options, $args);
    do_action('measure-end', $_f);
    if (BANG_FS_DEBUG) do_action('log', 'fs: Date facet widget %s: Adjusted options', $level, '!value,selected,count', $options);

    if ($hide_empty) $accepted = array();
    foreach ($options as $option) {
      $option->count = apply_filters('bang_faceted_search_date_widget_option_count', $option->count, $option, $args);
      if ($hide_empty && ($option->count > 0 || $option->selected)) {
        $accepted[] = $option;
        if ($max !== false && count($accepted) >= $max) break;
      }
    }
    if ($hide_empty) $options = $accepted;

    if (BANG_FS_DEBUG) do_action('log', 'fs: Date facet widget %s: Filtered options', $level, '!value,selected,count', $options);
    do_action('measure-end', $_c);
  }

  // even if we're not hiding empty options, we can still hide empty options predating the group with the
  // oldest real option in it ... provided we actually have counts loaded
  if (!$hide_empty && $hide_prehistory && !empty($options) && ($css_empty || $disable_empty || $show_count)) {
    $options = array_values($options);
    $oldest = false;
    $final = false;
    foreach ($options as $option) {
      $final = $option->str;
      if (empty($option->count))
        $option->count = 0;
      if (($option->count > 0 || $option->selected) && !$oldest)
        $oldest = $option->str;
    }

    if ($oldest) {
      if (BANG_FS_DEBUG) do_action('log', 'fs Date: Oldest option', $oldest);
      if ($group) {
        switch ($groupby) {
          case 'year':
            $oldest = date('Y', strtotime($oldest)).'-01-01';
            break;

          case 'month':
            $oldest = date('Y-m', strtotime($oldest)).'-01';
            break;

          case 'week':
            $weekday = intval(date('N', strtotime($oldest)));
            $diff = 8 - $weekday; // TODO check results of this!
            $oldest = date('Y-m-d', strtotime($oldest.' +'.$diff.' days'));
            break;
        }
        if (BANG_FS_DEBUG) do_action('log', 'fs Date: Oldest group start', $oldest);

        if ($oldest != $final) {
          $cutoff = strtotime($oldest);
          $accepted = array();
          foreach ($options as $option) {
            if (strtotime($option->str) < $cutoff)
              break;
            $accepted[] = $option;
          }
          $options = $accepted;
          if (BANG_FS_DEBUG) do_action('log', 'fs Date: Cutoff options', '!value,selected,count', $options);
        }
      } else {
        $cutoff = strtotime($oldest);
        foreach ($options as $option) {
          if (strtotime($option->str) < $cutoff)
            break;
          $accepted[] = $option;
        }
        $options = $accepted;
        if (BANG_FS_DEBUG) do_action('log', 'fs Date: Cutoff options', '!value,selected,count', $options);
      }
    }
  }

  // give filters a chance to rename options as if they were taxonomy terms
  foreach ($options as $option) {
  	$option->name = bang_fs_taxonomy_term_name($option, $instance);
  }

  do_action('measure-end', $_l);
  $_e = apply_filters('measure', 'echo widget');

  // pick display style
  $style = isset($instance['style']) ? $instance['style'] : 'default';
  if (empty($style) || $style == 'default')
  	$style = $settings->style;
  if (empty($style) || $style == 'auto')
    $style = (count($options) > BANG_FS_AUTO_STYLE_THRESHOLD) ? 'select' : 'list';
  if ($style == 'drop_down')
  	$style = 'select';

  if ($group && !empty($options) && isset($defaultFormats[$groupby])) {
    $groups = array();
    $groupOptions = array();
    foreach ($options as $option) {
      $g = date($defaultFormats[$groupby], $option->date);
      if (!empty($g)) {
        $option->group = $g;
        $groups[] = $g;
        $groupOptions[$g][] = $option;
      }
    }
    $groups = array_filter($groups);
    $groups = array_unique($groups);

    if (!empty($groups)) {
      echo $before_widget.$before_title.esc_html($title).$after_title."<div class='fs-out'>";
      $n = 0;
      foreach ($groups as $group) {
        $opts = $groupOptions[$group];
        if (!empty($opts)) {
          $title = apply_filters('bang_faceted_search_group_name', $group, $group, $n, $opts);
          $cls = array("fs-out", "faceted-search-group", "fs-group-$group");
          if ($css_empty || $disable_empty) {
            $grpempty = true;
            foreach ($opts as $option) {
              if ($option->count > 0) {
                $grpempty = false;
                break;
              }
            }
            if ($grpempty) $cls[] = 'fs-empty-group';
          }
          $cls = apply_filters('bang_faceted_search_group_class', $cls, $group, $n, $opts);
          echo "<div class='".implode(" ", $cls)."'><$grouptag>".$title."</$grouptag><ul class='fs fs-$level' id='fs-$group-$level'>";
          foreach ($opts as $option) {
            $name = $option->name;
            if ($show_count)
              $name = bang_fs_facet_name_with_count($name, $option->count);
            $licls = array();
            if (($css_empty || $disable_empty) && $option->count == 0)
              $licls[] = bang_fs_empty_class();
            $licls = empty($licls) ? '' : "class='".implode(" ", $licls)."'";
            $selected = $option->selected ? ' class="selected"' : '';
            if ($settings->disable_empty && $option->count == 0)
              echo "<li><span $licls>$name</a></li>";
            else
              echo "<li><a$selected rel='nofollow' href='".esc_attr($option->link)."' $licls>$name</a></li> ";
          }
          echo "</ul></div>";
        }
        $n++;
      }
      echo "</div>".$after_widget;
      do_action('measure-end', $_e);
      do_action('measure-end', $_w);
      return;
    }
  }

  if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Date facet widget %s: Writing %s options', $level, count($options), '!value,selected,count', $options);
  if (!empty($options)) {
    $with_selection = $has_selection ? ' with-selection' : '';
    echo $before_widget.$before_title.$title.$after_title."<div class='fs-out'>";

    switch ($style) {
	  	case 'select':
	  	case 'drop_down':
		  	bang_fs_date_facet_terms__select($options, $instance);
    		break;

    	case 'text':
    		bang_fs_date_facet_terms__text($options, $instance);
    		break;

    	default:
    		bang_fs_date_facet_terms__list($options, $instance);
    		break;
    }

    echo "</div>".$after_widget;
  }
  do_action('measure-end', $_e);
  do_action('measure-end', $_w);
}

function bang_fs_date_facet_terms__select($options, $instance) {
	$inputkey = $instance['inputkey'];
	$level = $instance['level'];
  $baseurl = esc_attr(bang_fs_remove_facet_url($inputkey));
  echo "<select class='fs-select' id='fs-$level' name='".esc_attr($inputkey)."' data-url='$baseurl'>";
  if (!$instance['default']) {
	  $levelname = __($level);
	  $a = in_array($levelname, array('a', 'e', 'i', 'o', 'u')) ? 'an' : 'a';
	  $none = sprintf(__("Select $a %s...", 'faceted-search'), $levelname);
    $none = apply_filters('bang_fs_date_select_text', $date, $levelname);
	  echo "<option value=''>".$none."</option>";
	}
  foreach ($options as $option) {
    $name = $option->name;
    // if ($show_count)
    //   $name = bang_fs_facet_name_with_count($name, $option->count);
    $name = strip_tags($name);
    $selected = selected($option->selected);
    $disabled = disabled($disable_empty && $option->count == 0);
    echo "<option value='".esc_attr($option->value)."'$selected$disabled>$name</option>";
  }
  echo "</select>";
}

function bang_fs_date_facet_terms__list($options, $instance) {
  $settings = bang_fs_settings();
  $show_count = (boolean) $settings->show_count;
  $css_empty = (boolean) $settings->css_empty;

	$inputkey = $instance['inputkey'];
	$level = $instance['level'];
  $has_selection = $instance['has_selection'];
  $with_selection = $has_selection ? ' with-selection' : '';
  echo "<ul class='fs$with_selection' id='fs-$level' name='".esc_attr($inputkey)."'>";
  foreach ($options as $option) {
    $selected = $option->selected ? ' class="selected"' : '';
    $name = $option->name;
    // if ($show_count)
    //   $name = bang_fs_facet_name_with_count($name, $option->count);
    $licls = array();
    if (($css_empty || $disable_empty) && $option->count == 0)
      $licls[] = bang_fs_empty_class();
    $licls = empty($licls) ? '' : "class='".implode(" ", $licls)."'";
    if ($settings->disable_empty && $option->count == 0)
      echo "<li><span $licls>$name</span></li>";
    else
      echo "<li><a$selected rel='nofollow' href='".esc_attr($option->link)."' $licls>$name</a></li> ";
  }
  echo "</ul>";
}

function bang_fs_date_facet_terms__text($options, $instance) {
	$inputkey = $instance['inputkey'];
	$id = str_replace('-', '_', sanitize_title($inputkey));

	$levelname = lcfirst($instance['title']);
  $a = in_array($levelname[0], array('a', 'e', 'i', 'o', 'u')) ? 'an' : 'a';
  $select_text = sprintf(__("Select $a %s...", 'faceted-search'), $levelname);
  $select_text = apply_filters('bang_fs_date_select_text', $select_text, $levelname);
	$placeholder = esc_attr($select_text);

	$autocomplete_terms = array();
	$reverse_array_name = "reverse_".$id;
	$reverse_names = array();

	if (!$instance['default']) {
		$autocomplete_terms[] = $select_text;
		$reverse_names[$select_text] = '';
	}

	$selection = '';
	$value_name = '';
	foreach ($options as $option) {
		$autocomplete_terms[] = $option->name;
		$reverse_names[$option_name] = $option->value;

		if ($option->selected) {
			$selection = $option->value;
			$value_name = $option_name;
		}
	}

	$autocomplete = array(
		'source' => $autocomplete_terms,
		'autoFocus' => true,
		'scroll' => true,
		'delay' => 0,
		'minLength' => $min_length,
	);
	$autocomplete = apply_filters('bang_fs_date_autocomplete', $autocomplete);
	$autocomplete_conf = json_encode($autocomplete);



	// output
	echo "<input type='hidden' name='$inputkey' id='hidden_$id' value='$value'/>";
	echo "<input type='text' id='$id' value='$value_name' placeholder='$placeholder' />";

	?><script>
		jQuery(function($) {
	    var <?php echo $reverse_array_name ?> = <?php echo json_encode($reverse_names) ?>;

	    var input_<?php echo $id ?>_hidden = $("#hidden_<?php echo $id ?>");
	    var input_<?php echo $id ?> = $("#<?php echo $id ?>").autocomplete(<?php echo $autocomplete_conf; ?>);
	    var input_<?php echo $id ?>_change = function (event, ui) {
    		var value = $(event.target).val();
    		var key = value == "" ? "" : <?php echo $reverse_array_name ?>[value];
    		if (typeof key !== "undefined" && key != input_<?php echo $id ?>_hidden.val()) {
	    		input_<?php echo $id ?>_hidden.val(key).closest("form").submit();
	    	} else if (value == '<?php echo esc_js($select_text); ?>') {
	    		input_<?php echo $id ?>.val('');
	    	}
	    };
	    input_<?php echo $id ?>.focus(function() {
		    $(this).autocomplete("search", $(this).val());
			});
	    input_<?php echo $id ?>.on("autocompletechange", input_<?php echo $id ?>_change);
	    input_<?php echo $id ?>.on("autocompleteclose", input_<?php echo $id ?>_change);
	  });
  </script><?php
}
