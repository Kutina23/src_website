<?php
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'models/Council.php';
require_once 'models/Staff.php';

$db = Database::getInstance();
$councilModel = new Council($db);
$staffModel = new Staff($db);

// Current academic year starts in July (e.g., 2025/2026 means July 2025 - June 2026)
$currentYear = (int)date('Y');
$currentMonth = (int)date('n');
$selectedYear = ($currentMonth >= 7) ? $currentYear : $currentYear - 1;

// Get Dean of Students profile
$dean = $staffModel->getDeanOfStudents();

    // Get council members by year
    $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.bio, u.key_responsibilities,
                u.linkedin, u.facebook, u.tiktok, u.staff_id,
                cm.position, cm.term_start, cm.term_end,
                m.file_path as profile_image_path
          FROM users u
          JOIN council_members cm ON u.id = cm.user_id
          LEFT JOIN media m ON cm.profile_image_id = m.id
          WHERE cm.is_active = TRUE
          AND YEAR(cm.term_start) <= ? 
          AND (cm.term_end IS NULL OR YEAR(cm.term_end) >= ?)
          ORDER BY cm.display_order ASC, cm.position ASC";

$executives = $db->fetchAll($sql, [$selectedYear, $selectedYear]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Profiles | SRC DHLTU</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <style>
        /* Executive Profiles Page Styles */
        main {
            margin-top: 123px;
            background: var(--navy);
            min-height: 100vh;
        }

        .profiles-hero {
            background: linear-gradient(rgba(10, 22, 40, 0.9), rgba(10, 22, 40, 0.9)), 
                        url('https://picsum.photos/seed/executive-team/1920/600') center/cover;
            padding: 80px 40px 60px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .profiles-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(201,168,76,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(201,168,76,0.04) 1px, transparent 1px);
            background-size: 60px 60px;
            animation: gridDrift 20s ease-in-out infinite alternate;
            pointer-events: none;
        }

        .profiles-hero-content {
            position: relative;
            z-index: 2;
            max-width: 900px;
            margin: 0 auto;
        }

        .profiles-hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-family: 'Space Mono', monospace;
            font-size: 10px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 20px;
        }

        .profiles-hero-tag::before,
        .profiles-hero-tag::after {
            content: '';
            width: 30px;
            height: 1px;
            background: var(--gold);
        }

        .profiles-hero h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(40px, 5vw, 72px);
            font-weight: 300;
            line-height: 1.1;
            color: var(--cream);
            margin-bottom: 16px;
        }

        .profiles-hero h1 em {
            color: var(--gold-light);
            font-style: italic;
            font-weight: 400;
        }

        .profiles-hero p {
            font-size: 16px;
            color: rgba(245,240,232,0.7);
            line-height: 1.8;
            margin-bottom: 0;
        }

        /* Dean Section */
        .dean-section {
            padding: 60px 40px;
            background: linear-gradient(135deg, rgba(201,168,76,0.08) 0%, rgba(201,168,76,0.03) 100%);
            border-bottom: 2px solid rgba(201,168,76,0.2);
            position: relative;
            overflow: hidden;
        }

        .dean-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(201,168,76,0.4), transparent);
        }

        .dean-section-label {
            text-align: center;
            font-family: 'Space Mono', monospace;
            font-size: 10px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .dean-section-label::before,
        .dean-section-label::after {
            content: '';
            width: 40px;
            height: 1px;
            background: var(--gold);
        }

        .dean-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        @media (max-width: 968px) {
            .dean-container {
                grid-template-columns: 1fr;
                gap: 40px;
            }
        }

        .dean-image-wrapper {
            position: relative;
            width: 100%;
            aspect-ratio: 3/4;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(201,168,76,0.15), rgba(30,107,74,0.1));
        }

        .dean-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transition: transform 0.6s ease;
        }

        .dean-image-wrapper:hover .dean-image {
            transform: scale(1.08);
        }

        .dean-image-fallback {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(201,168,76,0.2), rgba(30,107,74,0.15));
            font-size: 72px;
            color: rgba(201,168,76,0.3);
        }

        .dean-frame-border {
            position: absolute;
            inset: 0;
            border: 2px solid rgba(201,168,76,0.3);
            pointer-events: none;
        }

        .dean-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .dean-title {
            font-family: 'Space Mono', monospace;
            font-size: 12px;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 16px;
        }

        .dean-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 48px;
            font-weight: 600;
            color: var(--cream);
            line-height: 1.1;
            margin-bottom: 24px;
        }

        .dean-bio {
            font-size: 15px;
            color: rgba(245,240,232,0.75);
            line-height: 1.9;
            margin-bottom: 32px;
        }

        .dean-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }

        .dean-info-item {
            display: flex;
            flex-direction: column;
        }

        .dean-info-label {
            font-family: 'Space Mono', monospace;
            font-size: 10px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 8px;
        }

        .dean-info-value {
            font-size: 14px;
            color: rgba(245,240,232,0.8);
            word-break: break-word;
        }

        .dean-info-value a {
            color: var(--gold-light);
            text-decoration: none;
            transition: color var(--transition-fast);
        }

        .dean-info-value a:hover {
            color: var(--gold);
            text-decoration: underline;
        }

        .dean-office-hours {
            border-top: 1px solid rgba(201,168,76,0.2);
            padding-top: 24px;
            margin-top: 24px;
        }

        .dean-office-item {
            margin-bottom: 16px;
        }

        .dean-office-item:last-child {
            margin-bottom: 0;
        }

        .dean-office-label {
            font-family: 'Space Mono', monospace;
            font-size: 10px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 6px;
            display: block;
        }

        .dean-office-value {
            font-size: 13px;
            color: rgba(245,240,232,0.7);
        }

        /* Year Tabs */
        .profiles-controls {
            padding: 40px;
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
            background: var(--navy-mid);
            border-bottom: 1px solid rgba(201,168,76,0.1);
        }

        .year-tab {
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 12px 28px;
            border: 1px solid rgba(201,168,76,0.3);
            background: transparent;
            color: rgba(245,240,232,0.7);
            cursor: pointer;
            transition: all var(--transition-fast);
            text-decoration: none;
            display: inline-block;
        }

        .year-tab:hover {
            border-color: var(--gold);
            color: var(--gold-light);
            background: rgba(201,168,76,0.08);
        }

        .year-tab.active {
            background: linear-gradient(135deg, var(--gold-light), var(--gold));
            color: var(--navy);
            border-color: var(--gold);
        }

        /* Profiles Grid */
        .profiles-container {
            padding: 80px 40px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .profiles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 40px;
            margin-bottom: 60px;
        }

        @media (max-width: 1024px) {
            .profiles-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 32px;
            }
        }

        @media (max-width: 768px) {
            .profiles-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 24px;
            }
        }

        @media (max-width: 480px) {
            .profiles-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Profile Card */
        .profile-card {
            background: var(--navy-mid);
            border: 1px solid rgba(201,168,76,0.1);
            overflow: hidden;
            transition: all var(--transition-med);
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
            opacity: 0;
            transition: opacity var(--transition-med);
        }

        .profile-card:hover {
            border-color: rgba(201,168,76,0.3);
            background: var(--navy);
            box-shadow: 0 20px 60px rgba(201,168,76,0.1);
            transform: translateY(-8px);
        }

        .profile-card:hover::before {
            opacity: 1;
        }

        /* Profile Image */
        .profile-image-wrapper {
            position: relative;
            width: 100%;
            aspect-ratio: 3/4;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(201,168,76,0.1), rgba(30,107,74,0.05));
        }

        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transition: transform 0.5s ease;
        }

        .profile-card:hover .profile-image {
            transform: scale(1.05);
        }

        .profile-image-fallback {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(201,168,76,0.15), rgba(30,107,74,0.1));
            font-size: 48px;
            color: rgba(201,168,76,0.4);
        }

        /* Profile Content */
        .profile-content {
            padding: 32px 24px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .profile-position {
            font-family: 'Space Mono', monospace;
            font-size: 10px;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 12px;
        }

        .profile-name {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            font-weight: 600;
            color: var(--cream);
            line-height: 1.2;
            margin-bottom: 16px;
        }

        .profile-bio {
            font-size: 13px;
            color: rgba(245,240,232,0.65);
            line-height: 1.6;
            margin-bottom: 20px;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Profile Info Section */
        .profile-info-section {
            border-top: 1px solid rgba(201,168,76,0.1);
            padding-top: 16px;
            margin-top: auto;
        }

        .profile-info-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
            font-size: 12px;
        }

        .profile-info-item:last-child {
            margin-bottom: 0;
        }

        .profile-info-label {
            color: var(--gold);
            min-width: 40px;
            font-weight: 600;
        }

        .profile-info-value {
            color: rgba(245,240,232,0.7);
            word-break: break-word;
            flex-grow: 1;
        }

         .profile-info-value a {
             color: var(--gold-light);
             text-decoration: none;
             transition: color var(--transition-fast);
         }

         .profile-info-value a:hover {
             color: var(--gold);
             text-decoration: underline;
         }

         .social-links {
             display: flex;
             gap: 8px;
         }

         .social-link {
             display: flex;
             align-items: center;
             color: var(--gold-light);
             text-decoration: none;
             font-size: 14px;
             transition: color var(--transition-fast);
         }

         .social-link:hover {
             color: var(--gold);
         }

         .social-link.linkedin:hover {
             color: #0077B5;
         }

         .social-link.facebook:hover {
             color: #1877F2;
         }

         .social-link.tiktok:hover {
             color: #000000;
         }

         /* Modal Styles */
         .member-details {
             display: flex;
             gap: 24px;
         }

         .member-detail-image {
             width: 120px;
             height: 120px;
             border-radius: 8px;
             object-fit: cover;
             border: 2px solid rgba(201,168,76,0.2);
         }

         .member-detail-fallback {
             width: 120px;
             height: 120px;
             display: flex;
             align-items: center;
             justify-content: center;
             background: linear-gradient(135deg, rgba(201,168,76,0.15), rgba(30,107,74,0.1));
             font-size: 48px;
             color: rgba(201,168,76,0.4);
             border-radius: 8px;
         }

         .member-detail-info {
             flex-grow: 1;
         }

         .member-detail-info h4 {
             margin: 0 0 8px 0;
             color: var(--cream);
         }

         .member-position {
             font-family: 'Space Mono', monospace;
             font-size: 12px;
             letter-spacing: 0.1em;
             text-transform: uppercase;
             color: var(--gold);
             margin-bottom: 16px;
         }

         .member-bio {
             font-size: 14px;
             color: rgba(245,240,232,0.7);
             line-height: 1.6;
             margin-bottom: 20px;
         }

         .member-detail-contacts {
             margin-bottom: 20px;
         }

         .contact-item {
             display: flex;
             align-items: center;
             gap: 8px;
             margin-bottom: 12px;
             font-size: 13px;
         }

         .contact-item:last-child {
             margin-bottom: 0;
         }

         .contact-label {
             color: var(--gold);
             min-width: 60px;
         }

         .contact-value a {
             color: var(--gold-light);
             text-decoration: none;
         }

         .contact-value a:hover {
             color: var(--gold);
         }

         .member-detail-social {
             margin-bottom: 20px;
         }

         .member-detail-social h5 {
             font-size: 14px;
             color: var(--gold);
             margin-bottom: 12px;
         }

         .social-links {
             display: flex;
             gap: 12px;
         }

         .social-link {
             color: var(--gold-light);
             text-decoration: none;
             font-size: 14px;
             transition: color var(--transition-fast);
         }

         .social-link:hover {
             color: var(--gold);
         }

         .social-link.linkedin:hover {
             color: #0077B5;
         }

         .social-link.facebook:hover {
             color: #1877F2;
         }

         .social-link.tiktok:hover {
             color: #000000;
         }

         .member-detail-responsibilities {
             margin-bottom: 20px;
         }

         .member-detail-responsibilities h5 {
             font-size: 14px;
             color: var(--gold);
             margin-bottom: 12px;
         }

         .responsibilities-list {
             list-style: none;
             padding: 0;
             margin: 0;
         }

         .responsibilities-list li {
             padding-left: 16px;
             position: relative;
             margin-bottom: 8px;
             color: rgba(245,240,232,0.65);
             font-size: 13px;
         }

         .responsibilities-list li::before {
             content: '•';
             position: absolute;
             left: 0;
             color: var(--gold);
         }

        /* Core Responsibilities */
        .profile-responsibilities {
            border-top: 1px solid rgba(201,168,76,0.1);
            padding-top: 16px;
            margin-top: 16px;
        }

        .responsibilities-title {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 10px;
        }

        .responsibility-item {
            font-size: 12px;
            color: rgba(245,240,232,0.65);
            margin-bottom: 6px;
            padding-left: 12px;
            position: relative;
        }

        .responsibility-item::before {
            content: '•';
            position: absolute;
            left: 0;
            color: var(--gold);
        }

        .dean-responsibilities {
            border-top: 1px solid rgba(201,168,76,0.2);
            padding-top: 24px;
            margin-top: 24px;
        }

        .dean-responsibilities .responsibilities-title {
            font-size: 12px;
            margin-bottom: 12px;
        }

        .dean-responsibilities .responsibility-item {
            font-size: 14px;
            padding-left: 16px;
        }

        /* No Results */
        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
        }

        .no-results-icon {
            font-size: 64px;
            color: rgba(201,168,76,0.3);
            margin-bottom: 20px;
        }

        .no-results-text {
            font-size: 18px;
            color: rgba(245,240,232,0.6);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .profiles-hero {
                padding: 60px 20px 40px;
            }

            .dean-section {
                padding: 40px 20px;
            }

            .dean-container {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .dean-name {
                font-size: 32px;
                margin-bottom: 16px;
            }

            .dean-bio {
                font-size: 14px;
                margin-bottom: 20px;
            }

            .dean-info-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .profiles-controls {
                padding: 30px 20px;
            }

            .profiles-container {
                padding: 40px 20px;
            }

            .profile-card {
                margin: 0;
            }

            .profile-content {
                padding: 24px 16px;
            }

            .profile-name {
                font-size: 22px;
                margin-bottom: 12px;
            }
        }

        @media (max-width: 480px) {
            .profiles-hero h1 {
                font-size: 32px;
            }

            .dean-section-label {
                font-size: 9px;
            }

            .dean-section-label::before,
            .dean-section-label::after {
                width: 20px;
            }

            .dean-name {
                font-size: 24px;
            }

            .dean-bio {
                font-size: 13px;
            }

            .year-tab {
                padding: 10px 20px;
                font-size: 12px;
            }
        }

        /* Executives Section Label */
        .executives-section-label {
            text-align: center;
            font-family: 'Space Mono', monospace;
            font-size: 10px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .executives-section-label::before,
        .executives-section-label::after {
            content: '';
            width: 40px;
            height: 1px;
            background: var(--gold);
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .modal-wrap {
            background: var(--navy-mid);
            border: 1px solid rgba(201, 168, 76, 0.2);
            border-radius: 8px;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .modal-header-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px;
            border-bottom: 1px solid rgba(201, 168, 76, 0.2);
            position: sticky;
            top: 0;
            background: var(--navy-mid);
        }

        .modal-header-custom h3 {
            margin: 0;
            color: var(--cream);
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            font-weight: 600;
        }

        .modal-header-close {
            background: none;
            border: none;
            color: var(--gold);
            font-size: 28px;
            cursor: pointer;
            transition: color var(--transition-fast);
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-header-close:hover {
            color: var(--gold-light);
        }

        .modal-form-wrap {
            padding: 24px;
        }

        /* Modal Member Details */
        .member-details {
            display: flex;
            gap: 24px;
        }

        .member-detail-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid rgba(201, 168, 76, 0.2);
            flex-shrink: 0;
        }

        .member-detail-fallback {
            width: 150px;
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(201, 168, 76, 0.15), rgba(30, 107, 74, 0.1));
            font-size: 64px;
            color: rgba(201, 168, 76, 0.4);
            border-radius: 8px;
            flex-shrink: 0;
        }

        .member-detail-info {
            flex-grow: 1;
        }

        .member-detail-info h4 {
            margin: 0 0 8px 0;
            color: var(--cream);
            font-size: 24px;
        }

        .member-position {
            font-family: 'Space Mono', monospace;
            font-size: 12px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 16px;
        }

        .member-bio {
            font-size: 14px;
            color: rgba(245, 240, 232, 0.7);
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .member-detail-contacts {
            margin-bottom: 20px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            font-size: 13px;
        }

        .contact-item:last-child {
            margin-bottom: 0;
        }

        .contact-label {
            color: var(--gold);
            min-width: 60px;
            font-weight: 600;
        }

        .contact-value {
            color: rgba(245, 240, 232, 0.7);
        }

        .contact-value a {
            color: var(--gold-light);
            text-decoration: none;
            transition: color var(--transition-fast);
        }

        .contact-value a:hover {
            color: var(--gold);
            text-decoration: underline;
        }

        .member-detail-social {
            margin-bottom: 20px;
        }

        .member-detail-social h5 {
            font-size: 14px;
            color: var(--gold);
            margin: 0 0 12px 0;
            font-weight: 600;
        }

        .responsibilities-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .responsibilities-list li {
            padding-left: 16px;
            position: relative;
            margin-bottom: 8px;
            color: rgba(245, 240, 232, 0.65);
            font-size: 13px;
        }

        .responsibilities-list li::before {
            content: '•';
            position: absolute;
            left: 0;
            color: var(--gold);
        }

        .member-detail-responsibilities {
            margin-bottom: 20px;
        }

        .member-detail-responsibilities h5 {
            font-size: 14px;
            color: var(--gold);
            margin: 0 0 12px 0;
            font-weight: 600;
        }

        @media (max-width: 600px) {
            .member-details {
                flex-direction: column;
                align-items: flex-start;
            }

            .member-detail-image,
            .member-detail-fallback {
                width: 120px;
                height: 120px;
            }

            .modal-wrap {
                width: 95%;
                max-height: 95vh;
            }

            .modal-header-custom {
                padding: 16px;
            }

            .modal-header-custom h3 {
                font-size: 20px;
            }

            .modal-form-wrap {
                padding: 16px;
            }
        }
    </style>
</head>
<body>

<?php include 'include/header.php'; ?>

<main>
    <!-- Hero Section -->
    <section class="profiles-hero">
        <div class="profiles-hero-content reveal">
            <div class="profiles-hero-tag">Leadership Team</div>
            <h1>Executive <em>Profiles</em></h1>
            <p>Meet the dedicated leaders steering the Student Representative Council. Committed to excellence and championing student welfare.</p>
        </div>
    </section>

    <!-- Dean of Students Section -->
    <?php if ($dean): ?>
        <section class="dean-section reveal">
            <div class="dean-section-label">
                <span>✦ Office of Student Affairs ✦</span>
            </div>

            <div class="dean-container">
                <!-- Dean Image -->
                <div class="dean-image-wrapper">
                    <?php if (!empty($dean['profile_image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($dean['profile_image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($dean['first_name'] . ' ' . $dean['last_name']); ?>"
                             class="dean-image">
                    <?php else: ?>
                        <div class="dean-image-fallback">👨‍💼</div>
                    <?php endif; ?>
                    <div class="dean-frame-border"></div>
                </div>

                <!-- Dean Information -->
                <div class="dean-content">
                    <div class="dean-title">
                        <?php echo htmlspecialchars($dean['position']); ?>
                    </div>

                    <h2 class="dean-name">
                        <?php echo htmlspecialchars($dean['first_name'] . ' ' . $dean['last_name']); ?>
                    </h2>

                    <?php if (!empty($dean['bio'])): ?>
                        <p class="dean-bio">
                            <?php echo htmlspecialchars($dean['bio']); ?>
                        </p>
                    <?php else: ?>
                        <p class="dean-bio">Leading student affairs with a commitment to holistic development and student success. Dedicated to fostering a supportive academic environment.</p>
                    <?php endif; ?>

                    <!-- Contact Information Grid -->
                    <div class="dean-info-grid">
                        <?php if (!empty($dean['email'])): ?>
                            <div class="dean-info-item">
                                <span class="dean-info-label">Email</span>
                                <span class="dean-info-value">
                                    <a href="mailto:<?php echo htmlspecialchars($dean['email']); ?>">
                                        <?php echo htmlspecialchars($dean['email']); ?>
                                    </a>
                                </span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($dean['phone'])): ?>
                            <div class="dean-info-item">
                                <span class="dean-info-label">Phone</span>
                                <span class="dean-info-value">
                                    <a href="tel:<?php echo htmlspecialchars($dean['phone']); ?>">
                                        <?php echo htmlspecialchars($dean['phone']); ?>
                                    </a>
                                </span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($dean['staff_id'])): ?>
                            <div class="dean-info-item">
                                <span class="dean-info-label">Staff ID</span>
                                <span class="dean-info-value">
                                    <?php echo htmlspecialchars($dean['staff_id']); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Office Hours -->
                    <div class="dean-office-hours">
                        <?php if (!empty($dean['office_location'])): ?>
                            <div class="dean-office-item">
                                <span class="dean-office-label">📍 Office Location</span>
                                <span class="dean-office-value">
                                    <?php echo htmlspecialchars($dean['office_location']); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($dean['office_hours'])): ?>
                            <div class="dean-office-item">
                                <span class="dean-office-label">⏰ Office Hours</span>
                                <span class="dean-office-value">
                                    <?php echo htmlspecialchars($dean['office_hours']); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <?php if ($dean['appointment_required']): ?>
                            <div class="dean-office-item">
                                <span class="dean-office-label">📅 Appointment</span>
                                <span class="dean-office-value">Appointment Required</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($dean['key_responsibilities'])): ?>
                        <?php $deanResponsibilities = json_decode($dean['key_responsibilities'], true); ?>
                        <?php if (is_array($deanResponsibilities) && !empty($deanResponsibilities)): ?>
                            <div class="dean-responsibilities">
                                <div class="responsibilities-title">Key Responsibilities</div>
                                <?php foreach ($deanResponsibilities as $responsibility): ?>
                                    <div class="responsibility-item">
                                        <?php echo htmlspecialchars($responsibility); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Year Selection Controls -->
    <section class="profiles-controls">
        <span class="year-tab active">
            <?php echo $selectedYear; ?> / <?php echo $selectedYear + 1; ?>
        </span>
    </section>

    <!-- Profiles Grid -->
    <section class="profiles-container">
        <div class="executives-section-label">✦ Executive Council Members ✦</div>
        
        <?php if (!empty($executives)): ?>
            <div class="profiles-grid">
<?php foreach ($executives as $index => $executive): 
                    $responsibilities = !empty($executive['key_responsibilities']) 
                        ? json_decode($executive['key_responsibilities'], true) 
                        : getResponsibilities($executive['position']);
                    if (!is_array($responsibilities)) {
                        $responsibilities = getResponsibilities($executive['position']);
                    }
                    
                    // Parse key_responsibilities for modal
                    $keyResponsibilities = [];
                    if (!empty($executive['key_responsibilities'])) {
                        $parsed = json_decode($executive['key_responsibilities'], true);
                        if (is_array($parsed)) {
                            $keyResponsibilities = $parsed;
                        } else {
                            // Try to parse as plain text with asterisks or newlines
                            $lines = array_filter(array_map('trim', explode("\n", $executive['key_responsibilities'])));
                            $keyResponsibilities = [];
                            foreach ($lines as $line) {
                                $line = trim(ltrim($line, '* '));
                                if (!empty($line)) {
                                    $keyResponsibilities[] = $line;
                                }
                            }
                        }
                    }
                    
                    $memberData = [
                        'id' => $executive['id'],
                        'name' => $executive['first_name'] . ' ' . $executive['last_name'],
                        'position' => $executive['position'],
                        'bio' => $executive['bio'],
                        'email' => $executive['email'],
                        'phone' => $executive['phone'],
                        'staff_id' => $executive['staff_id'],
                        'linkedin' => $executive['linkedin'],
                        'facebook' => $executive['facebook'],
                        'tiktok' => $executive['tiktok'],
                        'key_responsibilities' => $keyResponsibilities,
                        'profile_image_path' => $executive['profile_image_path']
                    ];
                ?>
                     <article class="profile-card reveal" style="animation-delay: <?php echo min($index * 0.05, 0.3); ?>s; cursor: pointer;" 
                            onclick='openMemberDetailsModal(<?php echo json_encode($memberData); ?>)'>
                        <!-- Profile Headshot -->
                        <div class="profile-image-wrapper">
                            <?php if (!empty($executive['profile_image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($executive['profile_image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($executive['first_name'] . ' ' . $executive['last_name']); ?>"
                                     class="profile-image">
                            <?php else: ?>
                                <div class="profile-image-fallback">👤</div>
                            <?php endif; ?>
                        </div>

                        <!-- Profile Information -->
                        <div class="profile-content">
                            <!-- Position -->
                            <div class="profile-position">
                                <?php echo htmlspecialchars($executive['position']); ?>
                            </div>

                            <!-- Name -->
                            <h2 class="profile-name">
                                <?php echo htmlspecialchars($executive['first_name'] . ' ' . $executive['last_name']); ?>
                            </h2>

                            <!-- Biography -->
                            <?php if (!empty($executive['bio'])): ?>
                                <p class="profile-bio">
                                    <?php echo htmlspecialchars(substr($executive['bio'], 0, 150)); ?>
                                    <?php if (strlen($executive['bio']) > 150): ?>...<?php endif; ?>
                                </p>
                            <?php else: ?>
                                <p class="profile-bio">Dedicated SRC leader committed to student welfare and institutional excellence.</p>
                            <?php endif; ?>

                             <!-- Contact Information -->
                             <div class="profile-info-section">
                                 <!-- Email -->
                                 <?php if (!empty($executive['email'])): ?>
                                     <div class="profile-info-item">
                                         <span class="profile-info-label">Email:</span>
                                         <span class="profile-info-value">
                                             <a href="mailto:<?php echo htmlspecialchars($executive['email']); ?>">
                                                 <?php echo htmlspecialchars($executive['email']); ?>
                                             </a>
                                         </span>
                                     </div>
                                 <?php endif; ?>

                                 <!-- Phone -->
                                 <?php if (!empty($executive['phone'])): ?>
                                     <div class="profile-info-item">
                                         <span class="profile-info-label">Phone:</span>
                                         <span class="profile-info-value">
                                             <a href="tel:<?php echo htmlspecialchars($executive['phone']); ?>">
                                                 <?php echo htmlspecialchars($executive['phone']); ?>
                                             </a>
                                         </span>
                                     </div>
                                 <?php endif; ?>

                                 <!-- Social Media Links -->
                                 <?php if (!empty($executive['linkedin']) || !empty($executive['facebook']) || !empty($executive['tiktok'])): ?>
                                     <div class="profile-info-item">
                                         <span class="profile-info-label">Social:</span>
                                         <span class="profile-info-value social-links">
                                             <?php if (!empty($executive['linkedin'])): ?>
                                                 <a href="<?php echo htmlspecialchars($executive['linkedin']); ?>" target="_blank" class="social-link linkedin">
                                                     <i class="bi bi-linkedin"></i>
                                                 </a>
                                             <?php endif; ?>
                                             <?php if (!empty($executive['facebook'])): ?>
                                                 <a href="<?php echo htmlspecialchars($executive['facebook']); ?>" target="_blank" class="social-link facebook">
                                                     <i class="bi bi-facebook"></i>
                                                 </a>
                                             <?php endif; ?>
                                             <?php if (!empty($executive['tiktok'])): ?>
                                                 <a href="<?php echo htmlspecialchars($executive['tiktok']); ?>" target="_blank" class="social-link tiktok">
                                                     TikTok
                                                 </a>
                                             <?php endif; ?>
                                         </span>
                                     </div>
                                 <?php endif; ?>
                             </div>

<!-- Core Responsibilities -->
                             <?php if (!empty($responsibilities)): ?>
                                 <div class="profile-responsibilities">
                                     <div class="responsibilities-title">Key Responsibilities</div>
                                     <?php foreach ($responsibilities as $responsibility): ?>
                                         <div class="responsibility-item">
                                             <?php echo htmlspecialchars($responsibility); ?>
                                         </div>
                                     <?php endforeach; ?>
                                 </div>
                             <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="profiles-grid">
                <div class="no-results">
                    <div class="no-results-icon">🔍</div>
                    <div class="no-results-text">No executive profiles available for <?php echo $selectedYear; ?>/<?php echo $selectedYear + 1; ?></div>
                </div>
            </div>
        <?php endif; ?>
     </section>
</main>

<!-- Details Modal -->
<div id="detailsModal" class="modal-overlay">
    <div class="modal-wrap">
        <div class="modal-header-custom">
            <h3 id="modalTitle"></h3>
            <button type="button" class="modal-header-close" onclick="closeDetailsModal()">&times;</button>
        </div>
        <div class="modal-form-wrap" id="modalContent">
            <!-- Modal content will be filled dynamically -->
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>

<script>
    // Scroll reveal animation
    const revealElements = document.querySelectorAll('.reveal');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1 });

    revealElements.forEach(element => observer.observe(element));

     // Mobile menu toggle
     const mobileToggle = document.querySelector('.mobile-toggle');
     const navList = document.querySelector('.nav-list');
     
     if (mobileToggle) {
         mobileToggle.addEventListener('click', () => {
             navList.classList.toggle('active');
         });
     }
     
     // Details Modal
     const detailsModal = document.getElementById('detailsModal');
     
     function openMemberDetailsModal(memberData) {
         // Set modal title
         document.getElementById('modalTitle').textContent = memberData.name;
         
         // Set modal content
         let content = `
             <div class="member-details">
                 ${memberData.profile_image_path ? `<img src="${memberData.profile_image_path}" alt="${memberData.name}" class="member-detail-image">` : '<div class="member-detail-fallback">👤</div>'}
                 
                 <div class="member-detail-info">
                     <h4>${memberData.name}</h4>
                     <p class="member-position">${memberData.position}</p>
                     ${memberData.bio ? `<p class="member-bio">${memberData.bio.replace(/\n/g, '<br>')}</p>` : ''}
                     
                     <div class="member-detail-contacts">
                         ${memberData.email ? `<div class="contact-item"><span class="contact-label">Email:</span><a href="mailto:${memberData.email}" class="contact-value">${memberData.email}</a></div>` : ''}
                         ${memberData.phone ? `<div class="contact-item"><span class="contact-label">Phone:</span><span class="contact-value"><a href="tel:${memberData.phone}">${memberData.phone}</a></span></div>` : ''}
                         ${memberData.staff_id ? `<div class="contact-item"><span class="contact-label">Staff ID:</span><span class="contact-value">${memberData.staff_id}</span></div>` : ''}
                     </div>
                     
                      ${(memberData.linkedin || memberData.facebook || memberData.tiktok) ? `
                          <div class="member-detail-social">
                              <h5>Social Media</h5>
                              <div class="social-links">
                                  ${memberData.linkedin ? `<a href="${memberData.linkedin}" target="_blank" class="social-link linkedin"><i class="bi bi-linkedin"></i> LinkedIn</a>` : ''}
                                  ${memberData.facebook ? `<a href="${memberData.facebook}" target="_blank" class="social-link facebook"><i class="bi bi-facebook"></i> Facebook</a>` : ''}
                                  ${memberData.tiktok ? `<a href="${memberData.tiktok}" target="_blank" class="social-link tiktok">TikTok</a>` : ''}
                              </div>
                          </div>
                      ` : ''}
                     
                     ${memberData.key_responsibilities && Array.isArray(memberData.key_responsibilities) && memberData.key_responsibilities.length > 0 ? `
                         <div class="member-detail-responsibilities">
                             <h5>Key Responsibilities</h5>
                             <ul class="responsibilities-list">
                                 ${memberData.key_responsibilities.map(resp => `<li>${resp}</li>`).join('')}
                             </ul>
                         </div>
                     ` : ''}
                 </div>
             </div>
         `;
         
         document.getElementById('modalContent').innerHTML = content;
         detailsModal.style.display = 'flex';
     }
     
     function closeDetailsModal() {
         detailsModal.style.display = 'none';
     }
     
     // Close modal when clicking outside
     window.onclick = function(event) {
         if (event.target == detailsModal) {
             closeDetailsModal();
         }
     }
 </script>
</body>
</html>

<?php

/**
 * Get core responsibilities based on executive position
 */
function getResponsibilities($position) {
    $responsibilities = [
        'SRC President' => [
            'Lead SRC meetings and general assemblies',
            'Represent student interests to administration',
            'Oversee all council committees',
            'Approve council budgets and expenditures'
        ],
        'Vice President' => [
            'Assist the President in duties',
            'Preside over meetings in President\'s absence',
            'Coordinate council activities',
            'Lead special council projects'
        ],
        'General Secretary' => [
            'Record meeting minutes and resolutions',
            'Maintain official council records',
            'Prepare council correspondence',
            'Manage council documentation'
        ],
        'SRC Finance Officer' => [
            'Manage council finances and accounts',
            'Prepare financial reports',
            'Process council budget allocations',
            'Conduct financial audits'
        ],
        'SRC Organizer' => [
            'Organize council events and activities',
            'Coordinate student programs',
            'Manage event logistics',
            'Engage student body in activities'
        ],
        'PRO' => [
            'Handle public relations and communications',
            'Manage council social media presence',
            'Prepare press releases',
            'Coordinate media coverage'
        ],
        'SRC Chief Justice' => [
            'Preside over judicial proceedings',
            'Interpret council constitution',
            'Resolve disputes and grievances',
            'Ensure fair and just decisions'
        ],
        'Women\'s Commissioner' => [
            'Advocate for women\'s rights and welfare',
            'Represent women student issues',
            'Organize women-focused programs',
            'Promote gender equality initiatives'
        ],
        'Rt. Hon. Speaker' => [
            'Preside over General Assembly sessions',
            'Maintain order during meetings',
            'Ensure procedural compliance',
            'Facilitate student deliberations'
        ],
    ];

    return $responsibilities[$position] ?? [
        'Serve on council committees',
        'Participate in council activities',
        'Represent student interests',
        'Support council initiatives'
    ];
}

?>
