<?php

if (!defined('BANG_FS_FEEDBACK_DEBUG'))
	define('BANG_FS_FEEDBACK_DEBUG', false);

/*
  Give feedback on the current search parameters
*/
function bang_fs_write_feedback($args = array()) {
	/*
  $args = wp_parse_args($args, array(
    'search-key' => 's',
    'search-label' => 'You searched for',
    'search-label-always' => false,
    'search-quote' => '"',
    'dl' => false,
    'colon' => true,
    'remove' => true,
    'remove-text' => '<div data-code="f158" class="dashicons dashicons-no"></div> Remove',
    'table-header-row' => '',
    'label-class' => 'label',
    'value-class' => 'value',
    'remove-class' => 'remove'
  ));

  //  get the search parameters
  if (BANG_FS_DEBUG) do_action('log', 'fs: Feedback', bang_fs_get());
  $get = bang_fs_get_unpaged();
  unset($get['order']);
  unset($get['orderby']);
  $get = apply_filters('bang_fs_feedback_args', $get);
  if (BANG_FS_DEBUG >= 2) do_action('log', 'fs: Feedback: Adjusted', $get);
  if (empty($get))
    return;

  $formats = apply_filters('bang_fs_feedback_formats', array("year" => "Y", "month" => "F Y", "week" => "\W\e\e\k \of j M Y", "day" => "D j M Y"));

  //  prepare the output tags
  $colon = $args['colon'] ? ':' : '';
  $quote = $args['search-quote'];
  $search_key = $args['search-key'];
  $search_label = $args['search-label'];
  $remove_text = $args['remove-text'];

  if ($args['dl']) {
    $before_table = "<dl class='fs-feedback'>";
    $after_table = "</dl>";
    $before_tr = "";
    $after_tr = "";
    $before_label = "<dt class='{$args['label-class']}'>";
    $after_label = "</dt>";
    $before_value = "<dd class='{$args['value-class']}'>";
    $after_value = "</dd>";
    $before_remove = "";
    $after_remove = "";
  } else {
    $before_table = "<table class='fs-feedback golden-one-two-one'>";
    $after_table = "</table>";
    $before_tr = "<tr>";
    $after_tr = "</tr>";
    $before_label = "<td class='{$args['label-class']}'>";
    $after_label = "</td>";
    $before_value = "<td class='{$args['value-class']}'>";
    $after_value = "</td>";
    $before_remove = "<td class='{$args['remove-class']}'>";
    $after_remove = "</td>";
  }

  //  do the output
  echo $before_table.$args['table-header-row'];

  //  search string
  if ($search_key) {
    if (!empty($get[$search_key]) || $args['search-label-always']) {
      echo $before_tr.$before_label.$search_label.$colon.$after_label;
      if (!empty($get[$search_key])) {
        echo $before_value.$quote.esc_html($get[$search_key]).$quote.$after_value;
        if ($args['remove']) {
          //$link = bang_remove_facet_url($search_key);
          $link = esc_attr(bang_fs_set_facet_url('s', ''));
          echo "$before_remove<a href='$link' class='fs-remove'>$remove_text <span class='visuallyhidden'>keywords: ".esc_html($get[$search_key])."</a>$after_remove";
        }
      }
      echo $after_tr;
    }
    unset($get[$search_key]);
  }

  //  post type
  if (!empty($get['post_type'])) {
    $post_type = get_post_type_object($get['post_type']);
    if (!empty($post_type)) {
      echo $before_tr.$before_label."Type".$colon.$after_label;
      echo $before_value.$post_type->labels->singular_name.$after_value;
      $link = esc_attr(bang_fs_remove_facet_url('post_type'));
      echo "$before_remove<a href='$link' class='fs-remove'>$remove_text <span class='visuallyhidden'>Type: {$post_type->labels->singular_name}</span></a>$after_remove";
      echo $after_tr;
    }
    unset($get['post_type']);
  }

  //  author
  if (!empty($get['author'])) {
    $author = get_userdata($get['author']);
    if (!empty($author)) {
      echo $before_tr.$before_label."Author".$colon.$after_label;
      echo $before_value.get_avatar($author->ID, 20).$author->display_name.$after_value;
      $link = esc_attr(bang_fs_remove_facet_url('author'));
      echo "$before_remove<a href='$link' class='fs-remove'>$remove_text <span class='visuallyhidden'>Author: {$author->display_name}</span></a>$after_remove";
      echo $after_tr;
    }
    unset($get['author']);
  }

  //  date
  $date_key = false;
  if (isset($get['day']))    $date_key = 'day';
  else if (isset($get['week']))   $date_key = 'week';
  else if (isset($get['month']))  $date_key = 'month';
  else if (isset($get['year']))        $date_key = 'year';

  if ($date_key) {
    $date_value = strip_tags($get[$date_key]);
    if (BANG_FS_DEBUG >= 2) do_action('log', "fs: Feedback: Date '%s' = '%s'", $date_key, $date_value);

    if ($date_key != 'year') {
      //$dt = DateTime::createFromFormat("Y-m-d", $get[$date_key]);
      //$date_value = $dt->getTimestamp();
      $date_value = strtotime($date_value);
      $format = $formats[$date_key];
      $date_value = date($format, $date_value);
      if (BANG_FS_DEBUG >= 2) do_action('log', "fs: Feedback: Date '%s' = '%s' (format '%s')", $date_key, $date_value, $format);
    }

    unset($get[$date_key]);
    echo $before_tr.$before_label."Date".$colon.$after_label;
    echo $before_value.$date_value.$after_value;
    $link = esc_attr(bang_fs_remove_facet_url($date_key));
    echo "$before_remove<a href='$link' class='fs-remove'>$remove_text <span class='visuallyhidden'>Date: {$date_value}</span></a>$after_remove";
    echo $after_tr;
  }

  //  other facets
  foreach ($get as $key => $value) {
    if (is_object($value) && !empty($value->taxonomy))
      $key = $value->taxonomy;
    $tax = get_taxonomy($key);
    if (BANG_FS_DEBUG) do_action('log', 'fs: Feedback: Taxonomy %s', $key, $tax);

    $label = $key;
    if (!empty($tax)) {
      if (!empty($tax->labels->singular_name))
        $label = $tax->labels->singular_name;
      else if (!empty($tax->label))
        $label = $tax->label;
      else if (!empty($tax->labels->name))
        $label = $tax->labels->name;
      else if (!empty($tax->singular_label))
        $label = $tax->singular_label;
    }
    $label = esc_html(apply_filters('bang_fs_feedback_arg_label', $label, $key));

    $text = strip_tags($value);
    if (!is_object($value) && !empty($tax->name)) {
      $by = is_int($value) ? 'id' : (preg_match('![a-z0-9_-]!', $value) ? 'slug' : 'name');
      $term = get_term_by($by, $value, $tax->name);
      if (BANG_FS_DEBUG) do_action('log', 'fs: Feedback: Loading %s term by %s %s', $tax->name, $by, $value, $term);
      if (!empty($term->name))
        $text = $term->name;
    } else if (is_object($value) && !empty($term->name)) {
      $text = $value->name;
    }
    $text = esc_html(apply_filters('bang_fs_feedback_value_text', $text, $value, $key, $get));

    echo $before_tr.$before_label.$label.$colon.$after_label;
    echo $before_value.$text.$after_value;
    if ($args['remove']) {
      $link = esc_attr(bang_fs_remove_facet_url($key));
      echo $before_remove."<a href='$link' class='fs-remove'>$remove_text <span class='visuallyhidden'>$label: ".$text."</span></a>$after_remove";
    }
    echo $after_tr;
  }

  //  end
  echo $after_table;
*/









  // V2

  $args = bang_fs_write_feedback__defaults($args);
  $args['_in_table'] = true;
  $get = bang_fs_write_feedback__get();
  $get = bang_fs_write_feedback__sort_fields($get, $args);
  if (BANG_FS_FEEDBACK_DEBUG) do_action('log', 'fs: Feedback: Fields', $get);

	$strings = bang_fs_write_feedback__strings($args);
	extract($strings, EXTR_SKIP);

  echo $before_table;
  // other fields
  foreach ($get as $key => $value) {
  	bang_fs_write_feedback_field($key, $args, $value);
  }
  echo $after_table;
}

function bang_fs_write_feedback__sort_fields($get, $args) {
  $headline_fields = array(
    'q', 's', 'search', $args['search-key'],
    'post_type', 'post-type',
    'date', 'year', 'month', 'week', 'day',
    'author', 'user',
  );

  $sorted = array();
  foreach ($headline_fields as $key) {
    if (isset($get[$key])) {
      $sorted[$key] = $get[$key];
      unset($get[$key]);
    }
  }
  foreach ($get as $key => $value) {
    $sorted[$key] = $value;
  }
  return $sorted;
}

function bang_fs_write_feedback_field($key, $args, $value = null) {
  if (BANG_FS_FEEDBACK_DEBUG) do_action('log', 'fs: Feedback: Writing field %s = %s', $key, $value);
	if (in_array($key, ['q', 's', 'search', $args['search-key']])) {
		bang_fs_search_facet_feedback($key, $args, $value);
	} else if (in_array($key, ['date', 'year', 'month', 'week', 'day'])) {
		bang_fs_date_facet_feedback($key, $args, $value);
	} else if (in_array($key, ['post_type', 'post-type'])) {
		bang_fs_post_type_facet_feedback($key, $args, $value);
	} else if (in_array($key, ['author', 'user'])) {
		bang_fs_author_facet_feedback($key, $args, $value);
	} else {
		bang_fs_taxonomy_facet_feedback($key, $args, $value);
	}
}


// specific feedback writers - you can call these directly

function bang_fs_search_facet_feedback($key, $args, $value = null) {
	$args['multi'] = false; // can we safely assume this? it seems likely
  $quote = $args['search-quote'];
  switch ($quote) {
  	case '"': $before_quote = '“'; $after_quote = '”'; break;
  	case "'": $before_quote = '‘'; $after_quote = '’'; break;
  	default:  $before_quote = $quote; $after_quote = $quote; break;
  }

	$args = bang_fs_write_feedback__defaults($args);
	$label = bang_fs_write_feedback__label($args['search-label'], $key, $args);
	$value = bang_fs_write_feedback__value($key, $args, $value);
	$value_text = bang_fs_write_feedback__value_text($key, $args, $before_quote.$value.$after_quote);

	bang_fs_write_feedback__row($key, $label, $args, $value, $value_text);
}

function bang_fs_date_facet_feedback($key, $args, $value = null) {
	$args = bang_fs_write_feedback__defaults($args);
	$label = bang_fs_write_feedback__label(null, $key, $args);

	$value = bang_fs_write_feedback__value($key, $args, $value);

	$formats = apply_filters('bang_fs_feedback_formats', array("year" => "Y", "month" => "F Y", "week" => "\W\e\e\k \of j M Y", "day" => "D j M Y"));
	$value_text = bang_fs_write_feedback__value_text($key, $args, $value, function ($value_text, $value) use ($key, $formats) {

		// date format of the value
		// ...

		return $value;
	});

	bang_fs_write_feedback__row($key, $label, $args, $value, $value_text);
}

function bang_fs_post_type_facet_feedback($key, $args, $value = null) {
	$args = bang_fs_write_feedback__defaults($args);
	$label = bang_fs_write_feedback__label(null, $key, $args);

  if (!empty($value)) {
    $post_type = get_post_type_object($value);
    $value = $post_type->labels->name;
  }
	$value_text = bang_fs_write_feedback__value_text($key, $args, $value);

	bang_fs_write_feedback__row($key, $label, $args, $value, $value_text);
}

function bang_fs_author_facet_feedback($key, $args, $value = null) {
	$args = bang_fs_write_feedback__defaults($args);
	$label = bang_fs_write_feedback__label($facet, $key, $args);
	$value_text = bang_fs_write_feedback__value_text($key, $args, $value);

	bang_fs_write_feedback__row($key, $label, $args, $value, $value_text);
}

function bang_fs_taxonomy_facet_feedback($key, $args, $value = null) {
  if (is_object($value) && !empty($value->taxonomy))
    $key = $value->taxonomy;
  $tax = get_taxonomy($key);
  if (BANG_FS_FEEDBACK_DEBUG) do_action('log', 'fs: Feedback: Taxonomy %s', $key, $tax);

  $label = null;
  if (!empty($tax)) {
    if (!empty($tax->labels->singular_name))
      $label = $tax->labels->singular_name;
    else if (!empty($tax->label))
      $label = $tax->label;
    else if (!empty($tax->labels->name))
      $label = $tax->labels->name;
    else if (!empty($tax->singular_label))
      $label = $tax->singular_label;
  }

	$args = bang_fs_write_feedback__defaults($args);
	$label = bang_fs_write_feedback__label($label, $key, $args);
	$value = bang_fs_write_feedback__value($key, $args, $value);

	$value_text = bang_fs_write_feedback__value_text($key, $args, $value, function ($value_text, $value) use ($tax) {
		if (is_object($value) && !empty($value->name))
      return $value->name;

    if (!is_object($value) && !empty($tax->name)) {
      $by = is_int($value) ? 'id' : (preg_match('![a-z0-9_-]!', $value) ? 'slug' : 'name');
      $term = get_term_by($by, $value, $tax->name);
      if (BANG_FS_FEEDBACK_DEBUG) do_action('log', 'fs: Feedback: Loading %s term by %s %s', $tax->name, $by, $value, $term);
      if (!empty($term->name))
        return $term->name;
    }

    return strip_tags($value_text);
	});

	bang_fs_write_feedback__row($key, $label, $args, $value, $value_text);
}


// tools for consistent output behaviour

function bang_fs_write_feedback__label($label, $key, $args) {
	if (empty($label)) {
    $common_names = array(
      'q' => $args['search-label'],
      's' => $args['search-label'],
      'search' => $args['search-label'],
      $args['search-key'] => $args['search-label'],
      'post_type' => 'Type',
      'post-type' => 'Type',

      'date' => 'Date',
      'year' => 'Year',
      'month' => 'Month',
      'week' => 'Week',
      'day' => 'Day',

      'author' => 'Author',
      'user' => 'User',
    );

    $label = isset($common_names[$key]) ? $common_names[$key] : $key;
  }

  $label = apply_filters('bang_fs_feedback_arg_label', $label, $key, $args);
	if (BANG_FS_FEEDBACK_DEBUG) do_action('log', 'fs: Feedback label', $label);
	return $label;
}

function bang_fs_write_feedback__get() {
	static $get;
	if (!isset($get)) {
	  $get = bang_fs_get_unpaged();
	  unset($get['order']);
	  unset($get['orderby']);
	  $get = apply_filters('bang_fs_feedback_args', $get);
	  if (BANG_FS_FEEDBACK_DEBUG) do_action('log', 'fs: Feedback get', $get);
	}
  return $get;
}

function bang_fs_write_feedback__defaults($args) {
	return wp_parse_args($args, array(
		'_in_table' => false,
		'multi' => false,
		'hide_empty' => true,
		'show_label' => true,
    'show_count' => false,
    'sort_count' => false,
    'search-key' => 's',
    'search-label' => 'You searched for',
    'search-label-always' => false,
    'search-quote' => '"',
    'dl' => false,
    'colon' => true,
    'remove' => true,
    'remove-text' => '<div data-code="f158" class="dashicons dashicons-no"></div> Remove',
    'table-header-row' => '',
    'label-class' => 'label',
    'value-class' => 'value',
    'remove-class' => 'remove'
		));
}

function bang_fs_write_feedback__value($key, $args, $value, $callback = null) {
	if (!$args['_in_table'] && (!isset($value) || is_null($value))) {
	  $get = bang_fs_write_feedback__get();
	  $value = isset($get[$key]) ? $get[$key] : null;
	}

	if ($args['multi']) {
		if (is_null($value) || empty($value))
			$value = array();
		else
			$value = explode(',', $value);
		$value = array_map('trim', $value);
		if (!is_null($callback) && is_callable($callback))
			$value = array_map($callback, $value);
		$value = array_filter($value);
	} else {
		if (!is_null($callback) && is_callable($callback))
			$value = call_user_func($callback, $value);
	}

	if (BANG_FS_FEEDBACK_DEBUG) do_action('log', 'fs: Feedback value', $value);
	return $value;
}

function bang_fs_write_feedback__value_text($key, $args, $value, $callback = null) {
	$value_text = $value;

	if (!is_null($callback) && is_callable($callback)) {
		if ($args['multi']) {
			$value_text = array_map($callback, $value_text, $value);
		} else {
			$value_text = call_user_func($callback, $value_text, $value);
		}
	}

	$value_text = apply_filters('bang_fs_write_feedback__value_text', $value_text, $key, $args, $value);
	if (BANG_FS_FEEDBACK_DEBUG) do_action('log', 'fs: Feedback value text', $value_text);
	return $value_text;
}

function bang_fs_write_feedback__strings($args) {
  if ($args['dl']) {
  	$strings = array(
  		'before_table' => "<dl class='fs-feedback'>",
    	'after_table' => "</dl>",
    	'before_tr' => "",
    	'after_tr' => "",
    	'before_label' => "<dt class='{$args['label-class']}'>",
    	'after_label' => "</dt>",
    	'before_value' => "<dd class='{$args['value-class']}'>",
    	'after_value' => "</dd>",
    	'before_remove' => "",
    	'after_remove' => "",
  	);
  } else {
  	$strings = array(
    	'before_table' => "<table class='fs-feedback golden-one-two-one'>",
	    'after_table' => "</table>",
	    'before_tr' => "<tr>",
	    'after_tr' => "</tr>",
	    'before_label' => "<td class='{$args['label-class']}' rowspan='%d'>",
	    'after_label' => "</td>",
	    'before_value' => "<td class='{$args['value-class']}'>",
	    'after_value' => "</td>",
	    'before_remove' => "<td class='{$args['remove-class']}'>",
	    'after_remove' => "</td>",
    );
  }

	$strings = apply_filters('bang_fs_write_feedback__strings', $strings, $args);
	if (BANG_FS_FEEDBACK_DEBUG) do_action('log', 'fs: Feedback strings', $strings);
	return $strings;
}

// actually write the feedback row

function bang_fs_write_feedback__row($key, $label, $args, $value, $value_text) {
	// from this point we assume an array of values
	if (!$args['multi']) {
		$value = empty($value) ? array() : array($value);
		$value_text = empty($value) ? array() : array($value_text);
	}
	$value_text_by_value = array();
	for ($i=0; $i < count($value); $i++) {
		$value_text_by_value[$value[$i]] = $value_text[$i];
	}
	if (BANG_FS_FEEDBACK_DEBUG) do_action('log', 'fs: Feedback value map', $value_text_by_value);

	// if there are no values, don't write anything
	if (empty($value) && $hide_empty)
		return;

	// strings
  $colon = ($args['colon'] && !empty($label)) ? ':' : '';
  $remove_text = $args['remove-text'];
	$strings = bang_fs_write_feedback__strings($args);
	extract($strings, EXTR_SKIP);

	$remove_link = esc_attr(bang_fs_remove_facet_url($key));

	// output
	$contained = $args['_in_table'];
	if (!$contained) {
		echo $before_table;
		if (!empty($args['table-header-row']))
			echo $args['table-header-row'];
	}

	$get = bang_fs_write_feedback__get();
	$old_vs = isset($get[$key]) ? $get[$key] : '';
	if (BANG_FS_FEEDBACK_DEBUG) do_action('log', 'fs: Feedback: Old values for %s', $key, $old_vs);
	$old_vs = array_filter(array_map('trim', explode(',', $old_vs)));
	$old_vs = array_fill_keys($old_vs, true);
	if (BANG_FS_FEEDBACK_DEBUG) do_action('log', 'fs: Feedback: Old values for %s', $key, $old_vs);

  $first = true;
	foreach ($value as $v) {
		$vt = $value_text_by_value[$v];
		$remove_link = $args['multi'] ? bang_fs_remove_multi_facet_url($key, $v) : bang_fs_remove_facet_url($key);
		$remove_link = esc_attr($remove_link);

		/*
		if ($args['multi']) {
			$new_vs = $old_vs;
			unset($new_vs[$v]);
			if (BANG_FS_FEEDBACK_DEBUG) do_action('log', 'fs: Feedback: New values for %s', $key, $new_vs);
			$new_vs = array_keys(array_filter($new_vs));
			if (BANG_FS_FEEDBACK_DEBUG) do_action('log', 'fs: Feedback: New values for %s', $key, $new_vs);
			if (empty($new_vs))
				$remove_link = esc_attr(bang_fs_remove_facet_url($key));
			else {
				$new_vs = implode(',', $new_vs);
				$remove_link = esc_attr(bang_fs_set_facet_url($key, $new_vs));
			}
		}
		*/

		if (BANG_FS_FEEDBACK_DEBUG) do_action('log', 'fs: Feedback row', $v, $vt);

		echo $before_tr;
		if ($args['show_label']) {
			if ($first) {
				$first = false;
				printf($before_label, count($value));
				echo $label.$colon.$after_label;
			}
		}
	  echo $before_value.$vt.$after_value;
	  if (isset($args['remove']) && $args['remove']) {
	    echo $before_remove."<a href='$remove_link' rel='nofollow' class='fs-remove'>$remove_text <span class='visuallyhidden'>$label: ".$vt."</span></a>$after_remove";
	  }
	  echo $after_tr;
	}

	if (!$contained)
		echo $after_table;
}
