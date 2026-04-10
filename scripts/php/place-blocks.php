<?php

/**
 * @file
 * Places essential blocks in FDIC theme regions.
 *
 * Run via: ddev drush php:script /var/www/html/scripts/php/place-blocks.php
 *
 * Idempotent — existing blocks are updated to match the expected configuration
 * so partial prior runs are repaired.
 */

use Drupal\block\Entity\Block;

$stale_block_ids = [
  // Older bootstrap runs used this id for the system main block. Remove it so
  // rerunning the current script repairs local sites instead of rendering the
  // main content block twice.
  'fdic_content',
];

foreach ($stale_block_ids as $stale_block_id) {
  $stale_block = Block::load($stale_block_id);
  if ($stale_block) {
    $stale_block->delete();
  }
}

$blocks = [
  [
    'id'     => 'fdic_page_title',
    'plugin' => 'page_title_block',
    'region' => 'content',
    'weight' => -20,
  ],
  [
    'id'     => 'fdic_local_tasks',
    'plugin' => 'local_tasks_block',
    'region' => 'content',
    'weight' => -15,
  ],
  [
    'id'     => 'fdic_messages',
    'plugin' => 'system_messages_block',
    'region' => 'highlighted',
    'weight' => 0,
  ],
  [
    'id'     => 'fdic_main_content',
    'plugin' => 'system_main_block',
    'region' => 'content',
    'weight' => 0,
  ],
  [
    'id'     => 'fdic_breadcrumb',
    'plugin' => 'system_breadcrumb_block',
    'region' => 'breadcrumb',
    'weight' => 0,
  ],
  [
    // The header region must have at least one block for Drupal to render it.
    // region--header.html.twig attaches fdic/global-header and renders the
    // <fd-global-header> component; without a placed block the region is empty
    // and the template never executes.
    'id'     => 'fdic_branding',
    'plugin' => 'system_branding_block',
    'region' => 'header',
    'weight' => 0,
  ],
];

foreach ($blocks as $def) {
  $block = Block::load($def['id']);

  if ($block) {
    // Update existing block to match expected state (repairs partial runs).
    $block->setRegion($def['region']);
    $block->setWeight($def['weight']);
    $block->setStatus(TRUE);
    $block->set('theme', 'fdic');
    $block->save();
    continue;
  }

  Block::create([
    'id'     => $def['id'],
    'plugin' => $def['plugin'],
    'region' => $def['region'],
    'theme'  => 'fdic',
    'weight' => $def['weight'],
    'status' => TRUE,
    'settings' => [
      'label_display' => FALSE,
    ],
  ])->save();
}
