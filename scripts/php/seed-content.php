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
  <section id="news" class="fdicnet-news fdic-composition-section" aria-labelledby="fdicnet-news-title">
    <div class="fdic-composition-section__inner fdic-composition-feature-rail">
      <div class="fdicnet-news__feature fdic-composition-feature-item">
        <h2 id="fdicnet-news-title">Featured Story</h2>
        <div class="fdic-composition-story">
          <div class="fdic-composition-story__media">
            <img src="/themes/custom/fdic/assets/fdicnet-featured-story.png" alt="Travis Hill speaking at an FDIC hearing.">
          </div>
          <div class="fdic-composition-story__body">
              <div class="fdic-composition-copy-stack">
                <div class="fdic-composition-title-stack">
                  <h3>
                    <fd-link href="/node" size="h3">Travis Hill Sworn in as the 23rd FDIC Chairman</fd-link>
                  </h3>
                  <p class="fdic-composition-meta">January 13, 2026 | FDICNews | In Memoriam</p>
                </div>
                <p>On Tuesday, January 13, 2026, Travis Hill was sworn in as the 23rd Chairman of the Federal Deposit Insurance Corporation (FDIC). Chairman Hill has served as Acting Chairman of the FDIC Board since January 20, 2025, and previously as Vice Chairman since January 5, 2023. Chairman Hill was nominated by President Trump on September 30, 2025, for a term of five years and confirmed by the Senate on December 18, 2025.</p>
            </div>
          </div>
        </div>

        <div class="fdicnet-latest">
          <h2>Latest News</h2>
          <div class="fdic-composition-copy-stack">
            <div class="fdic-composition-title-stack">
              <h3>
                <fd-link href="/node" size="h3">FDIC Launches the Large Bank Ready Reserve, a New Cross-Divisional Readiness Initiative</fd-link>
              </h3>
              <p class="fdic-composition-meta">January 29, 2026 | FDICNews</p>
            </div>
            <p>Chairman Travis Hill spoke at the Single Resolution Mechanism&apos;s 10th Anniversary Conference in October, &amp; discussed reforms to enhance resolution preparedness.</p>
          </div>
          <p class="fdicnet-more">
            <fd-link href="/node" size="md">
              More News
              <fd-icon slot="icon-end" name="caret-right" aria-hidden="true"></fd-icon>
            </fd-link>
          </p>
        </div>
      </div>

      <aside class="fdicnet-news__messages" aria-labelledby="fdicnet-messages-title">
        <h2 id="fdicnet-messages-title">Global Messages</h2>
        <ul class="fdicnet-message-list">
          <li class="fdic-composition-title-stack"><h3><fd-link href="/node" size="md">Occupant Emergency Plan March 2026 Training: Register Today</fd-link></h3><p class="fdic-composition-meta">February 12, 2026 | FDIC-Wide</p></li>
          <li class="fdic-composition-title-stack"><h3><fd-link href="/node" size="md">Update on Telework Agreements</fd-link></h3><p class="fdic-composition-meta">February 11, 2026 | FDIC-Wide</p></li>
          <li class="fdic-composition-title-stack"><h3><fd-link href="/node" size="md">Walk for Peace</fd-link></h3><p class="fdic-composition-meta">February 8, 2026 | FDIC-Wide</p></li>
          <li class="fdic-composition-title-stack"><h3><fd-link href="/node" size="md">Headquarters Weather Advisory for Friday, February 6, 2026</fd-link></h3><p class="fdic-composition-meta">February 6, 2026 | FDIC-Wide</p></li>
          <li class="fdic-composition-title-stack"><h3><fd-link href="/node" size="md">FDIC Headquarters Operating Status for Wednesday, February 4, 2026</fd-link></h3><p class="fdic-composition-meta">February 4, 2026 | FDIC-Wide</p></li>
          <li class="fdic-composition-title-stack"><h3><fd-link href="/node" size="md">2026 Mandatory and Required Training</fd-link></h3><p class="fdic-composition-meta">February 2, 2026 | FDIC-Wide</p></li>
        </ul>
        <p class="fdicnet-more">
          <fd-link href="/node" size="md">
            More Messages
            <fd-icon slot="icon-end" name="caret-right" aria-hidden="true"></fd-icon>
          </fd-link>
        </p>
      </aside>
    </div>
  </section>

  <section id="benefits" class="fdicnet-featured-links fdic-composition-section fdic-composition-section--highlight" aria-labelledby="fdicnet-featured-links-title">
    <div class="fdic-composition-section__inner">
      <h2 id="fdicnet-featured-links-title">Featured Links</h2>
      <fd-tile-list columns="3" tone="cool" label="Featured Links">
        <fd-tile
          title="Performance Management"
          href="/node"
          description="Employee performance management program"
          icon-name="speedometer"
        ></fd-tile>
        <fd-tile
          title="Divisions &amp; Offices"
          href="/node"
          description="Browse all FDIC divisions and offices"
          icon-name="tree-view"
        ></fd-tile>
        <fd-tile
          title="Approved Directives"
          href="/node"
          description="View official, current FDIC directives and policy issuances"
          icon-name="check-circle"
        ></fd-tile>
        <fd-tile
          title="RD Memos"
          href="/node"
          description="Access memoranda issued by Regional Directors"
          icon-name="file-text"
        ></fd-tile>
        <fd-tile
          title="Travel &amp; Expense"
          href="/node"
          description="Submit and manage travel authorizations and expense reimbursements"
          icon-name="airplane-tilt"
        ></fd-tile>
        <fd-tile
          title="Cafeteria Menus"
          href="/node"
          description="Food and beverage choices for a better work day"
          icon-name="fork-knife"
        ></fd-tile>
      </fd-tile-list>
    </div>
  </section>

  <section id="services" class="fdicnet-link-categories fdic-composition-section" aria-label="Employee utility links">
    <div class="fdic-composition-section__inner fdic-composition-link-columns">
      <div class="fdicnet-link-category fdic-composition-link-column">
        <h2 class="fdic-composition-link-column__title">Corporate Applications</h2>
        <ul class="fdic-composition-link-column__list">
          <li><fd-link href="/node" size="md">GovTA</fd-link></li>
          <li><fd-link href="/node" size="md">CHRIS</fd-link></li>
          <li><fd-link href="/node" size="md">ARCS</fd-link></li>
          <li><fd-link href="/node" size="md">Employee Center</fd-link></li>
          <li><fd-link href="/node" size="md">Federal Employee Health Benefits (FEHB)</fd-link></li>
        </ul>
      </div>
      <div id="knowledge" class="fdicnet-link-category fdic-composition-link-column">
        <h2 class="fdic-composition-link-column__title">Examiner Tools</h2>
        <ul class="fdic-composition-link-column__list">
          <li><fd-link href="/node" size="md">ViSION</fd-link></li>
          <li><fd-link href="/node" size="md">Risk Examination Support</fd-link></li>
          <li><fd-link href="/node" size="md">Examinations Resources</fd-link></li>
          <li><fd-link href="/node" size="md">Compliance Examination Manual</fd-link></li>
          <li><fd-link href="/node" size="md">Community Reinvestment Act</fd-link></li>
        </ul>
      </div>
      <div id="career" class="fdicnet-link-category fdic-composition-link-column">
        <h2 class="fdic-composition-link-column__title">Training &amp; Onboarding</h2>
        <ul class="fdic-composition-link-column__list">
          <li><fd-link href="/node" size="md">Mandatory Training</fd-link></li>
          <li><fd-link href="/node" size="md">Examiner Learning Program</fd-link></li>
          <li><fd-link href="/node" size="md">Professional Learning Account (PLA)</fd-link></li>
          <li><fd-link href="/node" size="md">LinkedIn Learning</fd-link></li>
          <li><fd-link href="/node" size="md">ELX</fd-link></li>
        </ul>
      </div>
    </div>
  </section>

  <section class="fdicnet-events fdic-composition-section fdic-composition-section--warm" aria-labelledby="fdicnet-events-title">
    <div class="fdic-composition-section__inner">
      <h2 id="fdicnet-events-title">Upcoming Events</h2>
      <fd-event-list class="fdicnet-events__list" columns="3" tone="warm" label="Upcoming Events">
        <fd-event month="Feb" day="19" title="Eileen Vidrine on the Human Component of AI" href="/node" data-fdic-event-metadata='["FDIC-Wide", "CIOO, DIT", "Webinar"]'></fd-event>
        <fd-event month="Feb" day="20" title="Planned eFOS+ Outage" href="/node" data-fdic-event-metadata='["FDIC-Wide"]'></fd-event>
        <fd-event month="Feb" day="24" title="Section 508 Document Remediation Session (PPT/Excel)" href="/node" data-fdic-event-metadata='["FDIC-Wide", "Training"]'></fd-event>
      </fd-event-list>
      <p class="fdicnet-more">
        <fd-link href="/node" size="md">
          More Events
          <fd-icon slot="icon-end" name="caret-right" aria-hidden="true"></fd-icon>
        </fd-link>
      </p>
    </div>
  </section>

  <section class="fdicnet-people-social fdic-composition-section" aria-label="Employee spotlight and social updates">
    <div class="fdic-composition-section__inner fdic-composition-dual">
      <article class="fdicnet-spotlight fdic-composition-dual__panel">
        <h2>Employee Spotlight</h2>
        <fd-tile class="fdicnet-person-tile" title="Alex Harrison" description="Digital Media Specialist, Office of Communications" visual-type="avatar"></fd-tile>
        <p>Alex has brought a systematic approach and strategic focus to the FDIC's digital communication infrastructure and content. Through rigorous data analysis of subscription patterns and engagement metrics, he identified inefficiencies and gaps in how the FDIC reaches its audiences.</p>
      </article>

          <article class="fdicnet-social fdic-composition-dual__panel">
            <h2>FDIC on Social</h2>
            <div class="fdicnet-social__content">
              <div class="fdicnet-social__body">
                <div class="fdic-composition-copy-stack fdicnet-social__copy">
                  <p class="fdicnet-social__lede">Read Acting Chairman Travis Hill&apos;s Statement on Executive Order, &quot;Guaranteeing Fair Banking For All Americans.&quot;</p>
                  <p class="fdicnet-social__link"><fd-link href="https://fdic.gov/news/press-releases/2025/statement-acting-chairman-travis-hill-executive-order-titled-guaranteeing" size="md">https://fdic.gov/news/press-releases/2025/statement-acting-chairman-travis-hill-executive-order-titled-guaranteeing</fd-link></p>
                  <p class="fdic-composition-meta">Posted Aug. 8, 2025 on…</p>
                </div>
                <div class="fdicnet-social__platforms" aria-label="Social platforms">
                  <a class="fdicnet-social__platform" href="https://x.com/fdicgov" aria-label="FDIC on X">
                    <fd-icon name="x-logo" aria-hidden="true"></fd-icon>
                  </a>
                  <a class="fdicnet-social__platform" href="https://www.linkedin.com/company/fdic/" aria-label="FDIC on LinkedIn">
                    <fd-icon name="linkedin-logo" aria-hidden="true"></fd-icon>
                  </a>
                </div>
              </div>
              <div class="fdicnet-social-card" aria-hidden="true"><img src="/themes/custom/fdic/assets/fdicnet-social-card.png" alt=""></div>
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
