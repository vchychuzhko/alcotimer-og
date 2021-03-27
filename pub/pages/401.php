<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= __('Unauthorized'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <link rel="shortcut icon" type="image/png" href="/media/images/favicon.png"/>
    <style>
        * {
            margin: 0;
            padding: 0;
        }

        h1 {
            padding: 20px;
        }

        p {
            padding: 5px 20px;
        }

        footer {
            background: #ffffc2;
            bottom: 0;
            padding: 10px 0;
            position: fixed;
            text-align: center;
            width: 100%;
        }
    </style>
</head>
<body class="unauthorized-index-index">
    <main class="page-wrapper">
        <div class="content">
            <h1>401: <?= __('Unauthorized'); ?></h1>
            <p><?= __('Request\'s authorization was not correct.'); ?></p>
            <p><?= __('Please, check it and try to resent your request.'); ?></p>
        </div>
    </main>
    <footer>
        <span>&copy; AwesomeTeam. <?= __('All rights reserved'); ?></span>
    </footer>
</body>
</html>
