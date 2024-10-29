<?php

/*
include 'bang-debug.php';

add_action('template_redirect', 'bang_fs_template_redirect', 1);
function bang_fs_template_redirect () {
  //debug_print_backtrace(); die;
  //echo "template redirect"; die;
  //bang_debug_hooks('template_redirect'); die;

  //  catch incorrect searches
  if (!empty($_GET)) {
    $uri = bang_fs_local_uri();
    //$uri = trim($uri, '/');
    if (!empty($uri)) {
      $page = get_page_by_path($uri);
      if (!empty($page)) {
        if (BANG_FS_DEBUG >= 2)
          do_action('log', 'fs: Routing to page at %s', $uri);
        global $wp;
        query_posts("post_type=page&page_id={$page->ID}");
        $wp->register_globals();
        return;
      }
    }
  }
}

add_filter('template_include', 'bang_fs_template_include', 101);
function bang_fs_template_include ($template) {
  if (BANG_FS_DEBUG >= 2) {
    $type = array();
    if (is_404())             $type[] = "404";
    if (is_search())          $type[] = "search";
    if (is_tax())             $type[] = "tax";
    if (is_front_page())      $type[] = "front page";
    if (is_home())            $type[] = "home";
    if (is_attachment())      $type[] = "attachment";
    if (is_single())          $type[] = "single";
    if (is_page())            $type[] = "page";
    if (is_category())        $type[] = "category";
    if (is_tag())             $type[] = "tag";
    if (is_author())          $type[] = "author";
    if (is_date())            $type[] = "date";
    if (is_archive())         $type[] = "archive";
    if (is_comments_popup())  $type[] = "comments popup";
    if (is_paged())           $type[] = "paged";
    if (is_preview())         $type[] = "preview";

    $type = implode(", ", $type);
    //do_action('log', 'fs: Routing to template: %s - %s', $template, $type);
  }
  return $template;
}
*/
