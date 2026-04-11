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
          <div class="fdicnet-story__visual">
            <img class="fdicnet-story__image" src="/themes/custom/fdic/assets/fdicnet-featured-story.png" alt="Travis Hill speaking at an FDIC hearing.">
          </div>
          <div class="fdicnet-story__copy">
            <h3><a href="/node">Travis Hill Sworn in as the 23rd FDIC Chairman</a></h3>
            <p class="fdicnet-meta">January 13, 2026 | FDICNews | In Memoriam</p>
            <p>On Tuesday, January 13, 2026, Travis Hill was sworn in as the 23rd Chairman of the Federal Deposit Insurance Corporation (FDIC). Chairman Hill has served as Acting Chairman of the FDIC Board since January 20, 2025, and previously as Vice Chairman since January 5, 2023. Chairman Hill was nominated by President Trump on September 30, 2025, for a term of five years and confirmed by the Senate on December 18, 2025.</p>
          </div>
        </div>

        <div class="fdicnet-latest">
          <h2>Latest News</h2>
          <h3><a href="/node">FDIC Launches the Large Bank Ready Reserve, a New Cross-Divisional Readiness Initiative</a></h3>
          <p class="fdicnet-meta">January 29, 2026 | FDICNews</p>
          <p>Chairman Travis Hill spoke at the Single Resolution Mechanism&apos;s 10th Anniversary Conference in October, &amp; discussed reforms to enhance resolution preparedness.</p>
          <p class="fdicnet-more"><a href="/node">More News</a></p>
        </div>
      </div>

      <aside class="fdicnet-news__messages" aria-labelledby="fdicnet-messages-title">
        <h2 id="fdicnet-messages-title">Global Messages</h2>
        <ul class="fdicnet-message-list">
          <li><a href="/node">Occupant Emergency Plan March 2026 Training: Register Today</a><span>February 12, 2026 | FDIC-Wide</span></li>
          <li><a href="/node">Update on Telework Agreements</a><span>February 11, 2026 | FDIC-Wide</span></li>
          <li><a href="/node">Walk for Peace</a><span>February 8, 2026 | FDIC-Wide</span></li>
          <li><a href="/node">Headquarters Weather Advisory for Friday, February 6, 2026</a><span>February 6, 2026 | FDIC-Wide</span></li>
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
      <div class="fdicnet-featured-links__list" role="list" aria-label="Featured Links">
        <article class="fdicnet-featured-link" role="listitem">
          <span class="fdicnet-featured-link__icon" aria-hidden="true"><svg viewBox="0 0 256 256"><path d="M114.34,154.34l96-96a8,8,0,0,1,11.32,11.32l-96,96a8,8,0,0,1-11.32-11.32ZM128,88a63.9,63.9,0,0,1,20.44,3.33,8,8,0,1,0,5.11-15.16A80,80,0,0,0,48.49,160.88,8,8,0,0,0,56.43,168c.29,0,.59,0,.89-.05a8,8,0,0,0,7.07-8.83A64.92,64.92,0,0,1,64,152,64.07,64.07,0,0,1,128,88Zm99.74,13a8,8,0,0,0-14.24,7.3,96.27,96.27,0,0,1,5,75.71l-181.1-.07A96.24,96.24,0,0,1,128,56h.88a95,95,0,0,1,42.82,10.5A8,8,0,1,0,179,52.27a112,112,0,0,0-156.66,137A16.07,16.07,0,0,0,37.46,200H218.53a16,16,0,0,0,15.11-10.71,112.35,112.35,0,0,0-5.9-88.3Z"/></svg></span>
          <div class="fdicnet-featured-link__body">
            <h3><a href="/node">Performance Management</a></h3>
            <p>Employee performance management program</p>
          </div>
        </article>
        <article class="fdicnet-featured-link" role="listitem">
          <span class="fdicnet-featured-link__icon" aria-hidden="true"><svg viewBox="0 0 256 256"><path d="M176,152h32a16,16,0,0,0,16-16V104a16,16,0,0,0-16-16H176a16,16,0,0,0-16,16v8H88V80h8a16,16,0,0,0,16-16V32A16,16,0,0,0,96,16H64A16,16,0,0,0,48,32V64A16,16,0,0,0,64,80h8V192a24,24,0,0,0,24,24h64v8a16,16,0,0,0,16,16h32a16,16,0,0,0,16-16V192a16,16,0,0,0-16-16H176a16,16,0,0,0-16,16v8H96a8,8,0,0,1-8-8V128h72v8A16,16,0,0,0,176,152ZM64,32H96V64H64ZM176,192h32v32H176Zm0-88h32v32H176Z"/></svg></span>
          <div class="fdicnet-featured-link__body">
            <h3><a href="/node">Divisions &amp; Offices</a></h3>
            <p>Browse all FDIC divisions and offices</p>
          </div>
        </article>
        <article class="fdicnet-featured-link" role="listitem">
          <span class="fdicnet-featured-link__icon" aria-hidden="true"><svg viewBox="0 0 256 256"><path d="M229.66,90.34l-96,96a8,8,0,0,1-11.32,0l-48-48a8,8,0,0,1,11.32-11.32L128,169.37,218.34,79a8,8,0,0,1,11.32,11.32Z"/></svg></span>
          <div class="fdicnet-featured-link__body">
            <h3><a href="/node">Approved Directives</a></h3>
            <p>View official, current FDIC directives and policy issuances</p>
          </div>
        </article>
        <article class="fdicnet-featured-link" role="listitem">
          <span class="fdicnet-featured-link__icon" aria-hidden="true"><svg viewBox="0 0 256 256"><path d="M213.66,82.34l-56-56A8,8,0,0,0,152,24H56A16,16,0,0,0,40,40V216a16,16,0,0,0,16,16H200a16,16,0,0,0,16-16V88A8,8,0,0,0,213.66,82.34ZM160,51.31,188.69,80H160ZM200,216H56V40h88V88a8,8,0,0,0,8,8h48V216Zm-32-80a8,8,0,0,1-8,8H96a8,8,0,0,1,0-16h64A8,8,0,0,1,168,136Zm0,32a8,8,0,0,1-8,8H96a8,8,0,0,1,0-16h64A8,8,0,0,1,168,168Z"/></svg></span>
          <div class="fdicnet-featured-link__body">
            <h3><a href="/node">RD Memos</a></h3>
            <p>Access memoranda issued by Regional Directors</p>
          </div>
        </article>
        <article class="fdicnet-featured-link" role="listitem">
          <span class="fdicnet-featured-link__icon" aria-hidden="true"><svg viewBox="0 0 256 256"><path d="M185.33,114.21l29.14-27.42.17-.17a32,32,0,0,0-45.26-45.26c0,.06-.11.11-.17.17L141.79,70.67l-83-30.2a8,8,0,0,0-8.39,1.86l-24,24a8,8,0,0,0,1.22,12.31l63.89,42.59L76.69,136H56a8,8,0,0,0-5.65,2.34l-24,24A8,8,0,0,0,29,175.42l36.82,14.73,14.7,36.75.06.16a8,8,0,0,0,13.18,2.47l23.87-23.88A8,8,0,0,0,120,200V179.31l14.76-14.76,42.59,63.89a8,8,0,0,0,12.31,1.22l24-24a8,8,0,0,0,1.86-8.39Zm-.07,97.23-42.59-63.88A8,8,0,0,0,136.8,144c-.27,0-.53,0-.79,0a8,8,0,0,0-5.66,2.35l-24,24A8,8,0,0,0,104,176v20.69L90.93,209.76,79.43,181A8,8,0,0,0,75,176.57l-28.74-11.5L59.32,152H80a8,8,0,0,0,5.66-2.34l24-24a8,8,0,0,0-1.22-12.32L44.56,70.74l13.5-13.49,83.22,30.26a8,8,0,0,0,8.56-2L180.78,52.6A16,16,0,0,1,203.4,75.23l-32.87,30.93a8,8,0,0,0-2,8.56l30.26,83.22Z"/></svg></span>
          <div class="fdicnet-featured-link__body">
            <h3><a href="/node">Travel &amp; Expense</a></h3>
            <p>Submit and manage travel authorizations and expense reimbursements</p>
          </div>
        </article>
        <article class="fdicnet-featured-link" role="listitem">
          <span class="fdicnet-featured-link__icon" aria-hidden="true"><svg viewBox="0 0 256 256"><path d="M72,88V40a8,8,0,0,1,16,0V88a8,8,0,0,1-16,0ZM216,40V224a8,8,0,0,1-16,0V176H152a8,8,0,0,1-8-8,268.75,268.75,0,0,1,7.22-56.88c9.78-40.49,28.32-67.63,53.63-78.47A8,8,0,0,1,216,40ZM200,53.9c-32.17,24.57-38.47,84.42-39.7,106.1H200ZM119.89,38.69a8,8,0,1,0-15.78,2.63L112,88.63a32,32,0,0,1-64,0l7.88-47.31a8,8,0,1,0-15.78-2.63l-8,48A8.17,8.17,0,0,0,32,88a48.07,48.07,0,0,0,40,47.32V224a8,8,0,0,0,16,0V135.32A48.07,48.07,0,0,0,128,88a8.17,8.17,0,0,0-.11-1.31Z"/></svg></span>
          <div class="fdicnet-featured-link__body">
            <h3><a href="/node">Cafeteria Menus</a></h3>
            <p>Food and beverage choices for a better work day</p>
          </div>
        </article>
      </div>
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
        <fd-event month="Feb" day="19" title="Eileen Vidrine on the Human Component of AI" href="/node" data-fdic-event-metadata='["FDIC-Wide", "CIOO, DIT", "Webinar"]'></fd-event>
        <fd-event month="Feb" day="20" title="Planned eFOS+ Outage" href="/node" data-fdic-event-metadata='["FDIC-Wide"]'></fd-event>
        <fd-event month="Feb" day="24" title="Section 508 Document Remediation Session (PPT/Excel)" href="/node" data-fdic-event-metadata='["FDIC-Wide", "Training"]'></fd-event>
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
          <div class="fdicnet-social__body">
            <p class="fdicnet-social__lede">Read Acting Chairman Travis Hill&apos;s Statement on Executive Order, &quot;Guaranteeing Fair Banking For All Americans.&quot;</p>
            <p class="fdicnet-social__link"><a href="https://fdic.gov/news/press-releases/2025/statement-acting-chairman-travis-hill-executive-order-titled-guaranteeing">https://fdic.gov/news/press-releases/2025/statement-acting-chairman-travis-hill-executive-order-titled-guaranteeing</a></p>
            <p class="fdicnet-meta">Posted Aug. 8, 2025 on…</p>
            <div class="fdicnet-social__platforms" aria-label="Social platforms">
              <a class="fdicnet-social__platform" href="https://x.com/fdicgov" aria-label="FDIC on X">
                <svg viewBox="0 0 256 256" aria-hidden="true"><path d="M204.75,203.26a8,8,0,1,1-11.32,11.31L128,149.25,62.57,214.57a8,8,0,0,1-11.32-11.31L116.69,138,51.25,72.74A8,8,0,0,1,62.57,61.43L128,126.75l65.43-65.32a8,8,0,0,1,11.32,11.31L139.31,138Z"/></svg>
              </a>
              <a class="fdicnet-social__platform" href="https://www.linkedin.com/company/fdic/" aria-label="FDIC on LinkedIn">
                <svg viewBox="0 0 256 256" aria-hidden="true"><path d="M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40ZM92,176a8,8,0,0,1-16,0V104a8,8,0,0,1,16,0Zm-8-88A12,12,0,1,1,96,76,12,12,0,0,1,84,88Zm100,88a8,8,0,0,1-16,0V136a24,24,0,0,0-48,0v40a8,8,0,0,1-16,0V104a8,8,0,0,1,16,0v5.31A40,40,0,0,1,184,136Z"/></svg>
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
