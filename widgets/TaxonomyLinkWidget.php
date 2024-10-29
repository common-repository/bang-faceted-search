<?php

class TaxonomyLinkWidgetDisabled extends WP_widget {
  var $brand;
  var $vary;
  var $default_title;

  function __construct($code = 'tax_link_widget', $title = 'Taxonomy Links',
      $description = 'Link into a taxonomy search with a drop-down menu or list of links') {
    parent::__construct($code, $title, array(
      'classname' => $code,
      'description' => $description
    ));
    $this->brand = 'bang';
    $this->vary = true;
    $this->default_title = '';
  }

  function TaxonomyLinkWidget($code = null, $title = null, $description = null) {
    return self::__construct($code, $title, $description);
  }

  function form($instance) {
    $title = empty($instance['title']) ? '' : $instance['title'];
    $titleID =   $this->get_field_id('title');
    $titleName = $this->get_field_name('title');

    echo "<span class='bang-indicator' data-brand='$this->brand'></span>";

    echo "<label for='$titleID'>Title</label>";
    echo "<input name='$titleName' id='$titleID' value='$title' class='widefat' placeholder='$this->default_title'/>";

    if ($this->vary) {
      $taxonomy = $instance['taxonomy'];
      $taxonomyID = $this->get_field_id('taxonomy');
      $taxonomyName = $this->get_field_name('taxonomy');

      $taxonomies = get_taxonomies(array(), 'objects');
      echo "<label for='$taxonomyID'>Taxonomy</label>";
      echo "<select id='$taxonomyID' name='$taxonomyName' class='widefat'>";
      foreach ($taxonomies as $name => $tax) {
        if (empty($tax->object_type)) continue;
        echo "<option value='{$tax->name}'";
        if ($tax->name == $taxonomy)
          echo " selected";
        echo ">{$tax->label}</option>";
      }
      echo "</select>\n";

      $specific = (boolean) $instance['specific'];
      $specificID = $this->get_field_id('specific');
      $specificName = $this->get_field_name('specific');

      $checked = $specific ? 'checked' : '';
      echo "<p><label for='$specificID'>";
      echo "<input type='checkbox' name='$specificName' id='$specificID' $checked/>";
      echo " Show terms for the current page</label></p>";

      $styles = array('menu', 'list');
      $style = $instance['style'];
      if (!in_array($style, $styles)) $style = 'menu';
      $styleName = $this->get_field_name('style');
      $style_menuID = $this->get_field_id('style_menu');
      $style_listID = $this->get_field_id('style_list');

      echo "<label for='$styleID'>Style:</label>";
      $checked = ($style == 'menu') ? 'checked' : '';
      echo "<div><label for='$style_menuID'><input type='radio' name='$styleName' id='$style_menuID' value='menu' $checked/> Drop down menu</label></div>";
      $checked = ($style == 'list') ? 'checked' : '';
      echo "<div><label for='$style_listID'><input type='radio' name='$styleName' id='$style_listID' value='list' $checked/> List of links</label></div>";
    }
  }

  function update($new_instance, $old_instance) {
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    $instance['default_title'] = $this->default_title;
    if ($this->vary) {
      $instance['taxonomy'] = $new_instance['taxonomy'];
      $instance['specific'] = (boolean) $new_instance['specific'];
      $instance['style'] = $new_instance['style'];
    }
    return $instance;
  }

  function widget($args, $instance) {
    bang_fs_taxonomy_link_widget($args, $instance);
  }
}

function bang_fs_taxonomy_link_widget($args, $instance) {
  $args = bang_fs_widget_args($args);
  extract($args, EXTR_SKIP);

  $instance = bang_fs_widget_instance($instance, array(
      'specific' => false,
      'orderby' => 'count',
      'order' => DESC,
  ));
  if (BANG_FS_DEBUG) do_action('log', 'fs: Taxonomy link widget', $instance);

  $tax = $instance['taxonomy'];
  $taxonomy = get_taxonomy($tax);
  $specific = (boolean) $instance['specific'];

  $title = empty($instance['title']) ? $instance['default_title'] : $instance['title'];
  if (empty($title)) $title = $taxonomy->labels->name;
  $style = $instance['style'];
  $placeholder = $instance['placeholder'];
  if (empty($placeholder)) {
    $n = __($taxonomy->labels->singular_name);
    $a = (preg_match('/^[aeiou]|s\z/i', strtolower($n))) ? "an" : "a";
    $placeholder = __("Select $a $n...");
    $placeholder = apply_filters('bang_fs_taxonomy_select_text', $placeholder, $n);
  }

  //  get the terms
  $terms = bang_fs_taxonomy_facet_terms($instance);
  if (empty($terms)) return;

  echo $before_widget;
  echo $before_title.esc_html($title).$after_title;

  if ($style == 'menu') {
    echo "<form class='fs-link-form' action='/' method='get'>";

    $get = bang_fs_get();
    unset($get[$tax]);

    foreach ($get as $key => $value)
      echo "<input type='hidden' name='".esc_attr($key)."' value='".esc_attr($value)."'/>";
    echo "<select class='fs-dropdown' name='".esc_attr($tax)."'>";
    echo "<option value=''>$placeholder</option>";
    foreach ($terms as $term)
      echo "<option value='$term->slug'>$term->name</option>";
    echo "</select>";
    echo "</form>";
  } else {
    echo "<ul class='fs'>";
    foreach ($terms as $term) {
      $link = esc_attr(bang_fs_set_facet_url($tax, $term->slug, '/'));
      echo "<li><a rel='nofollow' href='$link'>$term->name</a></li>";
    }
    echo "</ul>";
  }

  echo $after_widget;
}

