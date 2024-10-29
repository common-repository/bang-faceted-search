<?php

/*
  bang_fs_get_count
  Count posts for the given args
*/

function bang_fs_get_count($args) {
  $db = bang_fs_wpdb();

  $args = apply_filters('bang_fs_count_query', bang_fs_get_unpaged($args));

  $fields = "$db->actual_posts.post_status as status";
  $groupby = 'status';
  $col = 'status';

  $counts = bang_fs_counts($args, null, null, $fields, $groupby, $col);
  //do_action('log', 'Count', $counts);
  if (!isset($counts['publish']['cnt']))
    return 0;
  return (int) $counts['publish']['cnt'];
}

/*
  bang_fs...counts
  Faceted search counting. Counts values for a whole taxonomy full of options at once.
*/

function bang_fs_post_type_counts($args) {
  $db = bang_fs_wpdb();

  $args = apply_filters('bang_fs_count_query', bang_fs_get_unpaged($args));
  unset($args['post_type']);

  $fields = "$db->actual_posts.$db->post_type as post_type";
  $groupby = 'post_type';

  return bang_fs_counts($args, null, null, $fields, $groupby, 'post_type');
}

function bang_fs_author_counts($args) {
  $db = bang_fs_wpdb();

  $args = apply_filters('bang_fs_count_query', bang_fs_get_unpaged($args));
  unset($args['author']);

  $join = "inner join $db->users on $db->actual_posts.post_author = $db->users.ID";
  $fields = "$db->users.ID as author";
  $groupby = 'author';

  return bang_fs_counts($args, $join, null, $fields, $groupby, 'author');
}

function bang_fs_date_counts($args, $from, $to, $level) {
  $db = bang_fs_wpdb();

  $args = bang_fs_get_unpaged($args);
  unset($args['year']);
  unset($args['month']);
  unset($args['week']);
  unset($args['day']);
  unset($args['date']);
  unset($args['post_date']);
  $args = apply_filters('bang_fs_count_query', $args);

  $where = array();
  if ($from)
    $where[] = "$db->actual_posts.post_date >= '".esc_sql($from)."'";
  if ($to)
    $where[] = "$db->actual_posts.post_date <= '".esc_sql($to)."'";
  $where = implode(" AND ", $where);
  switch ($level) {
    case 'day':
      $substr = 10;
      break;
    case 'month':
      $substr = 7;
      break;
    case 'year':
    default:
      $substr = 4;
      break;
  }
  $fields = "substring($db->actual_posts.post_date from 1 for $substr) as date";
  $groupby = "date";
  unset($args['level']);
  return bang_fs_counts($args, null, $where, $fields, $groupby, 'date');
}

function bang_fs_year_counts($args, $from, $to) {
  $db = bang_fs_wpdb();

  $args = apply_filters('bang_fs_count_query', bang_fs_get_unpaged($args));

  $years = array();
  for ($year = $to; $year >= $from; $year--)
    $years[] = "$db->actual_posts.post_date like '".esc_sql($year)."%'";
  $years = "(".implode(" or ", $years).")";

  unset($args['year']);
  $fields = "substring($db->actual_posts.post_date from 1 for 4) as year";
  $groupby = "year";
  $where = $years;
  return bang_fs_counts($args, null, $where, $fields, $groupby, 'year');
}

function bang_fs_tax_counts($args, $tax, $exclusive) {
  $db = bang_fs_wpdb();

  $args = apply_filters('bang_fs_count_query', bang_fs_get_unpaged($args));

  unset($args[$tax]);
  if (isset($args['tax_query']) && is_array($args['tax_query'])) {
    global $fs_count__tax_query__not_tax;
    $fs_count__tax_query__not_tax = $tax;
    $args['tax_query'] = array_filter($args['tax_query'], 'bang_fs_count__tax_query__not_tax');
  }

  $join = "inner join $db->term_relationships r on r.object_id = $db->posts.$db->id inner join $db->term_taxonomy t on r.term_taxonomy_id = t.term_taxonomy_id inner join $db->terms w on t.term_id = w.term_id";
  $where = "t.taxonomy='$tax'";
  $fields = "w.slug, w.name";
  $groupby = "t.term_id";
  return bang_fs_counts($args, $join, $where, $fields, $groupby);
}

function bang_fs_meta_counts($args, $meta, $values) {
  $db = bang_fs_wpdb();

  $args = apply_filters('bang_fs_count_query', bang_fs_get_unpaged($args));

  $join = "inner join $db->postmeta m on m.post_id = $db->posts.$db->id";
  $where = "m.meta_key = '".esc_sql($meta)."'";
  if (!empty($values)) {
    $slugs = array();
    foreach ($values as $value) {
      $slugs[] = $value->slug;
    }
    $values = "('".implode("', '", array_map('esc_sql', $slugs))."')";
    $where = "$where and m.meta_value in $values";
  }
  $fields = "m.meta_value";
  $groupby = "m.meta_value";
  return bang_fs_counts($args, $join, $where, $fields, $groupby, 'meta_value');
}

function bang_fs_wpdb() {
  global $wpdb;
  $db = (object) array(
    'posts' => $wpdb->posts,
    'actual_posts' => $wpdb->posts,
      'id' => 'ID',
      'post_type' => 'post_type',
    'term_relationships' => $wpdb->term_relationships,
    'term_taxonomy' => $wpdb->term_taxonomy,
    'terms' => $wpdb->terms,
    'postmeta' => $wpdb->postmeta
    );
  return apply_filters('bang_fs_wpdb', $db);
}

function bang_fs_counts($args, $fsjoin, $fswhere, $fsfields, $fsgroupby, $resultcol = 'slug') {
  $options = bang_fs_options();
  if (has_filter('bang_fs_count_params')) {
    $params = array($args, $fsjoin, $fswhere, $fsfields, $fsgroupby, $resultcol);
    $params = apply_filters('bang_fs_count_params', $params);
    list($args, $fsjoin, $fswhere, $fsfields, $fsgroupby, $resultcol) = $params;
  }

  $loc = bang_fs_location();
  if (!is_null($loc))
    $args = wp_parse_args($args, array('post_type' => $loc->post_types));
  if (isset($options->defaults))
    $args = wp_parse_args($args, $options->defaults);
  if (isset($options->force))
    $args = wp_parse_args($options->force, $args);

  unset($args['post_status']);
  unset($args['orderby']);
  unset($args['order']);
  unset($args['offset']);
  unset($args['posts_per_page']);
  unset($args['numposts']);
  unset($args['x']);
  unset($args['y']);
  if (BANG_FS_DEBUG) {
    $trace = debug_backtrace();
    $fn = $trace[1]['function'];
    do_action('log', "fs Count %s: Args", $fn, $args);
  }

  global $wpdb, $bang_fs_db;
  $db = $bang_fs_db = bang_fs_wpdb();
  if (BANG_FS_DEBUG >= 2 && BANG_FS_RELEVANSSI) do_action('log', 'fs Count %s: Db', $fn, $db);

  $post_status = apply_filters('bang_fs_count_post_status', array('publish'));
  $post_status = array_map('esc_sql', $post_status);
  $statuswhere = "$db->actual_posts.post_status in ('".implode("', '", $post_status)."')";

  $joins = array($fsjoin);
  $where = array($statuswhere, $fswhere);
  $fields = array("count($db->posts.$db->id) as cnt", $fsfields);

  //  parameters
  $n = 0;

  //  author
  if (!empty($args['author'])) {
    $author = (int) $args['author'];
    $where[] = "$db->actual_posts.post_author = $author";
  }
  unset($args['author']);

  //  pre-process date and time
  if (!empty($args['month']) && preg_match('!([0-9]{4})-([0-9]{2})!', $args['month'], $match)) {
    $args['year'] = (int) $match[1];
    $args['monthnum'] = (int) $match[2];
    unset($args['month']);
  }

  if (!empty($args['week']) && preg_match('!([0-9]{4})-([0-9]{2})-([0-9]{2})!', $args['week'], $match)) {
    $week = $args['week'];
    global $wpdb;
    $rows = $wpdb->get_results("select week('$week', 1);");
    $row = (array) $rows[0];
    $weeknum = array_pop($row);

    if (empty($args['year']))
      $year = (int) $match[1];
    $where[] = "week($db->actual_posts.post_date)=$weeknum";
  }

  if (!empty($args['day']) && preg_match('!([0-9]{4})-([0-9]{2})-([0-9]{2})!', $args['day'], $match)) {
    $args['year'] = (int) $match[1];
    $args['monthnum'] = (int) $match[2];
    $args['day'] = (int) $match[3];
  }

  //  date and time
  if (!empty($args['year'])) {
    $year = (int) $args['year'];
    unset($args['year']);
    $where[] = "YEAR($db->actual_posts.post_date)='$year'";
  }

  if (!empty($args['monthnum'])) {
    $month = (int) $args['monthnum'];
    unset($args['monthnum']);
    $where[] = "MONTH($db->actual_posts.post_date)='$month'";
  }

  if (!empty($args['day'])) {
    $day = (int) $args['day'];
    unset($args['day']);
    $where[] = "DAYOFMONTH($db->actual_posts.post_date)='$day'";
  }

  if (!empty($args['hour'])) {
    $hour = (int) $args['hour'];
    unset($args['hour']);
    $where[] = "HOUR($db->actual_posts.post_date)='$hour'";
  }

  if (!empty($args['minute'])) {
    $minute = (int) $args['minute'];
    unset($args['minute']);
    $where[] = "MINUTE($db->actual_posts.post_date)='$minute'";
  }

  if (!empty($args['second'])) {
    $second = (int) $args['second'];
    unset($args['second']);
    $where[] = "SECOND($db->actual_posts.post_date)='$second'";
  }


  //  custom fields
  if (!empty($args['meta_key'])) {
    $key = $args['meta_key'];
    $value = $args['meta_value'];
    $compare = $args['meta_compare'];

    $n++;
    $joins[] = "inner join $db->postmeta m$n on m$n.post_id = $db->posts.$db->id";
    $where[] = "m$n.meta_key = '".esc_sql($key)."'";
    switch ($compare) {
      case 'like':    $where[] = "m$n.meta_value like '".esc_sql($value)."'"; break;
      default:        $where[] = "m$n.meta_value = '".esc_sql($value)."'";    break;
    }
  }

  if (isset($args['meta_query']) && is_array($args['meta_query'])) {
    foreach ($args['meta_query'] as $meta_query) {
      $key = $meta_query['key'];
      $value = $meta_query['value'];
      $compare = $meta_query['compare'];

      $n++;
      $joins[] = "inner join $db->postmeta m$n on m$n.post_id = $db->posts.$db->id";
      $where[] = "m$n.meta_key = '".esc_sql($key)."'";
      switch ($compare) {
        case 'like':    $where[] = "m$n.meta_value like '".esc_sql($value)."'"; break;
        default:        $where[] = "m$n.meta_value = '".esc_sql($value)."'";    break;
      }
    }
  }

  // taxonomies
  if (isset($args['tax_query']) && is_array($args['tax_query'])) {
    if (isset($args['tax_query']['relation']))
      unset($args['tax_query']['relation']);
    foreach ($args['tax_query'] as $tax_query) {
      $tax = $tax_query['taxonomy'];
      $field = $tax_query['field'];
      if (empty($field)) $field = 'slug';
      if ($field == 'id') $field = 'term_id';
      $terms = $tax_query['terms'];
      $operator = $tax_query['operator'];
      if (empty($operator)) $operator = '';

      $n++;
      $joins[] = "inner join $db->term_relationships r$n on r$n.object_id = $db->posts.$db->id";
      $joins[] = "inner join $db->term_taxonomy t$n on r$n.term_taxonomy_id = t$n.term_taxonomy_id";
      $joins[] = "inner join $db->terms w$n on t$n.term_id = w$n.term_id";
      $where[] = "t$n.taxonomy = '".esc_sql($tax)."'";
      switch ($operator) {
        case 'IN': case 'in':
          if (!is_array($terms)) $terms = array($terms);
          $terms = array_map('bang_fs_count__quote_term', $terms);
          $terms = implode(",", $terms);
          $where[] = "w$n.$field in ($terms)";
          break;

        case 'NOT IN': case 'not in':
          if (!is_array($terms)) $terms = array($terms);
          $terms = array_map('bang_fs_count__quote_term', $terms);
          $terms = implode(",", $terms);
          $where[] = "w$n.$field not in ($terms)";
          break;

        default:
          if (is_array($terms)) $terms = array_shift($terms);
          $terms = bang_fs_count__quote_term($terms);
          $where[] = "w$n.$field = $terms";
          break;
      }
    }
  }
  unset($args['tax_query']);

  // any other arguments
  if (!is_array($args)) { do_action('log', 'fs count %s: Invalid args', $fn, $args); debug_print_backtrace(); die; }
  foreach ($args as $arg => $value) {
    if ($arg == 'meta_key' || $arg == 'meta_value' || $arg == 'meta_compare')
      continue;

    if ($arg == 's') {
      $value = trim($value);
      if (!empty($value)) {
        preg_match_all('/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', $args['s'], $matches);
        $swords = array_map('_search_terms_tidy', $matches[0]);
        $swords = array_filter($swords);
        foreach ($swords as $word) {
          $where[] = "($db->actual_posts.post_title like '%".esc_sql($word)."%' or $db->actual_posts.post_content like '%".esc_sql($word)."%')";
        }
      }
      continue;
    }

    if ($arg == 'post_type') {
      if (is_array($value) && count($value) == 1)
        $value = $value[0];

      if (is_array($value))
        $where[] = "$db->actual_posts.$db->post_type in ('".implode("','", array_map('esc_sql', $value))."')";
      else
        $where[] = "$db->actual_posts.$db->post_type = '".esc_sql($value)."'";
      continue;
    }

    if ($arg == 'year') {
      $where[] = "$db->actual_posts.post_date like '".esc_sql($value)."%'";
      continue;
    }

    if ($arg == 'post_name') {
      $where[] = "$db->actual_posts.post_name = '".esc_sql($value)."'";
      continue;
    }

    //  taxonomy parameters
    if (taxonomy_exists($arg)) {
    	// translate the term slug into a list of IDs, including full hierarchy
      $values = explode(",", $value);
      $term_ids = array();
      foreach ($values as $value_part) {
      	$term = get_term_by('slug', trim($value_part), $arg);
        if (empty($term))
          continue;
        if (BANG_FS_DEBUG >= 2) do_action('log', 'fs Count %s: Expanded term', $fn, $term);
      	$term_ids[] = $term->term_id;
      	$term_children = get_term_children($term->term_id, $arg);
        if (is_wp_error($term_children))
          continue;
      	if (BANG_FS_DEBUG >= 2) do_action('log', 'fs Count %s: Expanded term children of %s', $fn, $term->term_id, $term_children);
      	foreach ($term_children as $child_id) {
          if (!empty($child_id))
      		  $term_ids[] = $child_id;
      	}
      }
      if (BANG_FS_DEBUG >= 2) do_action('log', 'fs Count %s: Expanded term %s = %s to IDs', $fn, $arg, $value, $term_ids);

      // join on that list
      if (!empty($term_ids)) {
      	$n++;
      	$joins[] = "inner join $db->term_relationships r$n on r$n.object_id = $db->posts.$db->id";
      	$joins[] = "inner join $db->term_taxonomy t$n on r$n.term_taxonomy_id = t$n.term_taxonomy_id";
      	$where[] = "t$n.taxonomy = '".esc_sql($arg)."'";
      	if (count($term_ids) > 1)
      		$where[] = "t$n.term_id in (".implode(',', array_map('intval', $term_ids)).")";
      	else
      		$where[] = "t$n.term_id = ".intval($term_ids[0]);
      }
    }

    //  custom field parameters
    else {
      $n++;
      $joins[] = "inner join $db->postmeta m$n on m$n.post_id = $db->posts.$db->id";
      $where[] = "m$n.meta_key = '".esc_sql($arg)."'";
      $where[] = "m$n.meta_value = '".esc_sql($value)."'";
    }
  }

  // hooks
  $joins = apply_filters('bang_fs_count_joins', $joins, $db, $where);
  $joins = array_filter($joins);
  $joins = implode(" ", $joins);

  $where = apply_filters('bang_fs_count_where', $where, $db, $joins);
  $where = array_filter($where);
  $where = implode(" and ", $where);

  $fields = apply_filters('bang_fs_count_fields', $fields, $db);
  $fields = array_filter($fields);
  $fields = implode(", ", $fields);

  $sql = "select $fields from $db->posts $joins where $where group by $fsgroupby order by cnt desc";
  $sql = apply_filters('bang_fs_count_sql', $sql, $db);
  if (BANG_FS_DEBUG >= 2)
    do_action('log', "fs Count %s: SQL", $fn, $sql);

  $results = $wpdb->get_results($sql, ARRAY_A);
  $r = array();
  foreach ($results as $result) {
    $label = (string) $result[$resultcol];
    $r[$label] = $result;
  }
  if (BANG_FS_DEBUG >= 2)
    do_action('log', "fs Count %s: Results", $fn, $r);
  return $r;
}


function bang_fs_filter_count ($term) {
  return $term->count > 0;
}

function bang_fs_filter_count_or_has_children ($term) {
	return $term->count > 0 || (isset($term->has_children) && $term->has_children);
}

function bang_fs_cmp_name ($a, $b) {
  return strcmp($a->name, $b->name);
}

function bang_fs_cmp_count ($a, $b) {
  return $b->count - $a->count;
}

function bang_fs_count__quote_term($term) {
  return "'".esc_sql($term)."'";
}

function bang_fs_count__tax_query__not_tax($query) {
  global $fs_count__tax_query__not_tax;
  return $query['taxonomy'] != $fs_count__tax_query__not_tax;
}
