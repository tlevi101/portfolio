<!DOCTYPE html>
<html lang="en">
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
      font-family: Arial, Helvetica, sans-serif;
      font-size: 11px;
      line-height: 1.45;
      color: #161311;
      background: white;
    }

    .page {
      display: table;
      width: 100%;
      min-height: 297mm;
    }

    .main {
      display: table-cell;
      width: 62%;
      padding: 16mm 12mm 16mm 14mm;
      border-right: 1px solid #d9d0c5;
      vertical-align: top;
    }

    .side {
      display: table-cell;
      width: 38%;
      padding: 16mm 14mm 16mm 12mm;
      vertical-align: top;
    }

    /* Hero */
    .hero {
      padding-bottom: 14px;
      border-bottom: 1px solid #d9d0c5;
      margin-bottom: 16px;
    }

    .hero-inner {
      display: table;
      width: 100%;
    }

    .hero-text {
      display: table-cell;
      vertical-align: top;
    }

    .hero-qr {
      display: table-cell;
      width: 90px;
      vertical-align: top;
      text-align: center;
      padding-left: 12px;
    }

    .hero-qr img {
      width: 82px;
      height: 82px;
      border: 1px solid #d9d0c5;
      padding: 4px;
    }

    .hero-qr-label {
      display: block;
      margin-top: 5px;
      font-size: 9px;
      color: #85796e;
    }

    .name {
      font-family: Georgia, 'Times New Roman', serif;
      font-size: 27px;
      line-height: 1;
      letter-spacing: -0.5px;
      color: #161311;
      margin-bottom: 7px;
    }

    .role {
      font-size: 11px;
      font-weight: bold;
      color: #7b5a3d;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      margin-bottom: 9px;
    }

    .summary {
      font-size: 11px;
      color: #5f574f;
      max-width: 340px;
    }

    /* Section headings */
    .section {
      margin-bottom: 16px;
    }

    .section-title {
      font-size: 10px;
      font-weight: bold;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: #255f89;
      margin-bottom: 9px;
      padding-bottom: 4px;
      border-bottom: 1px solid #d9d0c5;
    }

    /* Experience / project entries */
    .entry {
      margin-bottom: 13px;
    }

    .entry:last-child {
      margin-bottom: 0;
    }

    .entry-head {
      display: table;
      width: 100%;
      margin-bottom: 3px;
    }

    .entry-head-left {
      display: table-cell;
      font-size: 12px;
      font-weight: bold;
      color: #161311;
    }

    .entry-head-right {
      display: table-cell;
      width: 90px;
      text-align: right;
      font-size: 10px;
      color: #85796e;
      white-space: nowrap;
      vertical-align: bottom;
    }

    .entry-sub {
      font-size: 10.5px;
      color: #5f574f;
      margin-bottom: 5px;
    }

    ul {
      padding-left: 16px;
      margin: 0;
    }

    li {
      font-size: 10.5px;
      color: #161311;
      margin-bottom: 4px;
    }

    /* Sidebar */
    .photo-wrap {
      margin-bottom: 14px;
    }

    .photo-wrap img {
      width: 100%;
      display: block;
    }

    .label {
      display: block;
      font-size: 8.5px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      color: #6f54a3;
      margin-bottom: 2px;
    }

    .value {
      font-size: 10.5px;
      color: #161311;
      word-break: break-all;
    }

    .contact-item {
      margin-bottom: 9px;
      padding-bottom: 9px;
      border-bottom: 1px solid #d9d0c5;
    }

    .contact-item:last-child {
      margin-bottom: 0;
      padding-bottom: 0;
      border-bottom: none;
    }

    .skill-group {
      margin-bottom: 10px;
    }

    .skill-group:last-child {
      margin-bottom: 0;
    }

    .skill-group-name {
      font-size: 10.5px;
      font-weight: bold;
      color: #161311;
      margin-bottom: 2px;
    }

    .skill-group-items {
      font-size: 10.5px;
      color: #5f574f;
    }
  </style>
</head>
<body>
<div class="page">

  {{-- Main column --}}
  <div class="main">

    {{-- Hero --}}
    <div class="hero">
      <div class="hero-inner">
        <div class="hero-text">
          <div class="name">{{ $profile->full_name }}</div>
          <div class="role">{{ $profile->role }}</div>
          <div class="summary">{{ $profile->tagline }}</div>
        </div>
        @if ($qr)
          <div class="hero-qr">
            <img src="{{ $qr }}" alt="Portfolio QR code" />
            <span class="hero-qr-label">Portfolio</span>
          </div>
        @endif
      </div>
    </div>

    {{-- Work Experience --}}
    @if ($workExperiences->isNotEmpty())
      <div class="section">
        <div class="section-title">Experience</div>
        @foreach ($workExperiences as $job)
          <div class="entry">
            <div class="entry-head">
              <div class="entry-head-left">{{ $job->company }} &mdash; {{ $job->title }}</div>
              <div class="entry-head-right">{{ $job->period }}</div>
            </div>
            @if ($job->location)
              <div class="entry-sub">{{ $job->location }}</div>
            @endif
            @if (!empty($job->bullets))
              <ul>
                @foreach ($job->bullets as $bullet)
                  <li>{{ $bullet }}</li>
                @endforeach
              </ul>
            @endif
          </div>
        @endforeach
      </div>
    @endif

    {{-- Selected Projects --}}
    @if ($selectedProjects->isNotEmpty())
      <div class="section">
        <div class="section-title">Selected Projects</div>
        @foreach ($selectedProjects as $project)
          <div class="entry">
            <div class="entry-head">
              <div class="entry-head-left">{{ $project->title }}</div>
              @if (!empty($project->stack))
                <div class="entry-head-right">{{ implode(' / ', array_slice($project->stack, 0, 3)) }}</div>
              @endif
            </div>
            <ul>
              @if ($project->problem)
                <li>{{ $project->problem }}</li>
              @endif
              @if ($project->role_description)
                <li>{{ $project->role_description }}</li>
              @endif
              @if ($project->outcome)
                <li>{{ $project->outcome }}</li>
              @endif
              @if (!$project->problem && !$project->role_description && !$project->outcome)
                <li>{{ $project->summary }}</li>
              @endif
            </ul>
          </div>
        @endforeach
      </div>
    @endif

    {{-- Education --}}
    @if ($educations->isNotEmpty())
      <div class="section">
        <div class="section-title">Education</div>
        @foreach ($educations as $edu)
          <div class="entry">
            <div class="entry-head">
              <div class="entry-head-left">{{ $edu->school }}</div>
              @if ($edu->graduation_year)
                <div class="entry-head-right">{{ $edu->graduation_year }}</div>
              @endif
            </div>
            @if ($edu->degree || $edu->location)
              <div class="entry-sub">{{ implode(' — ', array_filter([$edu->degree, $edu->location])) }}</div>
            @endif
          </div>
        @endforeach
      </div>
    @endif

  </div>

  {{-- Sidebar --}}
  <div class="side">

    @if ($avatar)
      <div class="photo-wrap">
        <img src="{{ $avatar }}" alt="{{ $profile->full_name }}" />
      </div>
    @endif

    {{-- Contact --}}
    <div class="section">
      <div class="section-title">Contact</div>
      <div class="contact-item">
        <span class="label">Email</span>
        <span class="value">{{ $profile->email }}</span>
      </div>
      @if ($profile->phone)
        <div class="contact-item">
          <span class="label">Phone</span>
          <span class="value">{{ $profile->phone }}</span>
        </div>
      @endif
      @if ($profile->location)
        <div class="contact-item">
          <span class="label">Location</span>
          <span class="value">{{ $profile->location }}</span>
        </div>
      @endif
    </div>

    {{-- Links --}}
    @if ($profile->portfolio_url || $profile->github_url || $profile->linkedin_url)
      <div class="section">
        <div class="section-title">Links</div>
        @if ($profile->portfolio_url)
          <div class="contact-item">
            <span class="label">Portfolio</span>
            <span class="value">{{ $profile->portfolio_url }}</span>
          </div>
        @endif
        @if ($profile->github_url)
          <div class="contact-item">
            <span class="label">GitHub</span>
            <span class="value">{{ parse_url($profile->github_url, PHP_URL_HOST) . parse_url($profile->github_url, PHP_URL_PATH) }}</span>
          </div>
        @endif
        @if ($profile->linkedin_url)
          <div class="contact-item">
            <span class="label">LinkedIn</span>
            <span class="value">{{ parse_url($profile->linkedin_url, PHP_URL_HOST) . parse_url($profile->linkedin_url, PHP_URL_PATH) }}</span>
          </div>
        @endif
      </div>
    @endif

    {{-- Skills --}}
    @if ($skillsByGroup->isNotEmpty())
      <div class="section">
        <div class="section-title">Skills</div>
        @foreach ($skillsByGroup as $groupValue => $groupSkills)
          <div class="skill-group">
            <div class="skill-group-name">{{ $groupSkills->first()->group->label() }}</div>
            <div class="skill-group-items">{{ $groupSkills->pluck('name')->implode(', ') }}</div>
          </div>
        @endforeach
      </div>
    @endif

  </div>

</div>
</body>
</html>
