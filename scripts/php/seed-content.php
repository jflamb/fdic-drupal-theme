<?php

/**
 * @file
 * Creates curated example content for the FDIC theme DDEV site.
 *
 * Run via: ddev drush php:script /var/www/html/scripts/php/seed-content.php
 *
 * The script is idempotent by title. Re-running it updates existing seeded
 * nodes so partial or older seed runs are repaired where practical.
 */

use Drupal\node\Entity\Node;
use Drupal\user\UserInterface;

$marker = 'FDIC Theme Example';

/**
 * Returns a valid node owner, preferring uid 1 only when it exists.
 */
function _fdic_seed_owner_id(): int {
  $user_storage = \Drupal::entityTypeManager()->getStorage('user');
  $admin = $user_storage->load(1);

  if ($admin instanceof UserInterface) {
    return 1;
  }

  $uids = $user_storage->getQuery()
    ->accessCheck(FALSE)
    ->condition('status', 1)
    ->sort('uid')
    ->range(0, 1)
    ->execute();

  if (!empty($uids)) {
    return (int) reset($uids);
  }

  return 0;
}

/**
 * Creates or updates a published node with a basic_html body.
 */
function _fdic_seed_ensure_node(string $type, string $title, string $body, array $options = []): Node {
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  $matches = $storage->loadByProperties([
    'type' => $type,
    'title' => $title,
  ]);

  $node = !empty($matches) ? reset($matches) : Node::create([
    'type' => $type,
    'title' => $title,
  ]);

  if (!$node instanceof Node) {
    $node = Node::create([
      'type' => $type,
      'title' => $title,
    ]);
  }

  $node->set('body', [
    'value' => $body,
    'format' => 'basic_html',
  ]);
  $node->setOwnerId(_fdic_seed_owner_id());
  $node->setPublished(TRUE);
  $node->setPromoted((bool) ($options['promoted'] ?? ($type === 'article')));
  $node->setSticky((bool) ($options['sticky'] ?? FALSE));
  $node->save();

  return $node;
}

/**
 * Sets the configured front page to a node.
 */
function _fdic_seed_set_front_page(Node $node): void {
  \Drupal::configFactory()
    ->getEditable('system.site')
    ->set('page.front', '/node/' . $node->id())
    ->save();
}

$home = _fdic_seed_ensure_node('page', "$marker: Home",
  '<p><strong>FDIC Theme Dev</strong> is a disposable Drupal site for developing and reviewing the FDIC Drupal theme against real Drupal output.</p>'
  . '<p><a href="/node">Browse the article listing</a> or review <a href="/node?page=1">the second listing page</a> and node pages rendered by the standard profile.</p>'
  . '<h2>Depositor confidence starts with clear service design</h2>'
  . '<p>The example content uses standard Drupal page and article nodes, body fields, links, headings, ordered and unordered lists, and default node metadata so theme changes can be reviewed in context.</p>'
  . '<h3>What this page covers</h3>'
  . '<ul>'
  . '<li>Page title and body field rendering</li>'
  . '<li>Breadcrumb and system message block placement</li>'
  . '<li>FDIC Design System CSS, JavaScript, and web component assets</li>'
  . '<li>Progressive enhancement fallbacks for Drupal markup</li>'
  . '</ul>'
  . '<h3>Common user tasks</h3>'
  . '<ol>'
  . '<li>Confirm deposit insurance coverage.</li>'
  . '<li>Find supervision and consumer protection resources.</li>'
  . '<li>Read research, reports, and public updates.</li>'
  . '</ol>',
  ['promoted' => FALSE, 'sticky' => TRUE]
);
_fdic_seed_set_front_page($home);

_fdic_seed_ensure_node('page', "$marker: About This Site",
  '<p>This local site is created by <code>scripts/bootstrap.sh</code>. It is intentionally disposable and can be rebuilt from the theme repository at any time.</p>'
  . '<h2>Integration checks</h2>'
  . '<p>The DDEV site verifies that Drupal can discover the theme, enable it as the default theme, place blocks, render node pages, and serve FDIC Design System assets from <code>node_modules</code>.</p>'
  . '<h3>Markup covered</h3>'
  . '<ul>'
  . '<li>Global header, footer, breadcrumb, highlighted, and content regions</li>'
  . '<li>Status message block placement for success, warning, and error messages</li>'
  . '<li>Article listings with multiple pages of content</li>'
  . '<li>Native Drupal forms and field wrappers used as fallbacks</li>'
  . '</ul>'
  . '<p>Use <a href="/node">the article listing</a> to review pager behavior and promoted content cards.</p>',
  ['promoted' => FALSE]
);

_fdic_seed_ensure_node('page', "$marker: Consumer Resources",
  '<p>Consumers need direct paths to practical banking information. This page provides copy patterns for links, lists, and section headings.</p>'
  . '<h2>Start with the basics</h2>'
  . '<ul>'
  . '<li><a href="https://www.fdic.gov/resources/deposit-insurance/">Deposit insurance information</a></li>'
  . '<li><a href="https://www.fdic.gov/resources/consumers/">Consumer assistance resources</a></li>'
  . '<li><a href="https://banks.data.fdic.gov/bankfind-suite/bankfind">BankFind Suite</a></li>'
  . '</ul>'
  . '<h2>Before opening an account</h2>'
  . '<ol>'
  . '<li>Confirm the institution is FDIC-insured.</li>'
  . '<li>Review account fees, access, and disclosures.</li>'
  . '<li>Keep records for deposits, withdrawals, and account changes.</li>'
  . '</ol>',
  ['promoted' => FALSE]
);

$article_bodies = [
  'How deposit insurance protects customers when an insured bank fails.',
  'What community banks contribute to local lending and economic resilience.',
  'How risk management supports safe and sound banking operations.',
  'Why consumer compliance examinations matter for fair access to credit.',
  'How quarterly banking data helps identify trends across insured institutions.',
  'What happens during a bank resolution and how insured depositors are protected.',
  'How cybersecurity, vendors, and operational resilience affect digital banking.',
  'Why transparent disclosures help consumers compare account options.',
  'How merger applications are reviewed for competition and community needs.',
  'What the Deposit Insurance Fund does and how assessments support it.',
  'How economic research informs supervision, policy, and public understanding.',
  'What examiners review when evaluating bank management and controls.',
  'How mobile banking changes expectations for service, security, and access.',
  'Why accessible content matters for public banking resources.',
  'How data publications support journalists, researchers, and local leaders.',
  'What consumers should know before responding to unexpected bank messages.',
];

foreach ($article_bodies as $index => $summary) {
  $number = $index + 1;
  _fdic_seed_ensure_node('article', sprintf('%s: Banking Update %02d', $marker, $number),
    '<p>' . $summary . '</p>'
    . '<h2>Key points</h2>'
    . '<ul>'
    . '<li>Plain language helps people understand decisions and next steps.</li>'
    . '<li>Consistent structure makes long public information easier to scan.</li>'
    . '<li>Drupal default markup should remain usable before JavaScript loads.</li>'
    . '</ul>'
    . '<h3>Details for reviewers</h3>'
    . '<p>This article exercises headings, body text, links to <a href="/node">the listing page</a>, promoted article rendering, and default node metadata.</p>',
    [
      'promoted' => TRUE,
      'sticky' => $number === 1,
    ]
  );
}
