<?php

/**
 * @file
 * Creates sample content for theme testing.
 *
 * Run via: ddev drush php:script /var/www/html/scripts/php/seed-content.php
 *
 * Creates enough articles to exercise the pager (>10) and one basic page.
 * Idempotent — each node is checked individually by title so partial runs
 * are repaired on re-run.
 */

use Drupal\node\Entity\Node;

$marker = 'FDIC Theme Dev';

/**
 * Creates a node if one with the given title does not already exist.
 */
function _fdic_seed_ensure_node(string $type, string $title, string $body): void {
  $existing = \Drupal::entityTypeManager()
    ->getStorage('node')
    ->loadByProperties(['title' => $title]);

  if (!empty($existing)) {
    return;
  }

  Node::create([
    'type'   => $type,
    'title'  => $title,
    'body'   => [
      'value'  => $body,
      'format' => 'basic_html',
    ],
    'status' => 1,
    'uid'    => 1,
  ])->save();
}

// --- Articles (12 nodes so the default 10-per-page pager activates) ---

$article_bodies = [
  'The Federal Deposit Insurance Corporation insures deposits at member banks and thrift institutions. This article covers how deposit insurance works and why it matters for consumers.',
  'Bank examinations are a core FDIC function. Examiners assess a bank\'s financial condition, management practices, and compliance with consumer protection laws.',
  'The FDIC publishes quarterly banking profiles that summarize the financial results of all insured institutions. These reports track industry trends in lending, asset quality, and earnings.',
  'Community banks serve a vital role in local economies. They typically focus on relationship lending and have deep knowledge of their markets.',
  'When a bank fails, the FDIC steps in as receiver to protect insured depositors. The resolution process aims to minimize disruption and preserve asset value.',
  'The Dodd-Frank Act expanded the FDIC\'s authority and raised the standard deposit insurance limit to $250,000 per depositor, per institution, per ownership category.',
  'Risk-based deposit insurance premiums mean that banks taking greater risks pay higher assessments. This creates incentives for prudent risk management.',
  'The FDIC maintains the Deposit Insurance Fund through premiums assessed on insured institutions. The fund target ratio is set by statute at 1.35 percent of insured deposits.',
  'Consumer compliance examinations evaluate whether banks follow laws like the Truth in Lending Act, Equal Credit Opportunity Act, and Fair Housing Act.',
  'Digital banking has transformed how consumers interact with financial institutions. The FDIC monitors technology risks including cybersecurity, third-party dependencies, and operational resilience.',
  'Bank merger applications are reviewed for competitive effects, financial and managerial resources, community reinvestment needs, and anti-money-laundering compliance.',
  'The FDIC\'s economic research division publishes working papers on topics ranging from bank lending patterns to systemic risk measurement and financial stability indicators.',
];

for ($i = 0; $i < count($article_bodies); $i++) {
  $num = $i + 1;
  _fdic_seed_ensure_node('article', "$marker — Article $num", '<p>' . $article_bodies[$i] . '</p>');
}

// --- Basic page ---

_fdic_seed_ensure_node('page', "$marker — About This Site",
  '<p>This is a disposable Drupal site for developing and testing the FDIC theme. It was created by <code>scripts/bootstrap.sh</code>.</p>'
  . '<h2>What to test</h2>'
  . '<ul>'
  . '<li>Global header — menu rendering and search</li>'
  . '<li>Status messages — create and save content to trigger success alerts</li>'
  . '<li>Pager — the article listing should have multiple pages</li>'
  . '<li>Form elements — edit a node to see fd-input, fd-textarea, fd-selector</li>'
  . '<li>Skip link — press Tab on any page</li>'
  . '<li>Progressive enhancement — disable JavaScript and verify forms still work</li>'
  . '</ul>'
);
