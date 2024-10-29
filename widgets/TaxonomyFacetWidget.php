<?php

class TaxonomyFacetWidget extends WP_Widget {
  function __construct() {
    parent::__construct('bang-taxonomy-widget', 'Taxonomy', array(
      'classname' => 'bang-taxonomy-widget',
      'description' => 'Taxonomy options for the faceted search sidebar.',
    ));
  }

  function TaxonomyFacetWidget() {
    return self::__construct();
  }

  function form ($instance) {
    if (BANG_FS_DEBUG >= 2) do_action('log', "Instance", $instance);
    $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
    $titleID = $this->get_field_id('title');
    $titleName = $this->get_field_name('title');
    $taxonomy = isset($instance['taxonomy']) ? $instance['taxonomy'] : '';
    $taxonomyID = $this->get_field_id('taxonomy');
    $taxonomyName = $this->get_field_name('taxonomy');

    $styles = array("default" => "Use default", "list" => "Links", "drop_down" => "Drop-down menu", "radio" => "Radio buttons", "text" => "Free text");
    $style = esc_attr($instance['style']);
    if (!isset($styles[$style])) $style = "list";
    $styleID = $this->get_field_id('style');
    $styleName = $this->get_field_name('style');

    $multi = isset($instance['multi']) && (boolean) $instance['multi'];
    $multiID = $this->get_field_id('multi');
    $multiName = $this->get_field_name('multi');

    $max = isset($instance['max']) ? (integer) $instance['max'] : 0;
    if ($max <= 0) $max = '';
    $maxID = $this->get_field_id('max');
    $maxName = $this->get_field_name('max');

    $orders = bang_fs_taxonomy_orders($taxonomy);
    $orderby = isset($instance['orderby']) ? esc_attr($instance['orderby']) : '';
    if (!isset($orders[$orderby])) $orderby = 'menu_order';
    $orderbyID = $this->get_field_id('orderby');
    $orderbyName = $this->get_field_name('orderby');

    $group = isset($instance['group']) && (boolean) $instance['group'];
    $groupID = $this->get_field_id('group');
    $groupName = $this->get_field_name('group');

    $nested = isset($instance['nested']) && (boolean) $instance['nested'];
    $nestedID = $this->get_field_id('nested');
    $nestedName = $this->get_field_name('nested');

/*
    $groups = array("az" => "First letter A-Z");
    $groupby = esc_attr($instance['groupby']);
    if (!isset($groups[$groupby])) $groupby = "az";
    $groupbyID = $this->get_field_id('groupby');
    $groupbyName = $this->get_field_name('groupby');

    $grouptags = array("h1", "h2", "h3", "h4", "h5", "h6");
    $grouptag = esc_attr($instance['grouptag']);
    if (!in_array($grouptag, $grouptags)) $grouptag = "h4";
    $grouptagID = $this->get_field_id('grouptag');
    $grouptagName = $this->get_field_name('grouptag');
*/
    $more = isset($instance['more']) && (boolean) $instance['more'];
    $moreID = $this->get_field_id('more');
    $moreName = $this->get_field_name('more');

    $more_max = isset($instance['more_max']) ? (int) $instance['more_max'] : 0;
    if ($more_max <= 0) $more_max = '';
    $more_maxID = $this->get_field_id('more_max');
    $more_maxName = $this->get_field_name('more_max');

    $select_text = isset($instance['select_text']) ? $instance['select_text'] : '';
    $select_textID = $this->get_field_id('select_text');
    $select_textName = $this->get_field_name('select_text');


    // get the data
    $taxonomies = get_taxonomies(array(), 'objects');
    $settings = bang_fs_settings();

    $taxname = $taxonomies[$taxonomy]->labels->singular_name;
    if (empty($taxname))
      $taxname = $taxonomies[$taxonomy]->labels->name;
    if (empty($taxname))
      $taxname = $taxonomy;
    $taxnamewords = explode(' ', $taxname);
    if (!ctype_upper($taxnamewords[0]))
      $taxname = lcfirst($taxname);
    $a = in_array($taxname[0], array('a', 'e', 'i', 'o', 'u')) ? 'an' : 'a';
    $select_text_default = sprintf(__("Select $a %s...", 'faceted-search'), $taxname);
    $select_text_default = apply_filters('bang_fs_taxonomy_select_text', $select_text_default, $taxname);


    // display it
    ?><span class='bang-indicator search-indicator'></span><?php

    echo "<input class='search-title' id='$titleID' name='$titleName' type='text' placeholder='Title' value='$title' />\n";

    echo "<select id='$taxonomyID' name='$taxonomyName' class='widefat'>";
    foreach ($taxonomies as $name => $tax) {
      if (empty($tax->object_type)) continue;
      echo "<option value='{$tax->name}'";
      if ($tax->name == $taxonomy)
        echo " selected";
      echo ">{$tax->label}</option>";
    }
    echo "</select>\n";

    echo "<p><label for='$styleID'>Selection style</label> &nbsp; <select id='$styleID' name='$styleName'>";
    foreach ($styles as $code => $name) {
      echo "<option value='$code'";
      selected($code, $style);
      echo ">".$name."</option>";
    }
    echo "</select>";
    echo "</p>";

    echo "<p>";
    echo "<label for='$multiID'><input type='checkbox' id='$multiID' name='$multiName'"; checked($multi); echo "> Multi-select</label>";
    echo "</p>";

    //  sort
    echo "<p><label for='$orderbyID'>Sorted by</label> &nbsp; <select id='$orderbyID' name='$orderbyName'>";
    foreach ($orders as $code => $name) {
      echo "<option value='$code'";
      if ($code == $orderby) echo " selected";
      echo ">$name</option>";
    }
    echo "</select></p>\n";

    /*echo "<div><label for='$groupID'>Group by first letter</label> &nbsp; <select name='$grouptagName' id='$grouptagID'>";
    foreach ($grouptags as $tag)
      echo "<option value='$tag'".($tag == $grouptag ? " selected" : "").">$tag</option>";
    echo "</select></div>\n";*/

    echo "<p><label for='$nestedID'><input type='checkbox' name='$nestedName' id='$nestedID'";
    if ($nested) echo " checked";
    echo " /> Indent sub-categories</label></p>\n";

    if (!$settings->auto_style && !$settings->drop_down_style) {
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

    if ($settings->auto_style || $settings->drop_down_style) {
      echo "<p><label for='$select_textID'>Drop down heading</label>".
        "<input type='text' class='widefat' name='$select_textName' id='$select_textID' value='".esc_attr($select_text)."' placeholder='$select_text_default'/></p>";
    }
  }

  function update($new_instance, $old_instance) {
    $instance = $old_instance;
    $instance['title'] = strip_tags($new_instance['title']);
    if (isset($new_instance['style'])) $instance['style'] = strip_tags($new_instance['style']);
    $instance['multi'] = (boolean) $new_instance['multi'];
    $instance['taxonomy'] = strip_tags($new_instance['taxonomy']);
    $instance['orderby'] = strip_tags($new_instance['orderby']);
    $instance['group'] = (boolean) $new_instance['group'];
    $instance['grouptag'] = strip_tags($new_instance['grouptag']);
    $instance['max'] = (integer) $new_instance['max'];
    $instance['more'] = (boolean) $new_instance['more'];
    $instance['nested'] = (boolean) $new_instance['nested'];
    $instance['more_max'] = (integer) $new_instance['more_max'];
    $instance['select_text'] = strip_tags($new_instance['select_text']);

    if (empty($instance['title'])) {
      $taxonomy = get_taxonomy($instance['taxonomy']);
      $instance['title'] = $taxonomy->label;
    }
    return $instance;
  }

  function widget($args, $instance) {
    if (!bang_fs_facets_visible()) return;
    bang_fs_taxonomy_facet_widget($args, $instance);
  }
}

function bang_fs_taxonomy_facet_widget ($args, $instance) {
  $args = bang_fs_widget_args($args);
  extract($args, EXTR_SKIP);

  $settings = bang_fs_settings();

  $instance = bang_fs_widget_instance($instance, array(
    'title' => '',
    'group' => false,
    'groupby' => 'az',
    'max' => 10,
    'multi' => false,
    'more' => false,
    'nested' => false,
    'css_empty' => false,
    'disable_empty' => false,
    'select_text' => '',
    'orderby' => ''
  ));
  if (empty($instance['orderby'])) {
    $instance['orderby'] = $instance['sort_count'] ? 'count' : 'az';
  }
  if (BANG_FS_DEBUG) do_action('log', 'fs: Taxonomy facet widget', $instance);

  $tax = $instance['taxonomy'];
  $taxonomy = get_taxonomy($tax);
  if (empty($taxonomy))
    return;

  $is_multisite = false;
  if (is_multisite()) {
    $netsettings = bang_fs_network_settings();
    $is_multisite = $netsettings->multisite;
  }

  $loc = bang_fs_location();
  $fs_options = bang_fs_options();

  $get = bang_fs_get();
  if (!empty($get[$tax]))
    $selected_term = $get[$tax];
  else
    $selected_term = null;
  if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Taxonomy facet widget: Selected term for %s', $tax, $selected_term);
  $selected_terms = is_string($selected_term) ? explode(',', $selected_term) : array();
  $selected_terms = array_fill_keys($selected_terms, true);

  if (!empty($fs_options->post_types))
    $post_types = $fs_options->post_types;
  else if (!empty($get['post_type']))
    $post_types = $get['post_type'];
  else if (isset($loc))
    $post_types = $loc->post_types;
  else
    $post_types = '';

  if ($is_multisite)
    $post_types = bang_fs_double_post_types($post_types);
  if (is_string($post_types))
    $post_types = explode(',', $post_types);

  if (!empty($post_types) && !$is_multisite) {
    $post_type_taxonomies = get_object_taxonomies($post_types);
    if (!in_array($tax, $post_type_taxonomies)) {
      if (BANG_FS_DEBUG) do_action('log', 'fs: Taxonomy facet widget: %s not applicable for %s (for post types %s)', $tax, $post_type_taxonomies, $post_types);
      return;
    }
  }

  $title = $instance['title'];
  if (empty($title))
    $title = $taxonomy->labels->singular_name;

  $group = (boolean) $instance['group'];
  $groupby = $instance['groupby'];

  $nested = (boolean) $instance['nested'];

  //  get the terms
  $terms = bang_fs_taxonomy_facet_terms($instance);
  bang_fs_taxonomy_terms_debug("Ready terms", $tax, $terms);

  foreach ($terms as $term) {
    $term->selected = (
	    	(isset($selected_terms[$term->term_id]) && $selected_terms[$term->term_id]) ||
	    	(isset($selected_terms[$term->slug]) && $selected_terms[$term->slug]) ||
	    	(isset($selected_terms[$term->name]) && $selected_terms[$term->name])
    	);
  }
  // if (BANG_FS_DEBUG) do_action('log', 'fs tax %s: Selected terms', $tax, $terms);

  // don't show widget with nothing in it
  if (empty($terms)) return;

  if ($group) {
    $groupshow = $instance['groupshow'];
    echo $before_widget.$before_title.esc_html($title).$after_title;

    $groups = array();
    $group_terms = array();
    foreach ($terms as &$term) {
      switch ($groupby) {
        case 'slug':  $term->group = $term->slug; break;
        case 'az':    $term->group = $term->name; break;
        default:
          if (is_callable('get_term_meta'))
            $term->group = get_term_meta($term->term_id, $groupby, true);
          break;
      }
      $g = strtolower(substr($term->group, 0, 1));
      if (!empty($g)) {
        $groups[] = $g;
        $group_terms[$g][] = $term;
      }
    }
    //do_action('log', 'fs tax %s: Terms', $tax, $terms);
    $groups = array_unique($groups);
    if (BANG_FS_DEBUG) do_action('log', 'fs tax %s: Groups', $tax, $groups);
    if (BANG_FS_DEBUG) do_action('log', 'fs tax %s: Selected group', $tax, $groupshow);

    //  index
    echo "<div class='fs-out' data-taxonomy='$taxonomy->name'><p class='fs-term-index'>";
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
        bang_fs_taxonomy_terms($group_terms[$letter], $instance, $selected_term);
        echo "</div>";
      }
    }
    echo "<div class='fs-section";
    if ($groupshow == "all") echo " current";
    echo "' id='fs-section-all'><h3>All</h3>";
    bang_fs_taxonomy_terms_debug("Writing all terms", $tax, $terms);
    bang_fs_taxonomy_terms($terms, $instance, $selected_term);
    echo "</div>";
    echo "</div>".$after_widget;
    return;
  }


  echo $before_widget.$before_title.$title.$after_title."<div class='fs-out' data-taxonomy='$taxonomy->name'>";
  bang_fs_taxonomy_terms($terms, $instance, $selected_term);
  echo "</div>".$after_widget;
}




// unmoved...




function bang_fs_taxonomy_orders($taxonomy) {
  $orders = array('count' => "Result count", 'menu_order' => "Sort order", 'az' => "Name A-Z", 'za' => "Name Z-A");
  global $wpdb;
  if ($wpdb->termmeta) {
    $res = $wpdb->get_results("select distinct meta_key from $wpdb->termmeta");
    foreach ($res as $r) {
      $key = $r->meta_key;
      if (!isset($orders[$key])) $orders[$key] = ucfirst($key);
    }
  }
  $orders = apply_filters('bang_fs_taxonomy_orders', $orders, $taxonomy);
  return $orders;
}

function bang_fs_taxonomy_groups($taxonomy) {
  $groups = array('az' => 'Name A-Z');
  global $wpdb;
  if ($wpdb->termmeta) {
    $res = $wpdb->get_results("select distinct meta_key from $wpdb->termmeta");
    if (is_array($res)) {
      foreach ($res as $r) {
        $key = $r->meta_key;
        if (!isset($groups[$key])) $groups[$key] = ucfirst($key);
      }
    }
  }
  $groups = apply_filters('bang_fs_taxonomy_groups', $groups, $taxonomy);
  return $gruops;
}

function bang_fs_taxonomy_cmp ($a, $b) {
  global $fs_taxonomy_orderby;
  if (is_callable('get_term_meta')) {
    $va = get_term_meta($a->term_id, $fs_taxonomy_orderby, true);
    $vb = get_term_meta($b->term_id, $fs_taxonomy_orderby, true);
    return strcmp($va, $vb);
  }
  return strcmp($a, $b);
}

/*
function bang_fs_taxonomy_facet_terms_children_of (&$pool, $parent_id, $indent = 0) {
	$children = array();
	if (BANG_FS_DEBUG >=2) do_action('log', 'Selecting from pool: ID %s @%s', $parent_id, $indent, array_keys($pool));
	foreach ($pool as $i => $child) {
		if (empty($child))
			continue;
		if ($child->parent == $parent_id) {
			$child->indent = $indent;
			$children[] = $child;
			$pool[$i] = null;
			$grandchildren = bang_fs_taxonomy_facet_terms_children_of($pool, $child->term_id, $indent + 1);
			$children = array_merge($children, $grandchildren);
		}
	}
	return $children;
}
*/

function bang_fs_taxonomy_facet_group_terms_by($pool, $attrname, $default) {
  if (BANG_FS_DEBUG) do_action('log', 'Grouping terms by %s', $attrname, '!term_id,name,'.$attrname, $pool);
  $groups = array();
  foreach ($pool as $term) {
    $attr = isset($term->$attrname) ? $term->$attrname : $default;
    if (empty($attr))
      $attr = $default;
    if (!isset($groups[$attr]))
      $groups[$attr] = array();
    $groups[$attr][] = $term;
  }
  if (BANG_FS_DEBUG) do_action('log', 'Grouped terms by %s', $attrname, $groups);
  return $groups;
}

function bang_fs_taxonomy_facet_nested_terms__nest ($id, $indent, $grouped, &$nested) {
  $n_terms = 0;
  if (isset($grouped[$id])) {
    if (BANG_FS_DEBUG) do_action('log', 'Nesting tree: parent id = %s, indent = %s, found %s', $id, $indent, count($grouped[$id]));
    foreach ($grouped[$id] as $child) {
      $child->indent = $indent;
      $nested[] = $child;

      // get the number we just added
      end($nested);
      $key = key($nested);
      $nested[$key]->n_children = bang_fs_taxonomy_facet_nested_terms__nest($child->term_id, $indent + 1, $grouped, $nested);
      $nested[$key]->has_children = $nested[$key]->n_children > 0;
      $n_terms++;
    }
  }
  return $n_terms;
}

function bang_fs_taxonomy_facet_nested_terms($pool) {
  $grouped = bang_fs_taxonomy_facet_group_terms_by($pool, 'parent', 0);
  $nested = array();
  bang_fs_taxonomy_facet_nested_terms__nest(0, 0, $grouped, $nested);
  if (BANG_FS_DEBUG) do_action('log', 'Nested tree', '!term_id,name,indent', $nested);
  return $nested;
}

//  the slugs of direct ancestors and descendents of a term
//  used to prevent parents and children being selected at once
function bang_fs_taxonomy_term_hierarchy($term) {
  // do_action('log', 'Looking for hierarchy of term', $term);
  $hier = array();
  if (isset($term->parent) && $term->parent != 0) {
    $hier = get_ancestors($term->term_id, $term->taxonomy, 'taxonomy');
  }
  if ($term->has_children) {
    $children = get_term_children($term->term_id, $term->taxonomy);
    $hier = array_merge($hier, $children);
  }
  $hier = array_map(function ($h_id) use ($term) {
    $h = get_term($h_id, $term->taxonomy);
    return $h->slug;
  }, $hier);
  // do_action('log', 'Hierarchy', $hier);
  return $hier;
}

function bang_fs_taxonomy_terms_prune_tree (&$pool) {
	if (BANG_FS_DEBUG >= 2) {
		do_action('log', 'Pruning tree');
		foreach ($pool as $term) {
			do_action('log', ' - %s (%s) = %s %s', $term->term_id, $term->parent, $term->count, $term->name);
		}
	}

	for ($i = 0; $i < count($pool); )
		$i = bang_fs_taxonomy_terms_prune_tree_child_count($pool, $i);
}

function bang_fs_taxonomy_terms_prune_tree_child_count(&$pool, $index) {
	$parent_id = $pool[$index]->term_id;
	if (BANG_FS_DEBUG >= 2) do_action('log', ' - pruning tree from index %s with parent id %s', $index, $parent_id);

	$new_index = $index + 1;
	while (isset($pool[$new_index]) && $pool[$new_index]->parent == $parent_id && $new_index < count($pool)) {
		$new_index = bang_fs_taxonomy_terms_prune_tree_child_count($pool, $new_index);
	}

	$has_children = false;
	for ($i = $index + 1; $i < $new_index; $i++) {
		if (is_numeric($pool[$i]->count) && $pool[$i]->count > 0) {
			$has_children = true;
			break;
		}
	}

	if (BANG_FS_DEBUG >= 2) do_action('log', ' - parent %s: %s', $pool[$index]->term_id, $has_children ? 'found children' : 'no children');
	$pool[$index]->has_children = $has_children;
	return $new_index;
}

function bang_fs_taxonomy_facet_terms ($instance) {
  $settings = bang_fs_settings();
  $hide_empty = (boolean) $settings->hide_empty;
  $css_empty = (boolean) $settings->css_empty;
  $disable_empty = (boolean) $settings->disable_empty;
  $show_count = (boolean) $settings->show_count;
  $show_nested = (boolean) $instance['nested'];

  $tax = $instance['taxonomy'];
  $taxonomy = get_taxonomy($tax);

  $orderby = $instance['orderby'];
  $sort_count = $orderby == 'count';

  if (BANG_FS_DEBUG) do_action('log', 'fs tax %s: orderby = %s, %s', $tax, $orderby, $sort_count);

  switch ($orderby) {
    case 'menu_order': $orderby = 'menu_order'; $order = 'ASC';  break;
    case 'az':         $orderby = 'name';       $order = 'ASC';  break;
    case 'za':         $orderby = 'name';       $order = 'DESC'; break;
    default:           $orderby = 'none';       $order = 'ASC';  break;
  }
  if (isset($instance['specific']) && $instance['specific']) {
    global $post;
    $terms = wp_get_object_terms($post->ID, $tax);
  } else {
    $terms = get_terms($tax, array(
      'hide_empty' => $hide_empty && !$show_nested,
      'orderby' => $orderby,
      'order' => $order,
    ));
  }
  $get = bang_fs_get();
  $terms = apply_filters('bang_fs_taxonomy_facet_terms', $terms, $tax, $get, $instance);

  // sort after the fact if we must
  if ($orderby == 'none' && !empty($instance['orderby']) && $instance['orderby'] != 'none') {
    $orders = bang_fs_taxonomy_orders($taxonomy);
    if (isset($orders[$instance['orderby']]) && $instance['orderby'] != "count") {
      global $fs_taxonomy_orderby;
      $fs_taxonomy_orderby = $instance['orderby'];
      uasort($terms, 'bang_fs_taxonomy_cmp');
    }
  }

  // indent nested terms
  if ($show_nested) {
  	if (BANG_FS_DEBUG >= 1) do_action('log', 'Nesting terms', '!term_id,name,parent', $terms);
  	$pool = array();
  	foreach ($terms as $term) {
  		$pool[$term->slug] = $term;
  	}
    $nested_terms = bang_fs_taxonomy_facet_nested_terms(array_values($pool));

  	// add back in anything left in the pool
  	// $terms = array_merge($nested_terms, array_filter(array_values($pool)));
    $terms = $nested_terms;
  	if (BANG_FS_DEBUG >= 1) do_action('log', 'Nested terms', '!term_id,name,indent', $terms);
  }
  if (BANG_FS_DEBUG >= 2) bang_fs_taxonomy_terms_debug("Original terms", $tax, $terms);

  // search-specific count
  if ($show_count || $sort_count || $hide_empty || $css_empty || $disable_empty) {
    $get = bang_fs_get();
    $counts = bang_fs_tax_counts($get, $tax, false);
    if (BANG_FS_DEBUG >= 2) do_action('log', "fs tax %s: Counts", $tax, $counts);

    foreach ($terms as $term) {
      if (empty($term))
        continue;
      $term->count = isset($counts[$term->slug]['cnt']) ? $counts[$term->slug]['cnt'] : 0;
    }

    if ($hide_empty) {
    	$terms = array_values(array_filter($terms));
    	if ($show_nested)
    		bang_fs_taxonomy_terms_prune_tree($terms);

      $terms = array_filter($terms, 'bang_fs_filter_count_or_has_children');
    }

    if ($sort_count) {
      usort($terms, 'bang_fs_cmp_count');
    }
  }

  if (BANG_FS_DEBUG) do_action('log', 'fs tax %s: terms', $tax, '!term_id,name', $terms);
  return $terms;
}

function bang_fs_taxonomy_terms_debug ($msg, $tax, $terms) {
  if (BANG_FS_DEBUG < 2) return;

  $terms_out = array();
  foreach ($terms as $term)
    $terms_out[$term->slug] = "{$term->name} ({$term->count})";
  do_action('log', "fs tax %s: $msg", $tax, $terms_out);
}

function bang_fs_taxonomy_terms($terms, $instance) {

  $settings = bang_fs_settings();

  $style = isset($instance['style']) ? $instance['style'] : 'default';
  if (empty($style) || $style == 'default')
  	$style = $settings->style;
  if (empty($style) || $style == 'auto')
    $style = (count($terms) > BANG_FS_AUTO_STYLE_THRESHOLD) ? 'select' : 'list';
  if (BANG_FS_DEBUG) do_action('log', 'fs Facet style', $style);

  $has_selection = false;
  foreach ($terms as $term)
  	if (isset($term->selected) && $term->selected)
  		$has_selection = true;
  $instance['has_selection'] = $has_selection;

  switch ($style) {
  	case 'select':
  	case 'drop_down':
  		bang_fs_taxonomy_terms__select($terms, $instance);
  		return;
  	case 'radio':
  		bang_fs_taxonomy_terms__radio($terms, $instance);
  		return;
  	case 'text':
  		bang_fs_taxonomy_terms__text($terms, $instance);
  		return;
  	default:
  		bang_fs_taxonomy_terms__list($terms, $instance);
  		return;
  }
}

function bang_fs_taxonomy_terms__select($terms, $instance) {
  //do_action('log', $instance);
  $max = $instance['max'];
  $offset = isset($instance['offset']) ? (int) $instance['offset'] : 0;
  if (empty($offset)) $offset = 0;
  $tax = $instance['taxonomy'];
  $show_more = (boolean) $instance['more'];

  $terms = array_slice($terms, $offset);
  if ($max > 0 && count($terms) > $max) {
    $terms2 = array_slice($terms, $max);
    $terms = array_slice($terms, 0, $max);
  }

  $key = $instance['taxonomy'];
  $baseurl = esc_attr(bang_fs_remove_facet_url($key));
  echo "<select name='$key' class='fs-select' data-url='$baseurl'>\n";

  $select_text = $instance['select_text'];
  if (empty($select_text)) {
    $taxonomy = get_taxonomy($key);
    $taxname = $taxonomy->labels->singular_name;
    $taxnamewords = explode(' ', $taxname);
    if (!ctype_upper($taxnamewords[0]))
      $taxname = lcfirst($taxname);
    $a = in_array($taxname[0], array('a', 'e', 'i', 'o', 'u')) ? 'an' : 'a';
    $select_text = sprintf(__("Select $a %s...", 'faceted-search'), $taxname);
  }
  $select_text = apply_filters('bang_fs_taxonomy_select_text', $select_text, $taxname);
  $select_text = esc_html(__($select_text, 'faceted-search'));
  $selected = selected(!$instance['has_selection'], true, false);
  echo "<option value=''$selected>$select_text</option>";

  foreach ($terms as $term) {
    $value = $term->slug;
    $name = bang_fs_taxonomy_term_name($term, $instance);
    $name = strip_tags($name);
    $selected = selected($term->selected, true, false);
    $disabled = disabled($settings->disable_empty && $term->count == 0, true, false);
    echo "<option value='$value'$selected$disabled>$name</option>\n";
  }
  echo "</select>\n";
  return;
}

function bang_fs_taxonomy_terms__list($terms, $instance) {
  $settings = bang_fs_settings();
  $max = $instance['max'];
  $offset = isset($instance['offset']) ? (int) $instance['offset'] : 0;
  if (empty($offset)) $offset = 0;
  $tax = $instance['taxonomy'];
  $show_more = (boolean) $instance['more'];
  $show_nested = (boolean) $instance['nested'];

  $terms = array_slice($terms, $offset);
  if ($max > 0 && count($terms) > $max) {
    $terms2 = array_slice($terms, $max);
    $terms = array_slice($terms, 0, $max);
  }

  // write the
  //do_action('log', 'fs tax %s: Max: %s    More: %s    Terms: %s, %s', $tax, $max, $show_more, count($terms), count($terms2));
  $cols = isset($instance['columns']) ? $instance['columns'] : 1;

  $head_callback = function ($i) use ($instance) {
	  $with_selection = $instance['has_selection'] ? '' : ' with-selection';
	  echo "<ul class='fs$with_selection'>";
  };
  $tail_callback = function ($i) {
  	echo "</ul>";
  };
  $lastindent = 0;
  $term_callback = function ($term) use ($instance, $settings, $show_nested, &$lastindent) {
    $settings = bang_fs_settings();
    $name = bang_fs_taxonomy_term_name($term, $instance);

    if ($show_nested) {
      $i = isset($term->indent) ? $term->indent : 0;
      if ($i > $lastindent) {
        echo "<ul data-parent-id='{$term->parent}'>";
      } else if ($i < $lastindent) {
        echo "</ul></li>";
      }
      $lastindent = $i;
    }

    $link = $instance['multi'] ?
      ($term->selected ?
        bang_fs_remove_multi_facet_url($instance['taxonomy'], $term->slug) :
        bang_fs_set_multi_facet_url($instance['taxonomy'], $term->slug, false, bang_fs_taxonomy_term_hierarchy($term))) :
      bang_fs_set_facet_url($instance['taxonomy'], $term->slug);
    $link = esc_attr($link);
    $licls = array();
    if (isset($term->selected) && $term->selected)
      $licls[] = 'selected';
    if (($instance['css_empty'] || $instance['disable_empty']) && ($term->count == 0 || empty($term->count)))
      $licls[] = bang_fs_empty_class();
    if (isset($term->has_children) && $term->has_children)
      $licls[] = 'has-children';
    $licls = empty($licls) ? '' : "class='".implode(" ", $licls)."'";
    $term_id = isset($term->term_id) ? "data-term-id='{$term->term_id}'" : '';
    if ($instance['disable_empty'] && $term->count == 0)
      echo "<li><span $licls $term_id>{$name}</span>";
    else
      echo "<li><a rel='nofollow' href='$link' $licls $term_id>{$name}</a>";

    if (!(isset($term->has_children) && $term->has_children))
      echo "</li>";
  };

  bang_fs_taxonomy_terms__cols($terms, $cols, $term_callback, $head_callback, $tail_callback);

  if ($show_more && !empty($terms2)) {
    $morelabel = apply_filters('fs-more-label', 'Show more');
    echo "<a href='javascript:void(0);' class='fs-showmore' rel='#fs-$tax-more'>$morelabel</a>";

    echo "<div id='fs-$tax-more' class='fs-more'>";
    bang_fs_taxonomy_terms__cols($terms2, $cols, $term_callback, $head_callback, $tail_callback);
    echo "</div>";
  }
}

function bang_fs_taxonomy_terms__radio($terms, $instance) {
  $settings = bang_fs_settings();
  //do_action('log', $instance);
  // $tax = $instance['taxonomy'];
  $show_more = (boolean) $instance['more'];

  $terms = array_slice($terms, $offset);
  if ($max > 0 && count($terms) > $max) {
    $terms2 = array_slice($terms, $max);
    $terms = array_slice($terms, 0, $max);
  }

  $cols = isset($instance['columns']) ? $instance['columns'] : 1;

  $head_callback = function ($i) use ($instance) {
	  $with_selection = $instance['has_selection'] ? '' : ' with-selection';
	  echo "<ul class='fs$with_selection'>";
  };
  $tail_callback = function ($i) {
  	echo "</ul>";
  };
  $term_callback = function ($term) use ($instance) {
    $title = bang_fs_taxonomy_term_name($term, $instance);
    $link = $instance['multi'] ?
      bang_fs_set_multi_facet_url($instance['taxonomy'], $term->slug, false, bang_fs_taxonomy_term_hierarchy($term)) :
      bang_fs_set_facet_url($instance['taxonomy'], $term->slug);
    $link = esc_attr($link);

    $checked = checked($term->selection, true, false);
    $disabled = "";
    $licls = array("fs-radio");
    if (($settings->css_empty || $settings->disable_empty) && ($term->count == 0 || empty($term->count)))
      $licls[] = bang_fs_empty_class();
    if ($settings->disable_empty && $term->count == 0)
    	$disabled = "disabled";
    if ($term->has_children)
      $licls[] = 'has-children';
    $licls = empty($licls) ? '' : "class='".implode(" ", $licls)."'";

    $name = $instance['taxonomy'];
    $value = $term->slug;
    $id = sanitize_title($name.'-'.$value);
    echo "<li><label for='$id'><input type='radio' id='$id' name='$name' value='$value' $checked $disabled $licls data-term-id='{$term->term_id}'> $title</li>";
  };

  bang_fs_taxonomy_terms__cols($terms, $cols, $term_callback, $head_callback, $tail_callback);
}

function bang_fs_taxonomy_terms__text($terms, $instance) {
	$name = $instance['taxonomy'];
	$id = str_replace('-', '_', sanitize_title($name));

	$hidden_value = '';
	$value_name = '';
	$value_multi_prefix = array();
	if ($instance['multi']) {
		foreach ($terms as $term) {
			if ($term->selected && !empty($term->slug))
				$value_multi_prefix[] = $term->slug;
		}
		$value_multi_prefix = array_filter($value_multi_prefix);
		$value_multi_prefix = implode(',', $value_multi_prefix);
		$hidden_value = $value_multi_prefix;
		do_action('log', 'Taxonomy terms text multi value prefix', $value_multi_prefix);
	} else {
		foreach ($terms as $term) {
			if ($term->selected) {
				$hidden_value = $term->slug;
				$value_name = esc_attr($term->name);
			}
		}
	}
	$tax = get_taxonomy($instance['taxonomy']);

  $taxname = $tax->labels->singular_name;
  $taxnamewords = explode(' ', $taxname);
  if (!ctype_upper($taxnamewords[0]))
    $taxname = lcfirst($taxname);
  $a = in_array($taxname[0], array('a', 'e', 'i', 'o', 'u')) ? 'an' : 'a';
  $select_text = sprintf(__("Select $a %s...", 'faceted-search'), $taxname);
	$placeholder = esc_attr($select_text);

	$autocomplete_terms = array($select_text);
	$reverse_array_name = "reverse_".$id;
	$reverse_names = array();

	$reverse_names[$select_text] = '';
	foreach ($terms as $term) {
		$term_name = bang_fs_taxonomy_term_name($term, $instance);
		$autocomplete_terms[] = $term_name;
		$reverse_names[$term_name] = $term->slug;
	}

	// mininum length before menu: pick based on number of terms
	$min_length = intval(floor(log10(count($terms) - 40)));
	if (!is_int($min_length) || $min_length < 0) $min_length = 0;

	$autocomplete = array(
		'source' => $autocomplete_terms,
		'autoFocus' => true,
		'scroll' => true,
		'delay' => 0,
		'minLength' => $min_length,
	);
	$autocomplete = apply_filters('bang_fs_taxonomy_autocomplete', $autocomplete);
	$autocomplete_conf = json_encode($autocomplete);

	// output
	echo "<input type='hidden' name='$name' id='hidden_$id' value='$hidden_value'/>";
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
    			<?php if (!empty($value_multi_prefix)) { ?>
    				key = "<?php echo $value_multi_prefix ?>,"+key;
					<?php } ?>
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


// utility function to split initially visible and hidden terms
function bang_fs_taxonomy_terms__split_more($terms, $instance) {
  $max = intval($instance['max']);
  $offset = isset($instance['offset']) ? intval($instance['offset']) : 0;
  if (empty($offset) || !is_int($offset)) $offset = 0;

  $terms = array_slice($terms, $offset);
  if ($max > 0 && count($terms) > $max) {
    $below = array_slice($terms, $max);
    $above = array_slice($terms, 0, $max);
	  return array($above, $below);
  }
  return array($terms, array());
}


// utility function to write options in columns
function bang_fs_taxonomy_terms__cols($terms, $cols, $term_callback, $head_callback = null, $tail_callback = null) {
	$cols = intval($cols);

	if (!is_int($cols) || $cols <= 1) {
		if (is_callable($head_callback))
			call_user_func($head_callback, 0);
    if (is_callable($term_callback)) {
  		foreach ($terms as $term) {
  			call_user_func($term_callback, $term);
  		}
    }
		if (is_callable($tail_callback))
			call_user_func($tail_callback, 0);
		return;
	}

	// distribute items into columns
	$columns = array_fill(0, $cols, array());
  $i = 0;
  foreach ($terms as $term) {
    $c = $i % $cols;
    $i++;
    if (BANG_FS_DEBUG) do_action('log', 'fs tax: Adding %s to column %s', $term->term_id, $c);
    $columns[$c][] = $term;
  }

  //  pad the columns (for the sake of zebra striping)
  $pad_to = max(array_map(function ($column) { return count($column); }, $columns));
  $columns = array_map(function ($column) use ($pad_to) { return array_pad($column, $pad_to, (object) array()); }, $columns);


  if (BANG_FS_DEBUG) do_action('log', 'fs tax: Distributed %s %s columns, %s', $cols, count($columns), $columns);

  echo "<div class='fs-columns fs-columns-$cols'>";
  $i = 0;
  foreach ($columns as $column) {
    echo "<div class='fs-col'>";
		if (is_callable($head_callback))
			call_user_func($head_callback, $i);
    foreach ($column as $term) {
    	call_user_func($term_callback, $term);
    }
		if (is_callable($tail_callback))
			call_user_func($tail_callback, $i);
    echo "</div>";
    $i++;
  }
  echo "</div>";
}








function bang_fs_taxonomy_terms_col($terms, $instance, $selected_term) {
  $cols = (int) $instance['columns'];

  if (isset($instance['table']) && $instance['table']) {
    if (BANG_FS_DEBUG) do_action('log', 'fs tax: Displaying with a table');

    echo "<table class='fs-table'><tr>";
    $i = 0;
    foreach ($terms as $term) {
      if ($i >= $cols) {
        echo "</tr>\n<tr>";
        $i = 0;
      }
      $link = $instance['multi'] ?
        bang_fs_set_multi_facet_url($instance['taxonomy'], $term->slug, false, bang_fs_taxonomy_term_hierarchy($term)) :
        bang_fs_set_facet_url($instance['taxonomy'], $term->slug);
      $link = esc_attr($link);
      $name = bang_fs_taxonomy_term_name($term, $instance);
      echo "<td><a rel='nofollow' href='$link'>$name</a></td>";
      $i++;
    }
    //  pad the last row (for the sake of zebra striping)
    while ($i < $cols) {
      echo "<td></td>";
      $i++;
    }
    echo "</tr></table>";

  } else {
    $columns = array();
    for ($c = 0; $c < $cols; $c++) $columns[$c] = array();

    // distribute the actual elements
    $i = 0;
    foreach ($terms as $term) {
      $c = $i % $cols;
      $i++;
      if (BANG_FS_DEBUG) do_action('log', 'fs tax: Adding %s to column %s', $term->term_id, $c);
      $columns[$c][] = $term;
    }
    //$columns = array_filter($columns);

    //  pad the columns (for the sake of zebra striping)
    $max = 0;
    foreach ($columns as $column) { if (count($column) > $max) $max = count($column); }
    for ($c =0; $c < $cols; $c++)
      while (count($columns[$c]) < $max)
        $columns[$c][] = (object) array();

    if (BANG_FS_DEBUG) do_action('log', 'fs tax: Distributed %s %s columns, %s', $cols, count($columns), $columns);

    echo "<div class='fs-columns'><div class='yui3-g'>";
    foreach ($columns as $column) {
      echo "<div class='yui3-u-1-$cols'>";
      bang_fs_taxonomy_terms_ul($column, $instance, $selected_term);
      echo "</div>";
    }
    echo "</div></div>";
  }
}

function bang_fs_taxonomy_terms_ul($terms, $instance, $selected_term) {
  $settings = bang_fs_settings();
  $show_nested = (boolean) $instance['nested'];
  $with_selection = $selected_term == null ? '' : ' with-selection';
  echo "<ul class='fs$with_selection'>";
  if (isset($instance['reset']) && $instance['reset'] && $instance['reset_position'] == 'first' && !empty($selected_term)) {
    $link = esc_attr(bang_fs_remove_facet_url($instance['taxonomy']));
    echo "<li><a rel='nofollow' href='$link'>{$instance['reset']}</a></li>";
  }
  $lastindent = 0;
  foreach ($terms as $term) {
    $name = bang_fs_taxonomy_term_name($term, $instance);
    if ($show_nested) {
      $i = isset($term->indent) ? $term->indent : 0;
      if ($i > $lastindent) {
        echo "<ul data-parent-id='{$term->parent}'>";
      } else if ($i < $lastindent) {
        echo "</ul></li>";
      }
      $lastindent = $i;
    }

    $link = $instance['multi'] ?
      bang_fs_set_multi_facet_url($instance['taxonomy'], $term->slug, false, bang_fs_taxonomy_term_hierarchy($term)) :
      bang_fs_set_facet_url($instance['taxonomy'], $term->slug);
    $link = esc_attr($link);
    $licls = array();
    if ($term->slug == $selected_term)
      $licls[] = 'selected';
    if (($settings->css_empty || $settings->disable_empty) && ($term->count == 0 || empty($term->count)))
      $licls[] = bang_fs_empty_class();
    if ($term->has_children)
      $licls[] = 'has-children';
    $licls = empty($licls) ? '' : "class='".implode(" ", $licls)."'";
    if ($settings->disable_empty && $term->count == 0)
      echo "<li><span $licls data-term-id='{$term->term_id}'>{$name}</span>";
    else
      echo "<li><a rel='nofollow' href='$link' $licls data-term-id='{$term->term_id}'>{$name}</a>";

    if (!$term->has_children)
      echo "</li>";
  }
  if (isset($instance['reset']) && $instance['reset'] && $instance['reset_position'] == 'last' && !empty($selected_term)) {
    $link = esc_attr(bang_remove_facet_url($instance['taxonomy']));
    echo "<li><a rel='nofollow' href='$link'>{$instance['reset']}</a></li>";
  }
  echo "</ul>";
}

function bang_fs_taxonomy_term_name($term, $instance) {
  $settings = bang_fs_settings();
  $name = esc_html($term->name);
  $name = apply_filters('bang_fs_facet_name', $name, $term, $instance);
  if ($settings->show_count)
    $name = bang_fs_facet_name_with_count($name, $term->count);
  if (isset($term->indent) && is_numeric($term->indent) && $term->indent > 0)
    $name = "<span class='indent' data-indent='".$term->indent."'>".str_repeat("&nbsp; &nbsp; ", intval($term->indent))."</span>".$name;
  return $name;
}
