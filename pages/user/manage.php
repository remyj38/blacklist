<?php
$users = Blacklist\Auth\User::findAll();

$errors = array();
if (isset($_GET['action'])) {
    switch($_GET['action']){
        case 'new':
            $currentUser = new \Blacklist\Auth\User();
            break;
        case 'delete':
        case 'disableotp':
        case 'edit':
            $currentUser = \Blacklist\Auth\User::findUserById(htmlspecialchars($_GET['id']));
            if (!$currentUser) {
                $errors[] = "User not found";
            }
            break;
    }
    if ($errors) {
        header('Location: ' . \Config::BASE_URL . '/user/manage?error=' . $errors[0]);
        exit(1);
    } elseif (isset($_POST['submit'])){
        if ($_GET['action'] == 'new'){
            if (!isset($_POST['username']) || empty($_POST['username'])) {
                $errors[] = "Username is required";
            } else {
                try {
                    $currentUser->setUsername($_POST['username']);
                } catch (\Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }
        if (!isset($_POST['password']) || empty($_POST['password'])) {
            if (!$currentUser->getId()) {
                $errors[] = "A password is needed";
            }
        } else {
            try {
                $currentUser->setPassword($_POST['password']);
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        $currentUser->setIsAdmin(isset($_POST['is_admin']));
        if (!$errors) {
            try {
                $currentUser->save();
                header('Location: ' . \Config::BASE_URL . '/user/manage?success=' . $currentUser->getUsername() . ((isset($_GET['id'])) ? ' updated' : ' added') . ' with success');
                exit;
            } catch (\Exception $e) {
                $errors[] = "Unable to save user:<br>".$e->getMessage();
            }
        }
    } elseif ($_GET['action'] == 'disableotp') {
        try {
            $currentUser->disableOtp()->save();
            header('Location: ' . \Config::BASE_URL . '/user/manage?success=OTP disabled for ' . $currentUser->getUsername());
        } catch (Exception $e) {
            header('Location: ' . \Config::BASE_URL . '/user/manage?error=' . $e->getMessage());
        }
    } elseif ($_GET['action'] == 'delete') {
        if ($currentUser->getId() == $auth->getUser()->getId()) {
            header('Location: ' . \Config::BASE_URL . '/user/manage?error=You can\'t delete your own user');
            exit;
        }
        try {
            $currentUser->delete();
            header('Location: ' . \Config::BASE_URL . '/user/manage?success=User ' . $currentUser->getUsername() . ' is now deleted');
        } catch (Exception $e) {
            header('Location: ' . \Config::BASE_URL . '/user/manage?error=' . $e->getMessage());
            exit;
        }
    }
} else {
    $currentUser = new \Blacklist\Auth\User();
}

include 'html/header.php';
if (isset($_GET['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_GET['error']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
}
if (isset($_GET['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_GET['success']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
}

?>

<h2>Manage users</h2>
<table class="table table-stripped">
    <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">Username</th>
            <th scope="col">Admin</th>
            <th scope="col">Disable OTP</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($users as $user) {
            echo '<tr scope="row"><td>' . $user->getId() . '</td>' .
            '<td>' . $user->getUsername() . '</td>' .
            '<td>' . (($user->isAdmin()) ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>') . '</td>' .
            '<td>' . (($user->getOtpSecret()) ? '<a href="' . \Config::BASE_URL . '/user/manage?id=' . $user->getId() . '&action=disableotp" class="btn btn-danger btn-sm">Disable OTP</a>' : 'OTP disabled') . '</td>' .
            '<td>' . (($user->getId() != $auth->getUser()->getId()) ? '<a href="' . \Config::BASE_URL . '/user/manage?id=' . $user->getId() . '&action=delete" class="btn btn-danger btn-sm" onclick="return confirm(\'Do you really want to delete ' . $user->getUsername() . '?\')">Delete User</a>' : '') . '</td></tr>';
        }
        ?>
    </tbody>
</table>

<h3>Edit User</h3>
<?php
if ($errors) {
    foreach($errors as $error) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . $error . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
    }
}
$queryString = implode('&', array_map(function ($v, $k) {
    if ($k == 'page') {
        return;
    }
    if(is_array($v)){
        return $k.'[]='.implode('&'.$k.'[]=', $v);
    }else{
        return $k.'='.$v;
    }
}, $_GET,array_keys($_GET)));
?>

<form name="userForm" action="<?php echo \Config::BASE_URL . '/user/manage?'. ((in_array('action', array_keys($_GET))) ? $queryString : 'action=new'); ?>" method="POST" onsubmit="return ValidatePassword()">
    <div class="form-group">
        <label for="ip">Username</label>
        <input type="text" class="form-control" id="username" name="username" value="<?php echo ($currentUser->getId()) ? $currentUser->getUsername() : ((isset($_POST['username'])) ? $_POST['username'] : ''); ?>" <?php if ($currentUser->getId()) { echo 'readonly';}?>>
    </div>
    <div class="form-group">
        <label for="password">Password</label>
        <input type="password" class="form-control" id="password" name="password" value="<?php echo isset($_POST['password']) ? $_POST['password'] : ''; ?>" onkeyup="ValidatePassword()">
        <small id="passwordHelpBlock">
            Your password must be 8 characters long at least.
        </small>
    </div>
    <div class="form-group form-check">
        <input type="checkbox" class="form-check-input" id="is_admin" name="is_admin" <?php if ($currentUser->isAdmin()){ echo 'checked';} ?>>
        <label class="form-check-label" for="is_admin">This user is admin</label>
    </div>
    <button type="submit" class="btn btn-primary" value="submit" name="submit">Save</button>
</form>

<script type="text/javascript">
function ValidatePassword() {
    var password = $('#password')
    if (password.val().length >= 8 <?php if ($currentUser->getId()) { echo '|| !password.val()';}?>) {
        password.addClass('is-valid').removeClass('is-invalid');
        return true
    }
    password.addClass('is-invalid').removeClass('is-valid');
    return false
}
window.onload = function() {
    $('tr:not(:first-child)').click(function() {
        document.location.replace('<?php echo \Config::BASE_URL . '/user/manage?action=edit&id='; ?>' + this.firstChild.textContent)
    })
}
</script>

<?php
include 'html/footer.php';
