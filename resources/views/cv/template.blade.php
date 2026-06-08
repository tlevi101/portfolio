<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8" />
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

    body {
      font-family: 'DejaVu Sans', sans-serif;
      font-size: 9.2pt;
      line-height: 1.32;
      color: #222222;
    }

    /* Repeating sidebar (fixed → painted on every page) */
    .sidebar {
      box-sizing: content-box;
      position: fixed;
      top: 0;
      right: 0;
      width: 62mm;            /* content 62 + 7+7 padding = 76mm → spans 134–210mm */
      height: 297mm;
      background: #f3f2ef;
      border-left: 0.3mm solid #d6d3cc;
      padding: 12mm 7mm;
    }

    /* Flowing main column. dompdf treats these as content-box, so width +
       padding must stay under the sidebar's left edge (~134mm) to avoid the
       text rendering beneath the fixed sidebar. 108 + 14 + 8 = 130mm. */
    .main {
      box-sizing: content-box;
      width: 108mm;
      padding: 9mm 8mm 9mm 14mm;
    }

    /* Header */
    .name {
      font-size: 17pt;
      font-weight: bold;
      letter-spacing: -0.2pt;
      color: #111111;
    }

    .role {
      margin-top: 2mm;
      font-size: 9pt;
      font-weight: bold;
      letter-spacing: 0.8pt;
      text-transform: uppercase;
      color: #555555;
    }

    .summary {
      margin-top: 3mm;
      color: #3c3c3c;
    }

    /* Section headings */
    .section {
      margin-top: 3mm;
    }

    .section-title {
      font-size: 9pt;
      font-weight: bold;
      letter-spacing: 1pt;
      text-transform: uppercase;
      color: #111111;
      padding-bottom: 1mm;
      border-bottom: 0.5mm solid #cfccc4;
      margin-bottom: 2mm;
    }

    /* Entries */
    .entry {
      margin-bottom: 2.4mm;
    }

    .entry:last-child {
      margin-bottom: 0;
    }

    .row {
      width: 100%;
      border-collapse: collapse;
    }

    .row-l {
      font-size: 10pt;
      font-weight: bold;
      color: #1a1a1a;
      vertical-align: bottom;
    }

    .row-r {
      text-align: right;
      white-space: nowrap;
      vertical-align: bottom;
      font-size: 8pt;
      color: #6b6b6b;
    }

    .entry-sub {
      margin-top: 0.6mm;
      font-size: 8.2pt;
      color: #6b6b6b;
    }

    .bullets {
      margin-top: 1mm;
      padding-left: 4.5mm;
    }

    .bullets li {
      margin-bottom: 0.5mm;
      color: #2c2c2c;
    }

    /* Projects: name on its own line, stack on a second row beneath it.
       Visually secondary to experience. */
    .proj-entry {
      margin-bottom: 1.8mm;
      font-size: 8.4pt;
    }

    .proj-entry:last-child {
      margin-bottom: 0;
    }

    .proj-name {
      font-weight: bold;
      color: #1a1a1a;
    }

    .proj-stack {
      margin-top: 0.4mm;
      color: #777777;
    }

    /* Sidebar blocks */
    .photo {
      width: 100%;
      margin-bottom: 4mm;
      text-align: center;
    }

    /* Matte frame ("keret") around the photo */
    .photo-frame {
      display: inline-block;
      padding: 1.4mm;
      background: #ffffff;
      border: 0.4mm solid #d6d3cc;
      border-radius: 4mm;
    }

    /* Fixed width + auto height keeps the original aspect ratio in dompdf
       (which ignores object-fit); the radius gives the soft edge. */
    .photo-frame img {
      display: block;
      width: 34mm;
      height: auto;
      border-radius: 2.8mm;
    }

    .side-title {
      font-size: 8.5pt;
      font-weight: bold;
      letter-spacing: 0.8pt;
      text-transform: uppercase;
      color: #111111;
      padding-bottom: 1mm;
      border-bottom: 0.4mm solid #d6d3cc;
      margin-bottom: 2mm;
    }

    .side-section {
      margin-bottom: 3.5mm;
    }

    .side-item {
      margin-bottom: 1.5mm;
    }

    .side-label {
      font-size: 7pt;
      font-weight: bold;
      letter-spacing: 0.6pt;
      text-transform: uppercase;
      color: #8a8a86;
    }

    .side-value {
      color: #2c2c2c;
      word-wrap: break-word;
    }

    .qr {
      text-align: center;
    }

    /* Same matte frame as the portrait photo */
    .qr-frame {
      display: inline-block;
      padding: 1.4mm;
      background: #ffffff;
      border: 0.4mm solid #d6d3cc;
      border-radius: 4mm;
    }

    .qr-frame img {
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
      word-wrap: break-word;
    }

    .skill-group {
      margin-bottom: 2mm;
    }

    .skill-group-name {
      font-weight: bold;
      color: #1a1a1a;
      margin-bottom: 0.4mm;
    }

    .skill-group-items {
      color: #4a4a4a;
    }

    /* Languages: bullet list in the main column, under Education. */
    .lang-name {
      font-weight: bold;
      color: #1a1a1a;
    }

    .lang-level {
      color: #777777;
    }
  </style>
</head>
<body>

  {{-- Sidebar (repeats on every page) --}}
  <div class="sidebar">
    @if ($avatar)
      <div class="photo"><span class="photo-frame"><img src="{{ $avatar }}" alt="{{ $profile->full_name }}" /></span></div>
    @endif

    <div class="side-section">
      <div class="side-title">{{ __('Contact') }}</div>
      <div class="side-item">
        <div class="side-label">{{ __('Name') }}</div>
        <div class="side-value">{{ $profile->full_name }}</div>
      </div>
      <div class="side-item">
        <div class="side-label">{{ __('Email') }}</div>
        <div class="side-value">{{ $profile->email }}</div>
      </div>
      @if ($profile->phone)
        <div class="side-item">
          <div class="side-label">{{ __('Phone') }}</div>
          <div class="side-value">{{ $profile->phone }}</div>
        </div>
      @endif
      @if ($profile->location)
        <div class="side-item">
          <div class="side-label">{{ __('Location') }}</div>
          <div class="side-value">{{ $profile->location }}</div>
        </div>
      @endif
      @if ($profile->linkedin_url)
        <div class="side-item">
          <div class="side-label">LinkedIn</div>
          <div class="side-value">{{ parse_url($profile->linkedin_url, PHP_URL_HOST) . parse_url($profile->linkedin_url, PHP_URL_PATH) }}</div>
        </div>
      @endif
      @if ($profile->github_url)
        <div class="side-item">
          <div class="side-label">GitHub</div>
          <div class="side-value">{{ parse_url($profile->github_url, PHP_URL_HOST) . parse_url($profile->github_url, PHP_URL_PATH) }}</div>
        </div>
      @endif
      {{-- Portfolio URL intentionally omitted here: the QR block below already
           shows it, so listing it in Contact too just duplicates a line. --}}
    </div>

    @if ($qr)
      @php($portfolioUrl = $profile->portfolio_url ?: route('portfolio.show', $profile->slug))
      <div class="side-section">
        <div class="side-title">{{ __('Portfolio') }}</div>
        <div class="qr">
          <span class="qr-frame"><img src="{{ $qr }}" alt="{{ __('Portfolio') }}" /></span>
          <a class="qr-url" href="{{ $portfolioUrl }}">{{ rtrim(preg_replace('#^https?://#', '', $portfolioUrl), '/') }}</a>
        </div>
      </div>
    @endif

    @if ($skillsByGroup->isNotEmpty())
      <div class="side-section">
        <div class="side-title">{{ __('Skills') }}</div>
        @foreach ($skillsByGroup as $groupValue => $groupSkills)
          <div class="skill-group">
            <div class="skill-group-name">{{ __($groupSkills->first()->group->label()) }}</div>
            <div class="skill-group-items">{{ $groupSkills->pluck('name')->implode(', ') }}</div>
          </div>
        @endforeach
      </div>
    @endif

  </div>

  {{-- Main column --}}
  <div class="main">
    <div class="name">{{ $profile->full_name }}</div>
    <div class="role">{{ $profile->role }}</div>
    @if ($profile->tagline)
      <div class="summary">{{ $profile->tagline }}</div>
    @endif

    @if ($workExperiences->isNotEmpty())
      <div class="section">
        <div class="section-title">{{ __('Experience') }}</div>
        @foreach ($workExperiences as $job)
          <div class="entry">
            <table class="row"><tr>
              <td class="row-l">{{ $job->company }} — {{ $job->title }}</td>
              <td class="row-r">{{ $job->period }}</td>
            </tr></table>
            @if ($job->location)
              <div class="entry-sub">{{ $job->location }}</div>
            @endif
            @if (!empty($job->bullets))
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

    @if ($selectedProjects->isNotEmpty())
      <div class="section">
        <div class="section-title">{{ __('Projects') }}</div>
        @foreach ($selectedProjects as $project)
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
            <table class="row"><tr>
              <td class="row-l">{{ $edu->school }}</td>
              <td class="row-r">{{ $edu->start_year ? $edu->start_year.' – '.$edu->graduation_year : $edu->graduation_year }}</td>
            </tr></table>
            @if ($edu->degree || $edu->location)
              <div class="entry-sub">{{ implode(' — ', array_filter([$edu->degree, $edu->location])) }}</div>
            @endif
          </div>
        @endforeach
      </div>
    @endif

    @if (! empty($profile->languages))
      <div class="section">
        <div class="section-title">{{ __('Languages') }}</div>
        <ul class="bullets">
          @foreach ($profile->languages as $language)
            <li><span class="lang-name">{{ $language['name'] }}</span> — <span class="lang-level">{{ __($language['level']) }}</span></li>
          @endforeach
        </ul>
      </div>
    @endif
  </div>

</body>
</html>
