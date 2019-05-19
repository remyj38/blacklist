<?php

if ($auth->getLoginLevel() < 1) {
    if (isset($_POST['username'], $_POST['password']) && $auth->login($_POST['username'], $_POST['password'])) {
        var_dump($auth);
        header('Location: ' . \Config::BASE_URL . '/user/login');
        exit(0);
    } else {
        $content = '<h2>Login form</h2>';
        if (isset($_POST['username'])) {
            $content .= '<div class="alert alert-warning" role="alert">Username or password is invalid !</div>';
        }
        if (isset($_SESSION['message'])) {
            $content .= '<div class="alert alert-warning" role="alert">' . $_SESSION['message'] . '</div>';
            unset($_SESSION['message']);
        }
        $content .= '<form action="login" method="POST">
  <div class="form-group">
    <label for="username">Username</label>
    <input class="form-control" name="username" id="username" autocomplete="off" placeholder="Username"' . ((isset($_POST['username'])) ? 'value="' . $_POST['username'] . '"' : '') . ' required>
  </div>
  <div class="form-group">
    <label for="password">Password</label>
    <input type="password" name="password" class="form-control" id="password" autocomplete="off" required>
  </div>
  <button type="submit" class="btn btn-primary">Login</button>
</form>';
    }
} else if ($auth->getLoginLevel() < 2) {
    // If OTP is disabled for user, skiping
    if (!$auth->getUser()->getOtpSecret()) {
        $auth->setLoginLevel(2);
        header('Location: ' . \Config::BASE_URL . '/user/profile');
        exit(0);
    } else if (isset($_POST['code']) && $auth->loginOTP($_POST['code'])) {
        header('Location: ' . \Config::BASE_URL . '/user/profile');
        exit(0);
    } else {
        $content = '<h2>Login form : OTP</h2>';
        if (isset($_POST['code'])) {
            $content .= '<div class="alert alert-warning" role="alert">
  OTP authentication failed !
</div>';
        }
        $content .= '<form action="login" method="POST">
  <div class="form-group">
    <label for="code">OTP code</label>
    <input type="number" name="code" class="form-control" id="code" maxlength="6" autocomplete="off">
  </div>
  <button type="submit" class="btn btn-primary">Validate login</button>
</form>';
    }
} else {
    header('Location: ' . \Config::BASE_URL . '/database/manage');
    exit(0);
}

include 'html/header.php';
echo $content;
include 'html/footer.php';
