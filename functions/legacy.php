<?php

/*
	Version 1.1
	Temporary patch to cover renamed functions and hooks
	Logs the source of all calls to make them easier to fix
*/

function bang_fs_legacy() {
	if (!BANG_FS_DEBUG) return;
	$backtrace = debug_backtrace();

	$legacy = (object) $backtrace[1];
	$caller = (object) $backtrace[2];

	$function = $legacy->function;
	$file = $caller->file;
	$file_parts = explode('/', $file);
	if (count($file_parts > 4)) {
		$file_parts = array_slice($file_parts, count($file_parts) - 4);
		$file = '.../'.implode('/', $file_parts);
	}
	$line = $caller->line;
	$from = "$file:$line";

	do_action('log', 'fs: Legacy call to %s', $function, $from);
}



// primary functions

/*
function fs_init($args = array(), $defer = false) {
	bang_fs_legacy();
	bang_fs_init($args, $defer);
}
*/

function bang_faceted_search__init($args = array(), $defer = false) {
	bang_fs_legacy();
	bang_fs_init($args, $defer);
}

function bang_faceted_search_count() {
	bang_fs_legacy();
	return bang_fs_count();
}

function bang_has_facets() {
	bang_fs_legacy();
	return bang_fs_has_facets();
}


// widgets

function fs_author_facet_widget($args, $instance) {
	bang_fs_legacy();
	bang_fs_author_facet_widget($args, $instance);
}

function fs_date_facet_widget($args, $instance) {
	bang_fs_legacy();
	bang_fs_date_facet_widget($args, $instance);
}

function fs_field_facet_widget($args, $instance) {
	bang_fs_legacy();
	bang_fs_field_facet_widget($args, $instance);
}

function fs_post_type_facet_widget($args, $instance) {
	bang_fs_legacy();
	bang_fs_post_type_facet_widget($args, $instance);
}

function fs_search_widget($args, $instance) {
	bang_fs_legacy();
	bang_fs_search_widget($args, $instance);
}

function fs_taxonomy_facet_widget($args, $instance) {
	bang_fs_legacy();
	bang_fs_taxonomy_facet_widget($args, $instance);
}

function the_taxonomy_link_widget($args, $instance) {
	bang_fs_legacy();
	bang_fs_taxonomy_link_widget($args, $instance);
}

function fs_year_facet_widget($args, $instance) {
	bang_fs_legacy();
	bang_fs_year_facet_widget($args, $instance);
}


// functions

function bang_set_facet_url($key, $value, $basepath = false, $remove = false) {
	bang_fs_legacy();
	return bang_fs_set_facet_url($key, $value, $basepath, $remove);
}

function bang_remove_facet_url($key, $value, $basepath = false, $remove = false) {
	bang_fs_legacy();
	return bang_fs_remove_facet_url($key, $value, $basepath, $remove);
}

function bang_write_facet_feedback($args = array()) {
	bang_fs_legacy();
	return bang_fs_write_feedback($args);
}

function bang_first_page_url($args = null, $basepath = false, $pagesize = 0) {
	bang_fs_legacy();
	return bang_fs_first_page_url($args, $basepath, $pagesize);
}

function bang_prev_page_url($args = null, $basepath = false, $pagesize = 0) {
	bang_fs_legacy();
	return bang_fs_prev_page_url($args, $basepath, $pagesize);
}

function bang_next_page_url($args = null, $basepath = false, $pagesize = 0) {
	bang_fs_legacy();
	return bang_fs_next_page_url($args, $basepath, $pagesize);
}

function bang_last_page_url($args = null, $basepath = false, $pagesize = 0) {
	bang_fs_legacy();
	return bang_fs_last_page_url($args, $basepath, $pagesize);
}
