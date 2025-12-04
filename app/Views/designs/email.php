<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="<?= site_url(); ?>assets/front/images/spacious-beauty-logo.png">
    <title></title>
    <style>
        .body { width: 100%; color: #999; }
        .mail_wrapper { border: 1px solid #eee; margin: 10px 5px; border-radius: 10px; }
        .mail_wrapper .top { border-radius: 10px 10px 0px 0px; padding: 15px; background-color: #fcfcfc; text-align: center; }
        .mail_wrapper .top img { max-width: 80%; }
        .mail_wrapper .main { padding: 15px; }
        .mail_wrapper .bottom { border-radius: 0px 0px 10px 10px; padding: 15px; background-color: #fcfcfc; text-align: center; font-size: small; }
    </style>
</head> 

<body class="body">
    <div class="mail_wrapper">
        <div class="top">
            <img alt="" src="<?= site_url(); ?>assets/front/images/spacious-beauty-logo.png" />
        </div>

        <div class="main"><?php echo $body; ?></div>

        <div class="bottom">
            &copy; <?php echo date('Y'); ?> - <?php echo app_name; ?>
        </div>
    </div>
</body>
</html>