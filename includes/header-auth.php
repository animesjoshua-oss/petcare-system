<?php
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Apply saved theme immediately to prevent flash of light mode -->
    <script>(function(){var t=localStorage.getItem('petcare_theme');if(t){document.documentElement.setAttribute('data-theme',t);}else if(window.matchMedia('(prefers-color-scheme: dark)').matches){document.documentElement.setAttribute('data-theme','dark');}})();</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' – ' : '' ?><?= APP_NAME ?></title>
    <meta name="description" content="PetCare+ Clinic Hub – Find veterinary clinics and grooming centers in Pangasinan, Philippines.">
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Styles -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css?v=<?= time() ?>">
    <?= isset($extraCss) ? $extraCss : '' ?>
    <style>
        /* Auth pages: no navbar, full-height centered layout */
        body { padding-top: 0 !important; }
        .auth-back-link {
            position: fixed;
            top: 1rem;
            left: 1rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .85rem;
            color: var(--text-muted);
            text-decoration: none;
            z-index: 100;
            transition: color .2s;
        }
        .auth-back-link:hover { color: var(--primary); }
    </style>
</head>
<body>



