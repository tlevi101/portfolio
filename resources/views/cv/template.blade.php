<!DOCTYPE html>
<html lang="hu">
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
      font-size: 8.3pt;
      line-height: 1.3;
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
      padding: 16mm 7mm;
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

    .proj-line {
      margin-top: 0.8mm;
    }

    .proj-line .k {
      font-weight: bold;
      color: #1a1a1a;
    }

    .proj-stack {
      margin-top: 0.8mm;
      font-size: 8pt;
      color: #555555;
    }

    /* Sidebar blocks */
    .photo {
      width: 100%;
      margin-bottom: 6mm;
    }

    .photo img {
      width: 100%;
      border: 0.3mm solid #d6d3cc;
    }

    .side-title {
      font-size: 8.5pt;
      font-weight: bold;
      letter-spacing: 0.8pt;
      text-transform: uppercase;
      color: #111111;
      padding-bottom: 1.2mm;
      border-bottom: 0.4mm solid #d6d3cc;
      margin-bottom: 2.6mm;
    }

    .side-section {
      margin-bottom: 6mm;
    }

    .side-item {
      margin-bottom: 2.4mm;
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
      margin-bottom: 6mm;
    }

    .qr img {
      width: 28mm;
      height: 28mm;
      border: 0.3mm solid #d6d3cc;
      padding: 1.5mm;
      background: #ffffff;
    }

    .qr-label {
      display: block;
      margin-top: 1.4mm;
      font-size: 7pt;
      letter-spacing: 0.6pt;
      text-transform: uppercase;
      color: #8a8a86;
    }

    .skill-group {
      margin-bottom: 3mm;
    }

    .skill-group-name {
      font-weight: bold;
      color: #1a1a1a;
      margin-bottom: 0.4mm;
    }

    .skill-group-items {
      color: #4a4a4a;
    }
  </style>
</head>
<body>

  {{-- Sidebar (repeats on every page) --}}
  <div class="sidebar">
    @if ($avatar)
      <div class="photo"><img src="{{ $avatar }}" alt="{{ $profile->full_name }}" /></div>
    @endif

    <div class="side-section">
      <div class="side-title">Kapcsolat</div>
      <div class="side-item">
        <div class="side-label">Email</div>
        <div class="side-value">{{ $profile->email }}</div>
      </div>
      @if ($profile->phone)
        <div class="side-item">
          <div class="side-label">Telefon</div>
          <div class="side-value">{{ $profile->phone }}</div>
        </div>
      @endif
      @if ($profile->location)
        <div class="side-item">
          <div class="side-label">Helyszín</div>
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
      @if ($profile->portfolio_url)
        <div class="side-item">
          <div class="side-label">Portfólió</div>
          <div class="side-value">{{ parse_url($profile->portfolio_url, PHP_URL_HOST) . parse_url($profile->portfolio_url, PHP_URL_PATH) }}</div>
        </div>
      @endif
    </div>

    @if ($qr)
      <div class="qr">
        <img src="{{ $qr }}" alt="Portfólió QR" />
        <span class="qr-label">Portfólió</span>
      </div>
    @endif

    @if ($skillsByGroup->isNotEmpty())
      <div class="side-section">
        <div class="side-title">Készségek</div>
        @foreach ($skillsByGroup as $groupValue => $groupSkills)
          <div class="skill-group">
            <div class="skill-group-name">{{ $groupSkills->first()->group->label() }}</div>
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
        <div class="section-title">Tapasztalat</div>
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
        <div class="section-title">Kiemelt projektek</div>
        @foreach ($selectedProjects as $project)
          <div class="entry">
            <table class="row"><tr>
              <td class="row-l">{{ $project->title }}</td>
            </tr></table>
            @if ($project->summary)
              <div class="proj-line">{{ $project->summary }}</div>
            @endif
            @if (! empty($project->stack))
              <div class="proj-stack">{{ implode(' · ', $project->stack) }}</div>
            @endif
          </div>
        @endforeach
      </div>
    @endif

    @if ($educations->isNotEmpty())
      <div class="section">
        <div class="section-title">Tanulmányok</div>
        @foreach ($educations as $edu)
          <div class="entry">
            <table class="row"><tr>
              <td class="row-l">{{ $edu->school }}</td>
              <td class="row-r">{{ $edu->graduation_year }}</td>
            </tr></table>
            @if ($edu->degree || $edu->location)
              <div class="entry-sub">{{ implode(' — ', array_filter([$edu->degree, $edu->location])) }}</div>
            @endif
          </div>
        @endforeach
      </div>
    @endif
  </div>

</body>
</html>
