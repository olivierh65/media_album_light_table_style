<?php

/**
 * @file
 */

/**
 * Implements hook_views_data_alter().
 */
function media_album_light_table_style_views_data_alter(array &$data) {
  $data['node_field_data']['album_title_filter'] = [
    'title' => t('Album title (select list)'),
    'filter' => [
      'title' => t('Album title (select list)'),
      'help' => t('Filter by album title using a pre-populated select list.'),
      'field' => 'nid',
      'id' => 'album_title_filter',
    ],
  ];
}
