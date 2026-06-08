<?php
// Header component for public files - uses CSS from main.css
?>
<header>
  <!-- TOP BAR — Logo + School Name -->
  <div class="top-bar">
    <div class="top-bar-logo">
      <img src="assets/images/logo.png" alt="SRC Logo" style="height:100%;object-fit:contain;">
    </div>
    <div class="top-bar-text">
      <div class="top-bar-school">Dr. Hilla Limann Technical University</div>
      <div class="top-bar-sub">Wa, Upper West Region · Ghana</div>
    </div>
    <div class="top-bar-divider"></div>
    <div class="top-bar-src">Student Representative Council</div>
    <div class="top-bar-right">
      <span class="top-bar-tag">Session</span>
      <span class="top-bar-date"><?php echo date('Y') . ' / ' . (date('Y') + 1); ?></span>
    </div>
  </div>

  <!-- MAIN NAV — Head Menu -->
  <button class="mobile-toggle" aria-label="Toggle mobile menu">
    <span></span>
  </button>
  <nav class="main-nav">
    <ul class="nav-list">
<li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
      
    

      <li class="nav-item">
        <a href="index.php#about" class="nav-link">About <span class="nav-chevron"></span></a>
        <div class="dropdown">
          <a href="index.php#about" class="dropdown-item">Our Mission & Vision</a>
          <a href="constitution.php" class="dropdown-item">SRC Constitution</a>
          <a href="index.php#council" class="dropdown-item">Executive Council</a>
          <a href="profiles.php" class="dropdown-item">Executive Profiles</a>
        </div>
      </li>

      <li class="nav-item">
        <a href="index.php#services" class="nav-link">Services <span class="nav-chevron"></span></a>
        <div class="dropdown">
          <a href="services.php" class="dropdown-item">Our Services</a>
          <a href="marketplac.php" class="dropdown-item">SRC Market Place</a>
        </div>
      </li>

      <li class="nav-item">
        <a href="index.php#portal" class="nav-link">Portal <span class="nav-chevron"></span></a>
        <div class="dropdown">
          <a href="complaint.php" class="dropdown-item">Complaint Desk</a>
          <a href="document-request.php" class="dropdown-item">Document Request</a>
          <a href="track.php" class="dropdown-item">Track Complaint/Document</a>
          <a href="halls.php" class="dropdown-item">Halls</a>
          <a href="passq-portal.php" class="dropdown-item">PASSQ Portal</a>
        </div>
      </li>

      <li class="nav-item">
        <a href="index.php#elections" class="nav-link">Elections <span class="nav-chevron"></span></a>
        <div class="dropdown">
          <a href="upcoming-elections.php" class="dropdown-item">Upcoming Elections</a>
          <a href="electoral-commission.php" class="dropdown-item">Electoral Commission</a>
        </div>
      </li>

      <li class="nav-item">
        <a href="index.php#clubs" class="nav-link">Clubs <span class="nav-chevron"></span></a>
        <div class="dropdown">
          <a href="clubs.php" class="dropdown-item">All Clubs & Societies</a>
          <a href="clubs.php" class="dropdown-item">Register a Club</a>
        </div>
      </li>

      <li class="nav-item">
        <a href="index.php#projects" class="nav-link">Projects <span class="nav-chevron"></span></a>
        <div class="dropdown">
          <a href="projects.php" class="dropdown-item">All Projects</a>
          <a href="scholarships.php" class="dropdown-item">Scholarships</a>
         
        </div>
      </li>

      
<li class="nav-item">
         <a href="index.php#news" class="nav-link">News <span class="nav-chevron"></span></a>
         <div class="dropdown dashboard-dropdown">
           <a href="latest-news.php" class="dropdown-item dashboard-item">Latest News</a>
           <a href="events-calendar.php" class="dropdown-item dashboard-item">Events Calendar</a>
           <a href="press-releases.php" class="dropdown-item dashboard-item">Press Releases</a>
           <a href="announcements.php" class="dropdown-item dashboard-item">Announcements</a>
         </div>
       </li>

       <li class="nav-item">
         <a href="index.php#committees" class="nav-link">Committees <span class="nav-chevron"></span></a>
         <div class="dropdown" style="max-height: 300px; overflow-y: auto;">
           <a href="tech-innovation.php" class="dropdown-item">Tech & Innovation Committee</a>
           <a class="dropdown-item" href="wealthfare_health_com.php">Welfare & Health Committee</a>
           <a class="dropdown-item" href="finance_audit_com.php">Finance & Budget Committee</a>
           <a class="dropdown-item" href="sports_com.php">Sports Committee</a>
           <a class="dropdown-item" href="entertainment_comm.php">Entertainment Committee</a>
           <a class="dropdown-item" href="editorial_com.php">Editorial Committee</a>
           <a class="dropdown-item" href="judicial_comm.php">Judicial Committee</a>
           <a class="dropdown-item" href="sponsorship_comm.php">Sponsorship Committee</a>
           <a class="dropdown-item" href="wocom_comm.php">Women's Commission (WoCom)</a>
           <a class="dropdown-item" href="adhoc_comm.php">Ad-Hoc Committees</a>
           <a class="dropdown-item" href="enterprise_comm.php">Enterprise Committees</a>
           <a class="dropdown-item" href="src_week_planning.php">SRC Week planning Committees</a>
           
         </div>
       </li>

      <li class="nav-item">
        <a href="index.php#ag" class="nav-link">GA <span class="nav-chevron"></span></a>
        <div class="dropdown">
          <a href="annual-general-meeting.php" class="dropdown-item">Annual General Meeting</a>
          <a href="emergency-ga.php" class="dropdown-item">Emergency General Assembly</a>
          <a href="special-sessions.php" class="dropdown-item">Special Sessions</a>
          <a href="voting-records.php" class="dropdown-item">Voting Records</a>
          <a href="meeting-minutes.php" class="dropdown-item">Meeting Minutes</a>
          <a href="resolutions.php" class="dropdown-item">Resolutions & Motions</a>
        </div>
      </li>
      <li class="nav-item"><a href="downloads.php" class="nav-link">Downloads</a></li>
    </ul>

    <div class="nav-right">
      <button class="btn-portal"></button>
    </div>
  </nav>
</header>
