<?php

if ($auth->getLoginLevel() == 2) {
    ?>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Blacklist</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo Config::BASE_URL; ?>/database/download">Use the database</a>
                </li>
            <?php if ($auth->isAllowed('database/manage')) { ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo Config::BASE_URL; ?>/database/manage">Manage the database</a>
                </li>
            <?php } ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo Config::BASE_URL; ?>/user/profile">Profile</a>
                </li>
            <?php if ($auth->isAllowed('user/manage')) { ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo Config::BASE_URL; ?>/user/manage">Manage users</a>
                </li>
            <?php } ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo Config::BASE_URL; ?>/user/logout">Logout</a>
                </li>
            </ul>
        </div>
    </nav>
<?php } ?>
