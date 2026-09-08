<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8" />
  @php
    /**
     * Inline 24×24 outline icons for the contact rows. They are embedded rather
     * than loaded so the document renders identically in the PDF (which Chrome
     * prints from a string, with no base URL) and in the admin live preview.
     */
    $icons = [
      'email' => '<path d="M3 6.5A1.5 1.5 0 0 1 4.5 5h15A1.5 1.5 0 0 1 21 6.5v11a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 17.5z"/><path d="m3.5 7 8.5 6 8.5-6"/>',
      'phone' => '<path d="M7.2 3.5h-.7A2.5 2.5 0 0 0 4 6c0 7.7 6.3 14 14 14a2.5 2.5 0 0 0 2.5-2.5v-.7a1 1 0 0 0-.76-.97l-3.4-.85a1 1 0 0 0-1 .32l-.9 1.05a12.6 12.6 0 0 1-5.83-5.83l1.05-.9a1 1 0 0 0 .32-1l-.85-3.4a1 1 0 0 0-.97-.76z"/>',
      'location' => '<path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/>',
      'linkedin' => '<rect x="3.5" y="3.5" width="17" height="17" rx="2.2"/><path d="M8 10.5V17M8 7.4v.1M12 17v-3.6a2.1 2.1 0 0 1 4.2 0V17"/>',
      'github' => '<path d="M12 3.2a8.8 8.8 0 0 0-2.8 17.2c.44.08.6-.2.6-.43v-1.5c-2.45.53-2.97-1.18-2.97-1.18-.4-1.02-.98-1.3-.98-1.3-.8-.55.06-.54.06-.54.89.06 1.35.92 1.35.92.79 1.35 2.07.96 2.57.73.08-.57.31-.96.56-1.18-1.95-.22-4-.98-4-4.35 0-.96.34-1.75.9-2.36-.09-.22-.39-1.12.09-2.33 0 0 .74-.24 2.42.9a8.4 8.4 0 0 1 4.4 0c1.68-1.14 2.42-.9 2.42-.9.48 1.21.18 2.11.09 2.33.56.61.9 1.4.9 2.36 0 3.38-2.06 4.13-4.02 4.34.32.28.6.82.6 1.65v2.45c0 .24.16.52.6.43A8.8 8.8 0 0 0 12 3.2z"/>',
    ];
  @endphp
  <style>
    @page {
      size: A4;
      margin: 0;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html {
      /* Keep the sidebar tint and the chip fills in the printed sheet. */
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    body {
      /* Liberation Sans ships with Chrome's own font dependency
         (fonts-liberation), so it is present wherever the renderer is. */
      font-family: 'Liberation Sans', 'DejaVu Sans', Arial, Helvetica, sans-serif;
      font-size: 9.2pt;
      line-height: 1.34;
      color: #222222;
      background: #ffffff;
    }

    /* ---------------------------------------------------------------- layout */

    /* Fixed elements are repainted on every printed page by Chrome, which is
       what keeps the sidebar on page two of a longer CV. */
    .sidebar {
      position: fixed;
      top: 0;
      right: 0;
      width: 76mm;
      height: 297mm;
      display: flex;
      flex-direction: column;
      background: #f3f2ef;
      border-left: 0.3mm solid #d6d3cc;
      padding: 12mm 7mm;
    }

    .main {
      width: 134mm;
      padding: 11mm 8mm 11mm 14mm;
    }

    /* ---------------------------------------------------------------- header */

    .name {
      font-size: 17pt;
      font-weight: bold;
      letter-spacing: -0.2pt;
      color: #111111;
    }

    .role {
      margin-top: 1.6mm;
      /* Must stay above the summary paragraph's 9.2pt: the job title should not
         read as lighter than body copy. */
      font-size: 10pt;
      font-weight: bold;
      letter-spacing: 0.8pt;
      text-transform: uppercase;
      color: #555555;
    }

    /* Two lines at this measure is roughly 190 characters; the admin caps the
       field so the block cannot silently push the layout down a page. */
    .summary {
      margin-top: 2.6mm;
      color: #3c3c3c;
    }

    /* The summary comes from a rich-text editor, so it arrives wrapped in
       block tags that must not add vertical rhythm of their own. */
    .summary p {
      margin: 0;
    }

    .summary p + p {
      margin-top: 1.4mm;
    }

    .summary a {
      color: inherit;
      text-decoration: none;
    }

    /* ------------------------------------------------------ highlighted stack */

    /* The line above Experience, swapped per job ad from the admin. Set as
       plain running text rather than chips: the sidebar's Skills card is where
       the eye is meant to land, and a second boxed block would compete with it. */
    .stack-bar {
      margin-top: 4.5mm;
    }

    .stack-bar-label {
      font-size: 7pt;
      font-weight: bold;
      /* Tracking is kept proportional to the other headings. Wider than this and
         the glyph gaps read as word breaks to PDF text extraction, which turns
         the label into "F Ő   T E C H N O L Ó G I Á K". */
      letter-spacing: 0.6pt;
      text-transform: uppercase;
      color: #8a8a86;
      margin-bottom: 1.2mm;
    }

    /* Runs as prose so it wraps like a sentence. */
    .stack-bar .chips {
      display: block;
    }

    .stack-bar .chip {
      display: inline;
      padding: 0;
      border: 0;
      border-radius: 0;
      background: none;
      font-size: 9.2pt;
      font-weight: bold;
      color: #2c2c2c;
    }

    /* The separator sits inside the preceding item so it never starts a line,
       and drops the item's `nowrap` so the break can happen after it. */
    .stack-bar .chip:not(:last-child)::after {
      content: ' \00B7 ';
      white-space: normal;
      font-weight: normal;
      color: #b5b1a8;
    }

    /* ----------------------------------------------------------------- chips */

    .chips {
      display: flex;
      flex-wrap: wrap;
      gap: 1mm;
    }

    .chip {
      display: block;
      padding: 0.7mm 1.7mm;
      border: 0.25mm solid #d6d3cc;
      border-radius: 1.4mm;
      background: #ffffff;
      font-size: 7.4pt;
      line-height: 1.28;
      color: #33332f;
      white-space: nowrap;
    }

    /* -------------------------------------------------------------- sections */

    .section {
      margin-top: 5mm;
    }

    .section-title {
      font-size: 9pt;
      font-weight: bold;
      /* Same extraction constraint as `.stack-bar-label`. */
      letter-spacing: 0.8pt;
      text-transform: uppercase;
      color: #111111;
      padding-bottom: 1mm;
      border-bottom: 0.5mm solid #cfccc4;
      margin-bottom: 2.4mm;
    }

    .entry {
      margin-bottom: 2.6mm;
      break-inside: avoid;
    }

    .entry:last-child {
      margin-bottom: 0;
    }

    .entry-head {
      display: flex;
      align-items: baseline;
      justify-content: space-between;
      gap: 4mm;
    }

    .entry-title {
      font-size: 10pt;
      font-weight: bold;
      color: #1a1a1a;
    }

    .entry-meta {
      flex: none;
      font-size: 8pt;
      color: #6b6b6b;
      white-space: nowrap;
    }

    /* The title and the date are separate flex items, so nothing sits between
       them in the PDF content stream and extraction glues them together
       ("Full-Stack Developer2022.08"). A non-breaking space is not collapsed
       away by the layout, so it survives into the stream as a real separator. */
    .entry-meta::before {
      content: '\00A0\00A0';
    }

    .entry-sub {
      margin-top: 0.6mm;
      font-size: 8.2pt;
      color: #6b6b6b;
    }

    .bullets {
      margin-top: 1.2mm;
      padding-left: 4.5mm;
    }

    .bullets li {
      margin-bottom: 0.5mm;
      color: #2c2c2c;
    }

    /* Projects stay visually secondary to experience: name on its own line with
       the stack beneath it. */
    .proj-entry {
      margin-bottom: 1.8mm;
      font-size: 8.4pt;
      break-inside: avoid;
    }

    .proj-entry:last-child {
      margin-bottom: 0;
    }

    .proj-name {
      font-weight: bold;
      color: #1a1a1a;
    }

    /* One notch smaller and lighter than the project name: five stack lines of
       equal weight otherwise read as banding rather than as captions. */
    .proj-stack {
      margin-top: 0.4mm;
      font-size: 7.6pt;
      color: #9c988f;
    }

    /* -------------------------------------------------------- sidebar blocks */

    .side-section {
      margin-bottom: 5mm;
    }

    /* Pins the portfolio/QR block to the bottom of the sidebar regardless of how
       much sits above it. */
    .side-section.is-last {
      margin-top: auto;
      margin-bottom: 0;
    }

    .side-title {
      font-size: 8.5pt;
      font-weight: bold;
      letter-spacing: 0.8pt;
      text-transform: uppercase;
      color: #111111;
      padding-bottom: 1mm;
      border-bottom: 0.4mm solid #d6d3cc;
      margin-bottom: 2.4mm;
    }

    .photo {
      margin-bottom: 5mm;
      text-align: center;
    }

    /* Matte frame ("keret") shared by the portrait and the QR code. */
    .frame {
      display: inline-block;
      padding: 1.4mm;
      background: #ffffff;
      border: 0.4mm solid #d6d3cc;
      border-radius: 4mm;
    }

    .photo .frame img {
      display: block;
      width: 34mm;
      height: auto;
      border-radius: 2.8mm;
    }

    /* ------------------------------------------------------- contact (icons) */

    /* Label-free rows: the icon carries the meaning, so the value gets the full
       width of a narrow column. */
    .contact-row {
      display: flex;
      align-items: flex-start;
      gap: 1.8mm;
      margin-bottom: 1.6mm;
    }

    .contact-row:last-child {
      margin-bottom: 0;
    }

    .contact-row svg {
      flex: none;
      width: 3.4mm;
      height: 3.4mm;
      margin-top: 0.35mm;
      stroke: #1d6b73;
      stroke-width: 1.6;
      fill: none;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    /* The brand marks are solid shapes, not outlines. */
    .contact-row svg.is-solid {
      fill: #1d6b73;
      stroke: none;
    }

    .contact-value {
      color: #2c2c2c;
      word-break: break-word;
      min-width: 0;
    }

    /* ------------------------------------------------------------- languages */

    .lang-row {
      display: flex;
      align-items: baseline;
      justify-content: space-between;
      gap: 2mm;
      margin-bottom: 1.2mm;
    }

    .lang-row:last-child {
      margin-bottom: 0;
    }

    .lang-name {
      font-weight: bold;
      color: #1a1a1a;
    }

    .lang-level {
      flex: none;
      font-size: 8pt;
      color: #777777;
    }

    /* Same flex-pair problem as `.entry-meta`: nothing separates the language
       from its level in the PDF content stream, so extraction reads
       "MagyarAnyanyelvi". */
    .lang-level::before {
      content: '\00A0\00A0';
    }

    /* ---------------------------------------------------------------- skills */

    .skill-group {
      margin-bottom: 2.4mm;
    }

    .skill-group:last-child {
      margin-bottom: 0;
    }

    .skill-group-name {
      font-size: 7.6pt;
      font-weight: bold;
      letter-spacing: 0.5pt;
      text-transform: uppercase;
      color: #1d6b73;
      margin-bottom: 1mm;
    }

    /* -------------------------------------------------------------------- QR */

    .qr {
      text-align: center;
    }

    .qr .frame img {
      display: block;
      width: 28mm;
      height: 28mm;
      border-radius: 2.8mm;
    }

    .qr-url {
      display: block;
      margin-top: 1.8mm;
      font-size: 7.5pt;
      color: #1d6b73;
      text-decoration: none;
      word-break: break-word;
    }
  </style>
</head>
<body>

  {{-- Sidebar: photo → contact → languages → skills → portfolio/QR --}}
  <aside class="sidebar">
    @if ($avatar)
      <div class="photo"><span class="frame"><img src="{{ $avatar }}" alt="{{ $cv->full_name }}" /></span></div>
    @endif

    @php
      $contactRows = collect([
        ['icon' => 'email', 'value' => $cv->email, 'solid' => false],
        ['icon' => 'phone', 'value' => $cv->phone, 'solid' => true],
        ['icon' => 'location', 'value' => $cv->location, 'solid' => false],
        ['icon' => 'linkedin', 'value' => $cv->linkedin_url, 'solid' => false, 'strip' => true],
        ['icon' => 'github', 'value' => $cv->github_url, 'solid' => true, 'strip' => true],
      ])->filter(fn (array $row): bool => filled($row['value']));
    @endphp

    @if ($contactRows->isNotEmpty())
      <div class="side-section">
        <div class="side-title">{{ __('Contact') }}</div>
        @foreach ($contactRows as $row)
          <div class="contact-row">
            <svg viewBox="0 0 24 24" @class(['is-solid' => $row['solid']]) aria-hidden="true">{!! $icons[$row['icon']] !!}</svg>
            <span class="contact-value">
              @if ($row['strip'] ?? false)
                {{-- A CV is read on paper: show the readable handle, not the scheme. --}}
                {{ rtrim(preg_replace('#^(https?://)?(www\.)?#', '', $row['value']), '/') }}
              @else
                {{ $row['value'] }}
              @endif
            </span>
          </div>
        @endforeach
      </div>
    @endif

    @if ($languages->isNotEmpty())
      <div class="side-section">
        <div class="side-title">{{ __('Languages') }}</div>
        @foreach ($languages as $language)
          <div class="lang-row">
            <span class="lang-name">{{ $language['name'] }}</span>
            <span class="lang-level">{{ __($language['level'] ?? '') }}</span>
          </div>
        @endforeach
      </div>
    @endif

    @if ($skillsByGroup->isNotEmpty())
      <div class="side-section">
        <div class="side-title">{{ __('Skills') }}</div>
        @foreach ($skillsByGroup as $groupSkills)
          <div class="skill-group">
            <div class="skill-group-name">{{ __($groupSkills->first()->group->label()) }}</div>
            <div class="chips">
              @foreach ($groupSkills as $skill)
                <span class="chip">{{ $skill->name }}</span>
              @endforeach
            </div>
          </div>
        @endforeach
      </div>
    @endif

    @if ($qr && $portfolioUrl)
      <div class="side-section is-last">
        <div class="side-title">{{ __('Portfolio') }}</div>
        <div class="qr">
          <span class="frame"><img src="{{ $qr }}" alt="{{ __('Portfolio') }}" /></span>
          <a class="qr-url" href="{{ $trackedUrl ?? $portfolioUrl }}">{{ rtrim(preg_replace('#^https?://#', '', $portfolioUrl), '/') }}</a>
        </div>
      </div>
    @endif
  </aside>

  {{-- Main column --}}
  <main class="main">
    <div class="name">{{ $cv->full_name }}</div>
    @if ($cv->role)
      <div class="role">{{ $cv->role }}</div>
    @endif
    @if (filled($cv->summary))
      <div class="summary">{!! str($cv->summary)->sanitizeHtml() !!}</div>
    @endif

    @if ($stackHighlights->isNotEmpty())
      <div class="stack-bar">
        <div class="stack-bar-label">{{ __('Core stack') }}</div>
        <div class="chips">
          @foreach ($stackHighlights as $item)
            <span class="chip">{{ $item }}</span>
          @endforeach
        </div>
      </div>
    @endif

    @if ($workExperiences->isNotEmpty())
      <div class="section">
        <div class="section-title">{{ __('Experience') }}</div>
        @foreach ($workExperiences as $job)
          <div class="entry">
            <div class="entry-head">
              <span class="entry-title">{{ $job->company }} — {{ $job->title }}</span>
              <span class="entry-meta">{{ $job->period }}</span>
            </div>
            @if ($job->location)
              <div class="entry-sub">{{ $job->location }}</div>
            @endif
            @if (! empty($job->bullets))
              <ul class="bullets">
                @foreach ($job->bullets as $bullet)
                  <li>{{ $bullet }}</li>
                @endforeach
              </ul>
            @endif
          </div>
        @endforeach
      </div>
    @endif

    @if ($projects->isNotEmpty())
      <div class="section">
        <div class="section-title">{{ __('Projects') }}</div>
        @foreach ($projects as $project)
          <div class="proj-entry">
            <div class="proj-name">{{ $project->title }}</div>
            @if (! empty($project->stack))
              <div class="proj-stack">{{ implode(' · ', $project->stack) }}</div>
            @endif
          </div>
        @endforeach
      </div>
    @endif

    @if ($educations->isNotEmpty())
      <div class="section">
        <div class="section-title">{{ __('Education') }}</div>
        @foreach ($educations as $edu)
          <div class="entry">
            <div class="entry-head">
              <span class="entry-title">{{ $edu->school }}</span>
              <span class="entry-meta">{{ $edu->start_year ? $edu->start_year.' – '.$edu->graduation_year : $edu->graduation_year }}</span>
            </div>
            @if ($edu->degree || $edu->location)
              <div class="entry-sub">{{ implode(' — ', array_filter([$edu->degree, $edu->location])) }}</div>
            @endif
          </div>
        @endforeach
      </div>
    @endif
  </main>

</body>
</html>
