<?php
// Header component for public files - uses CSS from main.css
?>
<header>
  <!-- TOP BAR — Logo + School Name -->
  <div class="top-bar">
    <div class="top-bar-logo">
      <picture>
        <source srcset="assets/images/logo.webp" type="image/webp">
        <img src="assets/images/logo.png" alt="SRC Logo" style="height:100%;object-fit:contain;">
      </picture>
    </div>
    <div class="top-bar-text">
      <div class="top-bar-school">STUDENT REPRESENTATIVE COUNCIL</div>
      <div class="top-bar-sub">Wa, Upper West Region · Ghana</div>
    </div>
    <div class="top-bar-divider"></div>
    <div class="top-bar-src">Dr. Hilla Limann Technical University</div>
    <div class="top-bar-right">
      <span class="top-bar-tag">Session</span>
      <span class="top-bar-date"><?php echo date('Y') . ' / ' . (date('Y') + 1); ?></span>
    </div>
  </div>

  <button class="mobile-toggle" id="mobileToggle" type="button" aria-label="Toggle mobile menu" aria-controls="navList" aria-expanded="false">
    <span></span>
  </button>
  <nav class="main-nav" aria-label="Mobile navigation">
    <ul class="nav-list" id="navList">
      <li class="nav-item">
        <a href="index.php" class="nav-link" data-no-dropdown>Home</a>
      </li>
      <li class="nav-item">
        <a href="index.php#about" class="nav-link" data-dropdown="about" aria-haspopup="true" aria-expanded="false" aria-controls="dropdown-about">About <span class="nav-chevron"></span></a>
        <div class="dropdown" id="dropdown-about">
          <a href="index.php#about" class="dropdown-item">Our Mission & Vision</a>
          <a href="constitution.php" class="dropdown-item">SRC Constitution</a>
          <a href="index.php#council" class="dropdown-item">Executive Council</a>
          <a href="profiles.php" class="dropdown-item">Executive Profiles</a>
        </div>
      </li>

      <li class="nav-item">
        <a href="#" class="nav-link" data-dropdown="services" aria-haspopup="true" aria-expanded="false" aria-controls="dropdown-services">Services <span class="nav-chevron"></span></a>
        <div class="dropdown" id="dropdown-services">
          <a href="services.php" class="dropdown-item">Our Services</a>
          <a href="marketplac.php" class="dropdown-item">SRC Market Place</a>
        </div>
      </li>

      <li class="nav-item">
        <a href="#" class="nav-link" data-dropdown="portal" aria-haspopup="true" aria-expanded="false" aria-controls="dropdown-portal">Portal <span class="nav-chevron"></span></a>
        <div class="dropdown" id="dropdown-portal">
          <a href="complaint.php" class="dropdown-item">Complaint Desk</a>
          <a href="document-request.php" class="dropdown-item">Document Request</a>
          <a href="track.php" class="dropdown-item">Track Complaint/Document</a>
          <a href="halls.php" class="dropdown-item">Halls</a>
          <a href="passq-portal.php" class="dropdown-item">PASSQ Portal</a>
        </div>
      </li>

      <li class="nav-item">
        <a href="#" class="nav-link" data-dropdown="elections" aria-haspopup="true" aria-expanded="false" aria-controls="dropdown-elections">Elections <span class="nav-chevron"></span></a>
        <div class="dropdown" id="dropdown-elections">
          <a href="upcoming-elections.php" class="dropdown-item">Upcoming Elections</a>
          <a href="electoral-commission.php" class="dropdown-item">Electoral Commission</a>
        </div>
      </li>

      <li class="nav-item">
        <a href="#" class="nav-link" data-dropdown="clubs" aria-haspopup="true" aria-expanded="false" aria-controls="dropdown-clubs">Clubs <span class="nav-chevron"></span></a>
        <div class="dropdown" id="dropdown-clubs">
          <a href="clubs.php" class="dropdown-item">All Clubs & Societies</a>
          <a href="clubs.php" class="dropdown-item">Register a Club</a>
        </div>
      </li>

      <li class="nav-item">
        <a href="#" class="nav-link" data-dropdown="projects" aria-haspopup="true" aria-expanded="false" aria-controls="dropdown-projects">Projects <span class="nav-chevron"></span></a>
        <div class="dropdown" id="dropdown-projects">
          <a href="projects.php" class="dropdown-item">All Projects</a>
          <a href="scholarships.php" class="dropdown-item">Scholarships</a>
        </div>
      </li>

      <li class="nav-item">
        <a href="#" class="nav-link" data-dropdown="news" aria-haspopup="true" aria-expanded="false" aria-controls="dropdown-news">News <span class="nav-chevron"></span></a>
        <div class="dropdown" id="dropdown-news">
          <a href="latest-news.php" class="dropdown-item">Latest News</a>
          <a href="events-calendar.php" class="dropdown-item">Events Calendar</a>
          <a href="press-releases.php" class="dropdown-item">Press Releases</a>
          <a href="announcements.php" class="dropdown-item">Announcements</a>
        </div>
      </li>

      <li class="nav-item">
        <a href="#" class="nav-link" data-dropdown="committees" aria-haspopup="true" aria-expanded="false" aria-controls="dropdown-committees" data-dropdown-direction="up">Committees <span class="nav-chevron"></span></a>
        <div class="dropdown" id="dropdown-committees">
          <a href="tech-innovation.php" class="dropdown-item">Tech & Innovation Committee</a>
          <a href="wealthfare_health_com.php" class="dropdown-item">Welfare & Health Committee</a>
          <a href="finance_audit_com.php" class="dropdown-item">Finance & Budget Committee</a>
          <a href="sports_com.php" class="dropdown-item">Sports Committee</a>
          <a href="entertainment_comm.php" class="dropdown-item">Entertainment Committee</a>
          <a href="editorial_com.php" class="dropdown-item">Editorial Committee</a>
          <a href="judicial_comm.php" class="dropdown-item">Judicial Committee</a>
          <a href="sponsorship_comm.php" class="dropdown-item">Sponsorship Committee</a>
          <a href="wocom_comm.php" class="dropdown-item">Women's Commission (WoCom)</a>
          <a href="adhoc_comm.php" class="dropdown-item">Ad-Hoc Committees</a>
          <a href="enterprise_comm.php" class="dropdown-item">Enterprise Committees</a>
          <a href="src_week_planning.php" class="dropdown-item">SRC Week planning Committees</a>
        </div>
      </li>

      <li class="nav-item">
        <a href="#" class="nav-link" data-dropdown="ga" aria-haspopup="true" aria-expanded="false" aria-controls="dropdown-ga" data-dropdown-direction="up">GA <span class="nav-chevron"></span></a>
        <div class="dropdown" id="dropdown-ga">
          <a href="annual-general-meeting.php" class="dropdown-item">Annual General Meeting</a>
          <a href="emergency-ga.php" class="dropdown-item">Emergency General Assembly</a>
          <a href="special-sessions.php" class="dropdown-item">Special Sessions</a>
          <a href="voting-records.php" class="dropdown-item">Voting Records</a>
          <a href="meeting-minutes.php" class="dropdown-item">Meeting Minutes</a>
          <a href="resolutions.php" class="dropdown-item">Resolutions & Motions</a>
        </div>
      </li>
      <li class="nav-item">
        <a href="downloads.php" class="nav-link" data-no-dropdown>Downloads</a>
      </li>
    </ul>

    <div class="nav-right">
      <button class="btn-portal"></button>
    </div>
  </nav>
</header>
<script defer src="assets/js/navigation.js"></script>
