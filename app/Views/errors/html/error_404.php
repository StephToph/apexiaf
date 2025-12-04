<!DOCTYPE html>
<html lang="zxx">

<head>
    <!-- Meta -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="author" content="Oni Marvelous">
    <!-- Page Title -->
    <title>404 - Page not Found | <?=app_name; ?></title>
    <!-- Favicon Icon -->
    <link rel="shortcut icon" type="image/x-icon" href="<?= site_url(); ?>assets/front/images/spacious-beauty-logo.png">
    <!-- Google Fonts css-->
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&amp;family=Hanken+Grotesk:wght@400;500;600;700&amp;display=swap"
        rel="stylesheet">
    <!-- Bootstrap css -->
    <link href="<?= site_url(); ?>assets/front/css/bootstrap.min.css" rel="stylesheet" media="screen">
    <!-- SlickNav css -->
    <link href="<?= site_url(); ?>assets/front/css/slicknav.min.css" rel="stylesheet">
    <!-- Swiper css -->
    <link rel="stylesheet" href="<?= site_url(); ?>assets/front/css/swiper-bundle.min.css">
    <!-- Font Awesome icon css-->
    <link href="<?= site_url(); ?>assets/front/css/all.min.css" rel="stylesheet" media="screen">
    <!-- Animated css -->
    <link href="<?= site_url(); ?>assets/front/css/animate.css" rel="stylesheet">
    <!-- Magnific css -->
    <link href="<?= site_url(); ?>assets/front/css/magnific-popup.css" rel="stylesheet">
    <!-- Main custom css -->
    <link href="<?= site_url(); ?>assets/front/css/custom.css" rel="stylesheet" media="screen">
</head>

<body class="tt-magic-cursor">

    <!-- Preloader Start -->
    <div class="preloader">
        <div class="loading-container">
            <div class="loading"></div>
            <div id="loading-icon"><img src="<?= site_url(); ?>assets/front/images/loader.svg" alt=""></div>
        </div>
    </div>
    <!-- Preloader End -->

    <!-- Magic Cursor Start -->
    <div id="magic-cursor">
        <div id="ball"></div>
    </div>
    <!-- Magic Cursor End -->

    <!-- Header Start -->
    <header class="main-header">
        <div class="header-sticky">
            <nav class="navbar navbar-expand-lg">
                <div class="container">
                    <!-- Logo Start -->
                    <a class="navbar-brand" href="<?= site_url(); ?>">
                        <!-- <img src="./images/header-logo-spacious.svg" alt="Logo"> -->
                        <img src="<?= site_url(); ?>assets/front/images/spacious-beauty-logo.png" alt="Logo">

                    </a>
                    <!-- Logo End -->

                    <!-- Main Menu start -->
                    <div class="collapse navbar-collapse main-menu">
                        <ul class="navbar-nav mr-auto" id="menu">
                            <li class="nav-item"><a class="nav-link" href="<?= site_url(); ?>">Home</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?= site_url('services'); ?>">Services</a>
                            </li>
                            <li class="nav-item"><a class="nav-link" href="<?= site_url('contact'); ?>">Contact</a></li>
                            <li class="nav-item highlighted-menu"><a class="nav-link"
                                    href="<?= site_url('book'); ?>">Book Now</a></li>
                        </ul>
                    </div>
                    <!-- Main Menu End -->

                    <div class="navbar-toggle"></div>
                </div>
            </nav>

            <div class="responsive-menu"></div>
        </div>
    </header>
    <!-- Header End -->

    <!-- Page Header Section Start -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8 order-md-1 order-2">
                    <!-- Page Heading Start -->
                    <div class="page-header-box">
                        <h1 class="text-anime">Page Not Found</h1>
                        <!-- <ol class="breadcrumb wow fadeInUp" data-wow-delay="0.25s">
                            <li class="breadcrumb-item"><a href="<?=site_url(); ?>">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">404 Error</li>
                        </ol> -->
                    </div>
                    <!-- Page Heading End -->
                </div>

                <div class="col-md-4 order-md-2 order-1">
                    <!-- Page Header Right Icon Start -->
                    <div class="page-header-icon-box wow fadeInUp" data-wow-delay="0.5s">
                        <div class="page-header-icon">
                            <img src="<?=site_url(); ?>assets/front/images/icon-notfound.svg" alt="">
                        </div>
                    </div>
                    <!-- Page Header Right Icon End -->
                </div>
            </div>
        </div>
    </div>
    <!-- Page Header Section End -->

    <!-- Page FAQs Start -->
    <div class="page-not-found">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <!-- Page Not Found Box Start -->
                    <div class="page-not-found-box wow fadeInUp" data-wow-delay="0.25s">
                        <div class="not-found-image">
                            <img src="<?=site_url(); ?>assets/front/images/image-404.svg" alt="">
                        </div>

                        <h3>Page Not Found!</h3>
                        <p>The page you are looking for might have been removed, had its name changed,<br> or is
                            temporarily unavailable.</p>

                        <a href="<?=site_url(); ?>" class="btn-default">Back To Home</a>
                    </div>
                    <!-- Page Not Found Box End -->
                </div>
            </div>
        </div>
    </div>
    <!-- Page FAQs End -->
 <!-- Footer Start -->
 <footer class="footer">
        <!-- Footer Contact Information Section Start -->
        <div class="footer-contact-information">
            <div class="container">
                <div class="row">
                    <div class="col-md-4">
                        <!-- Footer Contact Info Box Start -->
                        <div class="contact-info-item wow fadeInUp">
                            <div class="icon-box">
                                <img src="<?=site_url(); ?>assets/front/images/icon-location.svg" alt="">
                            </div>

                            <h3>Our Location</h3>
                            <p>25 20th Street Brandon MB Address</p>
                        </div>
                        <!-- Footer Contact Info Box End -->
                    </div>

                    <div class="col-md-4">
                        <!-- Footer Contact Info Box Start -->
                        <div class="contact-info-item wow fadeInUp" data-wow-delay="0.25s">
                            <div class="icon-box">
                                <img src="<?=site_url(); ?>assets/front/images/icon-email-phone.svg" alt="">
                            </div>

                            <h3>Get in Touch</h3>
                            <p>Phone: +1 (204) 720-6353<br>Email: festuspatience2016@gmail.com</p>
                        </div>
                        <!-- Footer Contact Info Box End -->
                    </div>

                    <div class="col-md-4">
                        <!-- Footer Contact Info Box Start -->
                        <div class="contact-info-item wow fadeInUp" data-wow-delay="0.5s">
                            <div class="icon-box">
                                <img src="<?=site_url(); ?>assets/front/images/icon-working-hours.svg" alt="">
                            </div>

                            <h3>Working Hours</h3>
                            <p>Mon-Fri: 10:00 AM - 9:00 PM <br>Saturday: 10:00 AM - 7:00 PM <br>Sunday: 10:00 PM - 7:00
                                PM</p>
                        </div>
                        <!-- Footer Contact Info Box End -->
                    </div>
                </div>
            </div>
        </div>
        <!-- Footer Contact Information Section End -->

        <!-- Main Footer Start -->
        <div class="footer-main">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-5">
                        <!-- Footer Logo Start -->
                        <div class="footer-logo">
                            <img src="<?=site_url(); ?>assets/front/images/footer-logo-spacious.svg" alt="">
                        </div>
                        <!-- Footer Logo End -->

                        <!-- Footer Social Icons Start -->
                        <div class="footer-social">
                            <ul>
                                <li><a href="#"><i class="fab fa-facebook-f"></i></a></li>
                                <li><a href="#"><i class="fab fa-instagram"></i></a></li>
                                <li><a href="#"><i class="fab fa-twitter"></i></a></li>
                                <li><a href="#"><i class="fab fa-linkedin-in"></i></a></li>
                            </ul>
                        </div>
                        <!-- Footer Social Icons End -->
                    </div>

                    <div class="col-lg-7">
                        <!-- Footer Menu Start -->
                        <div class="footer-menu">
                            <ul>
                                <li><a href="<?=site_url(); ?>">Home</a></li>
                                <li><a href="<?=site_url('services'); ?>">Services</a></li>
                                <li><a href="<?=site_url('contact'); ?>">Contact Us</a></li>
                                <li><a href="<?=site_url( 'login'); ?>"><?=app_name; ?></a></li>
                            </ul>
                        </div>
                        <!-- Footer Menu End -->

                        <!-- Footer Copyright Start -->
                        <div class="copyright">
                            <p>Copyright &copy; <?=date('Y'); ?> <?=app_name; ?>. All Rights Reserved.</p>
                        </div>
                        <!-- Footer Copyright End -->
                    </div>
                </div>
            </div>
        </div>
        <!-- Main Footer End -->
    </footer>
    <!-- Footer End -->

    <!-- Jquery Library File -->
    <script src="<?=site_url(); ?>assets/front/js/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap js file -->
    <script src="<?=site_url(); ?>assets/front/js/bootstrap.min.js"></script>
    <!-- Validator js file -->
    <script src="<?=site_url(); ?>assets/front/js/validator.min.js"></script>
    <!-- SlickNav js file -->
    <script src="<?=site_url(); ?>assets/front/js/jquery.slicknav.js"></script>
    <!-- Swiper js file -->
    <script src="<?=site_url(); ?>assets/front/js/swiper-bundle.min.js"></script>
    <!-- Counter js file -->
    <script src="<?=site_url(); ?>assets/front/js/jquery.waypoints.min.js"></script>
    <script src="<?=site_url(); ?>assets/front/js/jquery.counterup.min.js"></script>
    <!-- Magnific js file -->
    <script src="<?=site_url(); ?>assets/front/js/jquery.magnific-popup.min.js"></script>
    <!-- SmoothScroll -->
    <script src="<?=site_url(); ?>assets/front/js/SmoothScroll.js"></script>
    <!-- MagicCursor js file -->
    <script src="<?=site_url(); ?>assets/front/js/gsap.min.js"></script>
    <script src="<?=site_url(); ?>assets/front/js/magiccursor.js"></script>
    <!-- Text Effect js file -->
    <script src="<?=site_url(); ?>assets/front/js/splitType.js"></script>
    <script src="<?=site_url(); ?>assets/front/js/ScrollTrigger.min.js"></script>
    <!-- Wow js file -->
    <script src="<?=site_url(); ?>assets/front/js/wow.js"></script>
    <!-- Main Custom js file -->
    <script src="<?=site_url(); ?>assets/front/js/function.js"></script>
</body>

</html>