<?php /** @var \Awesome\Frontend\App $this */
$deployedVersion = $this->getDeployedVersion();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Not Found</title>
    <link rel="shortcut icon" type="image/png" href="/pub/media/images/favicon.png"/>
    <link rel="stylesheet" type="text/css" href="/assets/css/errors.css"/>
    <script src="/lib/jquery/jquery.min.js"></script>
    <script src="/lib/jquery-ui/jquery-ui.min.js"></script>
    <script src="/assets/js/base.js"></script>
</head>
<body class="not-found-page">
    <main class="page-wrapper">
        <?php include('template/header.html'); ?>
        <div class="content">
            <h1>404 error: Not Found</h1>
            <p>Seems, page you are looking for is not present.</p>
            <p>Please start your journey from the <a href="/">Homepage</a></p>
        </div>
        <?php include('template/footer.html'); ?>
    </main>
    <script type="application/javascript">
        jQuery(function () {
            $('body').base({});
        });
    </script>
</body>
</html>