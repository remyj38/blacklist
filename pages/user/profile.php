<?php include 'html/header.php'; ?>

<h2>Your profile</h2>
<?php
/* @var $auth \Blacklist\Auth\Auth */
if (!$auth->getUser()->getOtpSecret()) {
    $auth->getUser()->generateOtpSecret()->save();
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">Your OTP is now enabled for your account. You\'ll need it to connect on next login.<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
}
if (isset($_POST['password'], $_POST['confirm_password']) && $_POST['password'] == $_POST['confirm_password'] && strlen($_POST['password']) >= 8) {
    try {
        $auth->getUser()->setPassword($_POST['password'])->save();
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Your password has been updated<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
    } catch(\Exception $e) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $e->getMessage() . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
    };
}
if (isset($_POST['resetotp'])) {
    $auth->getUser()->generateOtpSecret()->save();
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Your OTP secret has been regenerated !<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
}
?>
<form action='<?php echo \Config::BASE_URL;?>/user/profile' method='POST' id='passwordForm' onsubmit="return (ValidatePassword() && ValidateConfirmation())">
    <div class="form-group">
        <label>Username</label>
        <input type="text" readonly class="form-control-plaintext" id="username" value="<?php echo $auth->getUser()->getUsername(); ?>">
    </div>
    <div class="form-group">
        <label for="password">Password</label>
        <input type="password" class="form-control" id="password" name="password" placeholder="Password" onkeyup="ValidatePassword()">
        <small id="passwordHelpBlock" class="form-text">
            Your password must be 8 characters long at least.
        </small>
    </div>
    <div class="form-group">
        <label for="confirm_password">Confirm your Password</label>
        <input type="password" class="form-control" id="confirm_password" name="confirm_password" onkeyup="ValidateConfirmation()">
        <div class="invalid-feedback" id="password_message">
            Passwords not match
        </div>
    </div>
    <button type="submit" class="btn btn-primary" id="passwordFormSubmit">Change password</button>
</form>

<h2>OTP</h2>
<form action='<?php echo \Config::BASE_URL;?>/user/profile' method="POST" class="form-inline">
    <?php
    echo '<img src="' . \Sonata\GoogleAuthenticator\GoogleQrUrl::generate($auth->getUser()->getUsername(), $auth->getUser()->getOtpSecret(), 'Blacklist') . '" />';
    ?>
    <button type="submit" name="resetotp" value="resetotp" class="btn btn-danger">Regenerate OTP secret</button>
</form>
<script>
function ValidatePassword() {
    password = $('#password')
    if (password.val().length >= 8) {
        password.addClass('is-valid').removeClass('is-invalid');
        return true
    }
    password.addClass('is-invalid').removeClass('is-valid');
    return false
}

function ValidateConfirmation() {
    confirmation = $('#confirm_password')
    if (confirmation.val() == $('#password').val()) {
        confirmation.addClass('is-valid').removeClass('is-invalid');
        return true
    }
    confirmation.addClass('is-invalid').removeClass('is-valid');
    return false
}
</script>

<?php
include 'html/footer.php';
?>
