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
    'format' => $options['format'] ?? 'basic_html',
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

$home_body = <<<'HTML'
<div class="fdicnet-home">
  <section id="news" class="fdicnet-section fdicnet-news" aria-labelledby="fdicnet-news-title">
    <div class="fdicnet-section__inner">
      <div class="fdicnet-news__feature">
        <h2 id="fdicnet-news-title">Featured Story</h2>
        <div class="fdicnet-story">
          <div class="fdicnet-story__visual" role="img" aria-label="Illustrated FDIC briefing room scene"></div>
          <div class="fdicnet-story__copy">
            <h3><a href="/node">Travis Hill sworn in as the 23rd FDIC Chairman</a></h3>
            <p class="fdicnet-meta">January 13, 2026 | FDICNews | Leadership</p>
            <p>Chairman Hill has served as Acting Chairman of the FDIC Board since January 2025. This sample story demonstrates a feature article treatment for a rendered Drupal snapshot.</p>
          </div>
        </div>

        <div class="fdicnet-latest">
          <h2>Latest News</h2>
          <h3><a href="/node">FDIC launches readiness initiative for cross-divisional teams</a></h3>
          <p class="fdicnet-meta">January 29, 2026 | FDICNews</p>
          <p>Teams across the agency are preparing updated playbooks for resolution readiness, stakeholder communications, and employee support.</p>
          <p class="fdicnet-more"><a href="/node">More News</a></p>
        </div>
      </div>

      <aside class="fdicnet-news__messages" aria-labelledby="fdicnet-messages-title">
        <h2 id="fdicnet-messages-title">Global Messages</h2>
        <ul class="fdicnet-message-list">
          <li><a href="/node">Occupant Emergency Plan March 2026 Training: Register Today</a><span>February 12, 2026 | FDIC-Wide</span></li>
          <li><a href="/node">Update on Telework Agreements</a><span>February 11, 2026 | FDIC-Wide</span></li>
          <li><a href="/node">Walk for Peace</a><span>February 8, 2026 | FDIC-Wide</span></li>
          <li><a href="/node">Headquarters Theater Advisory for Friday, February 6, 2026</a><span>February 6, 2026 | FDIC-Wide</span></li>
          <li><a href="/node">FDIC Headquarters Operating Status for Wednesday, February 4, 2026</a><span>February 4, 2026 | FDIC-Wide</span></li>
          <li><a href="/node">2026 Mandatory and Required Training</a><span>February 2, 2026 | FDIC-Wide</span></li>
        </ul>
        <p class="fdicnet-more"><a href="/node">More Messages</a></p>
      </aside>
    </div>
  </section>

  <section id="benefits" class="fdicnet-section fdicnet-featured-links" aria-labelledby="fdicnet-featured-links-title">
    <div class="fdicnet-section__inner">
      <h2 id="fdicnet-featured-links-title">Featured Links</h2>
      <fd-tile-list class="fdicnet-featured-links__list" columns="3" tone="cool" label="Featured Links">
        <fd-tile icon-name="speedometer" title="Performance Management" href="/node" description="Employee performance management program"></fd-tile>
        <fd-tile icon-name="tree-view" title="Divisions &amp; Offices" href="/node" description="Browse all FDIC divisions and offices"></fd-tile>
        <fd-tile icon-name="check-circle" title="Approved Directives" href="/node" description="View official, current FDIC directives and policy issuances"></fd-tile>
        <fd-tile icon-name="file-text" title="RD Memos" href="/node" description="Access memoranda issued by Regional Directors"></fd-tile>
        <fd-tile icon-name="airplane-tilt" title="Travel &amp; Expense" href="/node" description="Submit and manage travel authorizations and expense reimbursements"></fd-tile>
        <fd-tile icon-name="fork-knife" title="Cafeteria Menus" href="/node" description="Food and beverage choices for a better work day"></fd-tile>
      </fd-tile-list>
    </div>
  </section>

  <section id="services" class="fdicnet-section fdicnet-link-categories" aria-label="Employee utility links">
    <div class="fdicnet-section__inner">
      <div class="fdicnet-link-category">
        <h2>Corporate Applications</h2>
        <ul>
          <li><a href="/node">GovTA</a></li>
          <li><a href="/node">CHRIS</a></li>
          <li><a href="/node">ARCS</a></li>
          <li><a href="/node">Employee Center</a></li>
          <li><a href="/node">Federal Employee Health Benefits (FEHB)</a></li>
        </ul>
      </div>
      <div id="knowledge" class="fdicnet-link-category">
        <h2>Examiner Tools</h2>
        <ul>
          <li><a href="/node">ViSION</a></li>
          <li><a href="/node">Risk Examination Support</a></li>
          <li><a href="/node">Examinations Resources</a></li>
          <li><a href="/node">Compliance Examination Manual</a></li>
          <li><a href="/node">Community Reinvestment Act</a></li>
        </ul>
      </div>
      <div id="career" class="fdicnet-link-category">
        <h2>Training &amp; Onboarding</h2>
        <ul>
          <li><a href="/node">Mandatory Training</a></li>
          <li><a href="/node">Examiner Learning Program</a></li>
          <li><a href="/node">Professional Learning Account (PLA)</a></li>
          <li><a href="/node">LinkedIn Learning</a></li>
          <li><a href="/node">ELX</a></li>
        </ul>
      </div>
    </div>
  </section>

  <section class="fdicnet-section fdicnet-events" aria-labelledby="fdicnet-events-title">
    <div class="fdicnet-section__inner">
      <h2 id="fdicnet-events-title">Upcoming Events</h2>
      <fd-event-list class="fdicnet-events__list" columns="3" tone="warm" label="Upcoming Events">
        <fd-event month="Feb" day="19" title="Eileen Vidrine on the Human Component of AI" href="/node"></fd-event>
        <fd-event month="Feb" day="20" title="Planned eFOS+ Outage" href="/node"></fd-event>
        <fd-event month="Feb" day="24" title="Section 508 Document Remediation Session (PPT/Excel)" href="/node"></fd-event>
      </fd-event-list>
      <p class="fdicnet-more"><a href="/node">More Events</a></p>
    </div>
  </section>

  <section class="fdicnet-section fdicnet-people-social" aria-label="Employee spotlight and social updates">
    <div class="fdicnet-section__inner">
      <article class="fdicnet-spotlight">
        <h2>Employee Spotlight</h2>
        <div class="fdicnet-person">
          <div class="fdicnet-avatar" aria-hidden="true">AH</div>
          <div><h3>Alex Harrison</h3><p>Digital Media Specialist, Office of Communications</p></div>
        </div>
        <p>Alex has brought a systematic approach and strategic focus to the FDIC's digital communication infrastructure and content. Through rigorous data analysis of subscription patterns and engagement metrics, he identified inefficiencies and gaps in how the FDIC reaches its audiences.</p>
      </article>

      <article class="fdicnet-social">
        <h2>FDIC on Social</h2>
        <div class="fdicnet-social__content">
          <div>
            <p>Read Acting Chairman Travis Hill's statement on Executive Order, "Guaranteeing Fair Banking For All Americans."</p>
            <p><a href="https://www.fdic.gov/news/press-releases/2025">fdic.gov/news/press-releases/2025</a></p>
            <p class="fdicnet-meta">Posted Aug. 8, 2025 on Facebook and YouTube</p>
          </div>
          <div class="fdicnet-social-card" aria-hidden="true"><span>Guaranteeing Fair Banking For All Americans</span><strong>FDIC</strong></div>
        </div>
      </article>
    </div>
  </section>
</div>
HTML;

$home = _fdic_seed_ensure_node('page', "$marker: Home", $home_body, [
  'promoted' => FALSE,
  'sticky' => TRUE,
  'format' => 'full_html',
]);
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
