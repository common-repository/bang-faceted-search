<?php

add_action('widgets_init', 'bang_fs_load_widgets');
function bang_fs_load_widgets() {
	$folder = dirname(BANG_FS_PLUGIN_FILE)."/widgets/*.php";
	//do_action('log', 'Faceted search: load widgets', $folder);
	foreach (glob($folder) as $filename) {
		//do_action('log', 'Load widget', $filename);
		include_once($filename);
		$name = substr($filename, strrpos($filename, "/")+1);
		$name = substr($name, 0, strlen($name) - strlen(".php"));
		//do_action('log', 'Register widget', $name);
		if (class_exists($name))
			register_widget($name);
	}
}

function bang_fs_widget_args($args) {
	global $wp_registered_sidebars;

	$options = bang_fs_options();
	$defaults = isset($options->widget_args) ? $options->widget_args : array();
	if (empty($defaults)) {
		if (is_string($args) && isset($wp_registered_sidebars[$args])) {
			$defaults = $wp_registered_sidebars[$args];
		}
		if (empty($wp_registered_sidebars)) {
			$defaults = array(
									 'name' => sprintf(__('Sidebar %d'), $i ),
									 'id' => "sidebar-$i",
									 'description' => '',
									 'class' => '',
									 'before_widget' => '<li id="%1$s" class="widget %2$s">',
									 'after_widget' => "</li>\n",
									 'before_title' => '<h2 class="widgettitle">',
									 'after_title' => "</h2>\n",
			);
		} else {
			// todo  - select the best sidebar
			$sidebars = array_values($wp_registered_sidebars);
			$sidebar = $sidebars[0];
			$defaults = $sidebar;
		}
	}
	
	if (!is_array($args)) $args = array();
	$args = wp_parse_args($args, $defaults);
	$keys = array('id', 'class', 'before_widget', 'after_widget', 'before_title', 'after_title');
	$out = array();
	foreach ($keys as $key)
		$out[$key] = $args[$key];
	return $out;
}


//  $instance > $defaults > $bang_fs_options->instance
function bang_fs_widget_instance($instance = array(), $defaults = array()) {
	$options = bang_fs_options();
	if (isset($options->instance))
		$defaults = wp_parse_args($defaults, $options->instance);
	$instance = wp_parse_args($instance, $defaults);
	
	$instance['show_count'] = isset($instance['show_count']) ? (boolean) $instance['show_count'] : false;
	$instance['sort_count'] = isset($instance['sort_count']) ? (boolean) $instance['sort_count'] : false;
	$instance['hide_empty'] = isset($instance['hide_empty']) ? (boolean) $instance['hide_empty'] : false;
	
	return $instance;
}


function bang_fs_facet_name_with_count($name, $count) {
	if (!isset($count) || !$count || $count <= 0) $count = 0;
	$out = "$name <span class='fs-count-outer'>(<span class='fs-count'>$count</span>)</span>";
	$out = apply_filters('bang_fs_facet_name_with_count', $out, $name, $count);
	if (empty($out)) $out = "&nbsp;";
	return $out;
}

function bang_fs_show_facets($args = array()) {
	global $bang_fs_show_facets, $bang_fs_override_args;
	$bang_fs_show_facets = true;
	
	wp_enqueue_script('faceted-search');
	wp_enqueue_style('faceted-search');
	wp_enqueue_style( 'dashicons' );

	if (is_array($args) && !empty($args)) {
		$bang_fs_override_args = wp_parse_args((array) $bang_fs_override_args, (array) $args);
	}
}

function bang_fs_facets_visible() {
	global $bang_fs_show_facets;
	if (BANG_FS_DEBUG) do_action('log', 'Visible? show = %s, is fs = %s', $bang_fs_show_facets, is_faceted_search());
	return $bang_fs_show_facets || is_faceted_search();
}