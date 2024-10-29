<?php

//  get the search results
function bang_fs_get_results() {
  $fs_instance = bang_fs_instance();
  return $fs_instance->get_posts();
}

//  hilight search terms
function bang_fs_hilight_search_terms($string) {
  global $s;
  if (empty($s)) return $string;
  
  $keys = explode(" ",$s);
  $keys = array_filter($keys);
  if (empty($keys))
    return $result;
  $result = preg_replace(
    '/('.implode('|', $keys) .')/iu', 
    '<mark>\0</mark>',
    $string);
  //do_action('log', 'fs: Hilighted string', $result);
  return $result;
}