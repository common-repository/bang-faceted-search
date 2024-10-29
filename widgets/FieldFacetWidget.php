<?php

class FieldFacetWidget extends WP_Widget {
  function __construct () {
    parent::__construct('bang-field-widget', 'Custom field', array(
      'classname' => 'bang-field-widget',
      'description' => 'Custom field options for the faceted search sidebar.',
    ));
  }

  function FieldFacetWidget() {
    return self::__construct();
  }

  function form ($instance) {
    if (BANG_FS_DEBUG >= 2) do_action('log', "Instance", $instance);
    $title = esc_attr($instance['title']);
    $titleID = $this->get_field_id('title');
    $titleName = $this->get_field_name('title');
    $field = $instance['field'];
    $fieldID = $this->get_field_id('field');
    $fieldName = $this->get_field_name('field');

    $max = (integer) $instance['max'];
    if ($max <= 0) $max = '';
    $maxID = $this->get_field_id('max');
    $maxName = $this->get_field_name('max');

    $orders = bang_fs_taxonomy_orders($field);
    $orderby = isset($instance['orderby']) ? esc_attr($instance['orderby']) : '';
    if (!isset($orders[$orderby])) $orderby = 'menu_order';
    $orderbyID = $this->get_field_id('orderby');
    $orderbyName = $this->get_field_name('orderby');

    $group = (boolean) $instance['group'];
    $groupID = $this->get_field_id('group');
    $groupName = $this->get_field_name('group');

/*
    $groups = array("az" => "First letter A-Z");
    $groupby = esc_attr($instance['groupby']);
    if (!isset($groups[$groupby])) $groupby = "az";
    $groupbyID = $this->get_field_id('groupby');
    $groupbyName = $this->get_field_name('groupby');
*/
    $grouptags = array("h1", "h2", "h3", "h4", "h5", "h6");
    $grouptag = esc_attr($instance['grouptag']);
    if (!in_array($grouptag, $grouptags)) $grouptag = "h4";
    $grouptagID = $this->get_field_id('grouptag');
    $grouptagName = $this->get_field_name('grouptag');

    $more = (boolean) $instance['more'];
    $moreID = $this->get_field_id('more');
    $moreName = $this->get_field_name('more');

    $more_max = (int) $instance['more_max'];
    if ($more_max <= 0) $more_max = '';
    $more_maxID = $this->get_field_id('more_max');
    $more_maxName = $this->get_field_name('more_max');

    // display it
    ?><span class='bang-indicator search-indicator'></span><?php

    // echo "<label for='$titleID'>Title</label>";
    echo "<input class='search-title' id='$titleID' name='$titleName' type='text' placeholder='Title' value='$title' />\n";
    echo "<label for='$fieldID'>Custom field</label>";
    echo "<input class='widefat' id='$fieldID' name='$fieldName' type='text' placeholder='field' value='$field' />\n";

    //  sort
    echo "<p><label for='$orderbyID'>Sorted by</label> &nbsp; <select id='$orderbyID' name='$orderbyName'>";
    foreach ($orders as $code => $name) {
      echo "<option value='$code'";
      if ($code == $orderby) echo " selected";
      echo ">$name</option>";
    }
    echo "</select></p>\n";

    echo "<p><label for='$groupID'>Group by first letter</label> &nbsp; <select name='$grouptagName' id='$grouptagID'>";
    foreach ($grouptags as $tag)
      echo "<option value='$tag'".($tag == $grouptag ? " selected" : "").">$tag</option>";
    echo "</select></p>\n";

    //  maximum
    echo "<p><label for='$maxID'>Show no more than ".
      "&nbsp;<input type='text' size='1' id='$maxID' name='$maxName' value='$max' placeholder='0' />&nbsp;".
      " terms.</label></p>\n";

    //  show more
    if ($max > 0) {
      echo "<p><label for='$moreID'>&nbsp;&nbsp; <input type='checkbox' name='$moreName' id='$moreID'";
      if ($more) echo " checked";
      echo " /> With option to reveal</label> ";
      echo "&nbsp;<input type='text' size='1' name='$more_maxName' id='$more_maxID' value='$more_max' placeholder='0'/>&nbsp;";
      echo " more</p>\n";
    }
  }

  function update($new_instance, $old_instance) {
    $instance = $old_instance;
    $instance['title'] = strip_tags($new_instance['title']);
    $instance['field'] = strip_tags($new_instance['field']);
    $instance['orderby'] = strip_tags($new_instance['orderby']);
    $instance['group'] = (boolean) $new_instance['group'];
    $instance['grouptag'] = strip_tags($new_instance['grouptag']);
    $instance['max'] = (integer) $new_instance['max'];
    $instance['more'] = (boolean) $new_instance['more'];
    $instance['more_max'] = (integer) $new_instance['more_max'];
    return $instance;
  }

  function widget($args, $instance) {
    if (!bang_fs_facets_visible()) return;
    bang_fs_field_facet_widget($args, $instance);
  }
}

function bang_fs_field_facet_widget ($args, $instance) {
  $args = bang_fs_widget_args($args);
  extract($args, EXTR_SKIP);

  $instance = bang_fs_widget_instance($instance, array(
    'title' => '',
    'group' => false,
    'groupby' => 'az',
    'max' => 10,
    'orderby' => '',
    'more' => false,
    'nested' => false,
    'multi' => false,
    'css_empty' => false,
    'disable_empty' => false,
    'default_value' => ''
  ));
  if (empty($instance['orderby'])) {
    $instance['orderby'] = $instance['sort_count'] ? 'count' : 'az';
  }
  if (BANG_FS_DEBUG) do_action('log', 'fs: Field facet widget', $instance);

  $field = $instance['field'];
  $instance['taxonomy'] = $field;

  //  values
  if (isset($instance['values']))
    $values = $instance['values'];
  else if (isset($instance['values_cb']))
    $values = call_user_func($instance['values_cb']);
  else
    $values = bang_fs_field_values($field);
  $values = apply_filters('bang_fs_field_values', $values, $field);
  if (BANG_FS_DEBUG) do_action('log', 'fs: Field facet values', $values);

  //  selected value
  $get = bang_fs_get();
  if (!empty($get[$field]))
    $selected_value = $get[$field];
  else
    $selected_value = $instance['default_value'];

  $title = $instance['title'];

  $group = (boolean) $instance['group'];
  $groupby = $instance['groupby'];

  // don't show widget with nothing in it
  if (empty($values)) return;

  if ($group) {
    $groupshow = $instance['groupshow'];
    echo $before_widget.$before_title.esc_html($title).$after_title;

    $groups = array();
    $group_terms = array();
    foreach ($values as &$value) {
      switch ($groupby) {
        case 'slug':  $value->group = $value->slug; break;
        case 'az':    $value->group = $value->name; break;
        default:
          if (is_function('get_term_meta'))
            $value->group = get_term_meta($value->term_id, $groupby, true);
          break;
      }
      $g = strtolower(substr($term->group, 0, 1));
      if (!empty($g)) {
        $groups[] = $g;
        $group_terms[$g][] = $term;
      }
    }
    //do_action('log', 'fs group %s: Terms', $field, $terms);
    $groups = array_unique($groups);
    if (BANG_FS_DEBUG) do_action('log', 'fs field %s: Groups', $field, $groups);
    if (BANG_FS_DEBUG) do_action('log', 'fs field %s: Selected group', $field, $groupshow);

    //  index
    echo "<div class='fs-out'><p class='fs-term-index'>";
    foreach (range('a', 'z') as $letter) {
      echo "<a href='javascript:void(0);' data-index-point='$letter' class='fs-term-index-point";
      if (!in_array($letter, $groups)) echo " empty";
      if ($letter == $groupshow) echo " current";
      echo "'>".$letter."</a>";
    }
    echo "<a href='javascript:void(0);' data-index-point='all' class='fs-term-index-point all";
    if ($groupshow == 'all') echo " current";
    echo "'>View all</a>";
    echo "</p>";

    //  items
    foreach (range('a', 'z') as $letter) {
      if (in_array($letter, $groups)) {
        echo "<div class='fs-section";
        if ($letter == $groupshow) echo " current";
        echo "' id='fs-section-$letter'><h3>$letter</h3>";
        bang_fs_taxonomy_terms($group_terms[$letter], $instance, $selected_value);
        echo "</div>";
      }
    }
    echo "<div class='fs-section";
    if ($groupshow == "all") echo " current";
    echo "' id='fs-section-all'><h3>All</h3>";
    bang_fs_taxonomy_terms_debug("Writing all values", $tax, $values);
    bang_fs_taxonomy_terms($values, $instance, $selected_value);
    echo "</div>";
    echo "</div>".$after_widget;
    return;
  }

  echo $before_widget.$before_title.$title.$after_title."<div class='fs-out'>";
  bang_fs_taxonomy_terms($values, $instance, $selected_value);
  echo "</div>".$after_widget;
}

function bang_fs_field_values($field) {
  // todo Field values
  do_action('log', 'fs field %s: Not implemented!', $field);
  return array();
}

add_filter('bang_fs_field_values', 'bang_fs_fix_values', 20);
function bang_fs_fix_values($values) {
  $out = array();

  foreach ($values as $key => $value) {
    if (is_string($value)) {
      $slug = empty($key) ? $value : $key;
      $out[] = (object) array('slug' => $slug, 'name' => $value);
      continue;
    }

    if (is_int($value)) {
      $term = get_term($value);
      if (is_wp_error($term)) continue;
      $value = $term;
    }

    if (is_object($value)) {
      if (empty($value->slug)) {
        // ...
      }
      if (empty($value->name)) {
        // ...
      }
      $out[] = $value;
    }
  }

  return $out;
}
