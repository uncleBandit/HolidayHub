{{-- Brings the site's typography into the panel: DM Serif Display for page
     and section headings, on the panel's own DM Sans body text. --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&display=swap" rel="stylesheet">
<style>
    .fi-header-heading,
    .fi-wi-stats-overview-stat-value,
    .fi-section-header-heading,
    .fi-modal-heading { font-family: 'DM Serif Display', serif; font-weight: 400; letter-spacing: 0; }
    .fi-header-heading { font-size: clamp(1.7rem, 2.6vw, 2.2rem); }
    html:not(.dark) .fi-body { background: #F7F3EB; }
    html:not(.dark) .fi-sidebar,
    html:not(.dark) .fi-topbar > nav { background: #FBF8F2; }
</style>
