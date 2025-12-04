<?php

$log_user_img = 'assets/images/avatar.png';
?>

<!DOCTYPE HTML>
<html lang="en-US">

<head>
    <title><?= esc($title); ?></title>
    <meta charset="UTF-8">
    <meta name="description" content="Tehilah Global – Corporate Parent Company" />
    <meta name="keywords" content="Corporate, Global, Business, Tehilah Global" />
    <meta name="author" content="Tehilah Global" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= site_url(); ?>assets/images/logo.png" />
    <!-- <link rel="shortcut icon" href="<?= site_url(); ?>assets/images/favicon.ico" /> -->

    <!-- Google Font: Sansation -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- in head -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">


    <link rel="stylesheet" type="text/css" href="<?= site_url(); ?>assets/css/bootstrap.min.css" />
    <!-- Main CSS -->
    <link rel="stylesheet" type="text/css" href="<?= site_url(); ?>assets/css/style.css" />
</head>

<body class="page-template-onepage">

    <div class="site-wrapper">

        <div class="doc-loader">
            <img src="<?= site_url(); ?>assets/images/preloader.gif" alt="Loading">
        </div>

        <header class="header-holder">
            <div class="menu-wrapper center-relative relative">

                <!-- Logo -->
                <div class="header-logo">
                    <a href="<?= site_url(); ?>">
                        <img src="<?= site_url(); ?>assets/images/logo.png" alt="Tehilah Global Logo">
                    </a>
                </div>

                <!-- Mobile Toggle -->
                <div class="toggle-holder">
                    <div id="toggle">
                        <div class="first-menu-line"></div>
                        <div class="second-menu-line"></div>
                        <div class="third-menu-line"></div>
                    </div>
                </div>

                <!-- Main Navigation -->
                <div class="menu-holder">
                    <nav id="header-main-menu">
                        <ul class="main-menu sm sm-clean">
                            <li><a href="#home" class="text fw-semibold">Home</a></li>
                            <li><a href="#about" class="text fw-semibold">About</a></li>
                            <li><a href="#services" class="text fw-semibold">What We Do</a></li>
                            <li><a href="#model" class="text fw-semibold">Investment Model</a></li>
                            <li><a href="#portfolio" class="text fw-semibold">Ventures</a></li>
                            <li><a href="#impact" class="text fw-semibold">Global Impact</a></li>
                            <li><a href="#leadership" class="text fw-semibold">Leadership</a></li>
                            <li><a href="#contact" class="text fw-semibold">Contact</a></li>
                        </ul>
                    </nav>
                </div>

                <div class="clear"></div>
            </div>
        </header>

        <?php echo $this->renderSection('content'); ?>


    </div>
    <!-- Back to Top Button -->
    <button type="button" class="btn btn-danger btn-lg rounded-circle shadow back-to-top" id="backToTop">
        <i class="fa fa-arrow-up"></i>
    </button>

    <!-- CSS -->
    <style>
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: none;
            z-index: 999;
            background-color: #a62a26;
            /* Matches your brand red */
            color: #fff;
            transition: all 0.3s ease-in-out;
        }

        .back-to-top:hover {
            background-color: #7d1f1c;
        }
    </style>

    <!-- JS -->
    <script>
        // Show button when user scrolls down
        window.addEventListener("scroll", function () {
            let backToTop = document.getElementById("backToTop");
            if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
                backToTop.style.display = "block";
            } else {
                backToTop.style.display = "none";
            }
        });

        // Smooth scroll to top
        document.getElementById("backToTop").addEventListener("click", function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    </script>

    <!--Load JavaScript-->
    <script src="<?= site_url(); ?>assets/js/jquery.js"></script>
    <script src="<?= site_url(); ?>assets/js/jquery.sticky.js"></script>
    <script src='<?= site_url(); ?>assets/js/imagesloaded.pkgd.js'></script>
    <script src='<?= site_url(); ?>assets/js/jquery.fitvids.js'></script>
    <script src='<?= site_url(); ?>assets/js/jquery.smartmenus.min.js'></script>
    <script src='<?= site_url(); ?>assets/js/isotope.pkgd.js'></script>
    <script src='<?= site_url(); ?>assets/js/owl.carousel.min.js'></script>
    <script src='<?= site_url(); ?>assets/js/main.js'></script>

    <!-- just before closing body tag -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true
        });
    </script>
</body>

</html>