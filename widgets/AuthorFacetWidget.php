<?php

class AuthorFacetWidget extends WP_Widget {
  function __construct() {
    parent::__construct('author-facet-widget', 'Author', array(
      'classname' => 'author-facet-widget',
      'description' => 'Search by post author',
    ));
  }

  function AuthorFacetWidget() {
    return self::__construct();
  }

  function form($instance) {
    do_action('log', 'fs Author: showing instance', $instance);
    $title = esc_attr($instance['title']);
    $titleID = $this->get_field_id('title');
    $titleName = $this->get_field_name('title');

    $styles = array("name" => "Name", "name_avatar" => "Name and avatar", "avatar" => "Avatar only");
    $style = esc_attr($instance['style']);
    if (!isset($styles[$style])) $style = "name_avatar";
    $styleID = $this->get_field_id('style');
    $styleName = $this->get_field_name('style');

    $avatar_size = (int) $instance['avatar_size'];
    if (empty($avatar_size) || $avatar_size <= 0) $avatar_size = 24;
    if ($avatar_size > 512) $avatar_size = 512;
    $sizeID = $this->get_field_id('avatar_size');
    $sizeName = $this->get_field_name('avatar_size');

    $max = (integer) $instance['max'];
    if ($max <= 0) $max = '';
    $maxID = $this->get_field_id('max');
    $maxName = $this->get_field_name('max');

    $orders = bang_fs_taxonomy_orders($taxonomy);
    $orderby = isset($instance['orderby']) ? esc_attr($instance['orderby']) : '';
    if (!isset($orders[$orderby])) $orderby = 'menu_order';
    $orderbyID = $this->get_field_id('orderby');
    $orderbyName = $this->get_field_name('orderby');

    // form
    ?><span class='bang-indicator search-indicator'></span><?php

    // echo "<label for='$titleID'>Title</label>";
    echo "<input class='search-title' id='$titleID' name='$titleName' type='text' placeholder='Title' value='$title' />";

    echo "<div>Display style &nbsp; ";
    foreach ($styles as $code => $name)
      echo " &nbsp; <label for='$styleID-$code'><input type='radio' id='$styleID-$code' name='$styleName' value='$code'".($code == $style ? " checked" : "")."> $name</label>";
    echo "</div>";

    if ($style != 'name') {
      echo "<p><label for='$sizeID'>Avatar size</label> &nbsp; ";
      echo "<input type='text' size='3' name='$sizeName' id='$sizeID' value='$avatar_size' placeholder='24' /> px</p>";
    }

    //  sort
    echo "<p><label for='$orderbyID'>Sorted by</label> &nbsp; <select id='$orderbyID' name='$orderbyName'>";
    foreach ($orders as $code => $name) {
      echo "<option value='$code'";
      if ($code == $orderby) echo " selected";
      echo ">$name</option>";
    }
    echo "</select></p>\n";

    //  maximum
    echo "<p><label for='$maxID'>Show no more than ".
      "&nbsp;<input type='text' size='1' id='$maxID' name='$maxName' value='$max' placeholder='0' />&nbsp;".
      " terms.</label></p>\n";
  }

  function update($new_instance, $old_instance) {
    $instance = array();
    $instance['title'] = strip_tags($new_instance['title']);
    $instance['style'] = strip_tags($new_instance['style']);
    $instance['avatar_size'] = (int) $new_instance['avatar_size'];
    $instance['orderby'] = strip_tags($new_instance['orderby']);
    $instance['max'] = (integer) $new_instance['max'];
    do_action('log', 'fs Author: saving instance', $instance);
    return $instance;
  }

  function widget($args, $instance) {
    if (!bang_fs_facets_visible()) return;
    bang_fs_author_facet_widget($args, $instance);
  }
}

function bang_fs_author_facet_widget($args, $instance) {
  $args = bang_fs_widget_args($args);
  extract($args, EXTR_SKIP);

  $instance = bang_fs_widget_instance($instance, array(
    'title' => 'Author',
    'style' => 'name_avatar',
    'avatar_size' => 24,
    'orderby' => '',
    'max' => 50,
  ));
  if (empty($instance['orderby']))
    $instance['orderby'] = $instance['sort_count'] ? 'count' : 'date';
  if (BANG_FS_DEBUG) do_action('log', 'fs: Author facet widget', $instance);

  $settings = bang_fs_settings();

  $title = $instance['title'];
  $style = $instance['style'];
  $avatar_size = $instance['avatar_size'];
  $max = (int) $instance['max'];
  $orderby = $instance['orderby'];
  do_action('log', 'fs Author: instance', $instance);

  $hide_empty = (boolean) $settings->hide_empty;
  $show_count = (boolean) $settings->show_count;
  $sort_count = $orderby == 'count';


  //  get all the authors of real posts
  global $wpdb;
  $sql = <<<END
select $wpdb->users.id as id, count($wpdb->posts.ID) as count from $wpdb->users
inner join $wpdb->posts on $wpdb->users.ID = $wpdb->posts.post_author
group by $wpdb->users.ID
END;
  $results = $wpdb->get_results($sql);
  $authors = array();
  foreach ($results as $result)
    $authors[] = get_userdata((int) $result->id);
  //do_action('log', 'fs Authors: All', $authors);

  if ($show_count || $sort_count || $hide_empty) {
    $get = bang_fs_get();
    $counts = bang_fs_author_counts($get);
    if (BANG_FS_DEBUG >= 2) do_action('log', "fs Authors: Counts", $counts);

    foreach ($authors as $a) {
      $a->count = $counts[$a->ID]['cnt'];
    }

    if ($hide_empty)
      $authors = array_filter($authors, 'bang_fs_filter_count');
    if ($sort_count)
      usort($authors, 'bang_fs_cmp_count');
  }

  $authors = apply_filters('bang_fs_authors', $authors);

  if ($orderby == 'shuffle') shuffle($authors);

  //  write the widget
  $selected_author = sanitize_text_field($_REQUEST['author']);

  if (!empty($authors)) {
    echo $before_widget.$before_title.esc_html($title).$after_title;
    echo "<div class='fs-out'><ul class='fs fs-authors fs-style-$style'>";
    foreach ($authors as $author) {
      $link = esc_attr(bang_fs_set_facet_url('author', $author->ID));
      $selected = ($author->ID == $selected_author) ? " class='selected'" : '';

      $name = '';
      if ($style == 'avatar' || $style == 'name_avatar')
        $name = $name.get_avatar($author->user_email, $avatar_size);
      if ($style == 'name' || $style == 'name_avatar')
        $name = $name."<span class='author-name'>{$author->display_name}</span>";
      if ($show_count)
        $name = bang_fs_facet_name_with_count($name, $author->count);

      echo "<li><a rel='nofollow' href='$link' $selected>$name</a></li>";
    }
    echo "</ul></div>".$after_widget;
  }
}

function bang_fs_author_orders() {
  $orders = array(
    'count' => 'Result count',
    'az' => 'Name A-Z',
    'date' => 'Most recent',
    //'shuffle' => 'Shuffle',
  );
  return $orders;
}

