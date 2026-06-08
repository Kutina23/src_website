<?php
require_once 'include/header.php';
// In a real application, you would process the form data here and save to database
// For now, we'll just show a success message
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Submitted - DHLTU SRC</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .success-container {
            max-width: 600px;
            margin: 2rem auto;
            text-align: center;
            padding: 2rem;
        }
        .success-icon {
            font-size: 4rem;
            color: var(--gold);
            margin-bottom: 1.5rem;
        }
        .success-container h1 {
            color: var(--navy);
            margin-bottom: 1rem;
        }
        .success-container p {
            color: var(--text-dark);
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .btn-back {
            background: var(--gold);
            color: var(--navy);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-back:hover {
            background: var(--gold-dark);
        }
    </style>
</head>
<body>
    <div class="success-container">
        <i class="bi bi-check-circle success-icon"></i>
        <h1>Application Submitted Successfully!</h1>
        <p>Thank you for applying for the scholarship. Your application has been received and will be reviewed by the scholarship committee.</p>
        <p>You will be notified via email about the status of your application.</p>
        <a href="scholarships.php" class="btn-back">Back to Scholarships</a>
    </div>
    <?php
    require_once 'include/footer.php';
    ?>
</body>
</html>