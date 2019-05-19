<?php

$errors = array();
if (isset($_GET['action'])) {
    switch($_GET['action']){
        case 'new':
            $currentEntry = new \Blacklist\Entry();
            break;
        case 'edit':
            $currentEntry = \Blacklist\Entry::findEntryById(htmlspecialchars($_GET['id']));
            if (!$currentEntry) {
                $errors[] = "Entry not found";
            }
            break;
    }
    if (isset($_POST['submit']) && !$errors){
        if ($_GET['action'] != "edit") {
            if (!isset($_POST['ip']) || empty($_POST['ip'])) {
                $errors[] = "IP is required";
            } else {
                if (\Blacklist\Entry::findEntryByIP($_POST['ip']) && $_POST['ip'] != $currentEntry->getIp()) {
                    $errors[] = "This IP is already in the database";
                } else {
                    try {
                        $currentEntry->setIp($_POST['ip']);
                    } catch (\Blacklist\IPValidateException $e) {
                        $errors[] = $e->getMessage();
                    }
                }
            }
        }
        if (isset($_POST['expiration_date']) && !empty($_POST['expiration_date'])) {
            try {
                $expirationDate = new \DateTime($_POST['expiration_date']);
            } catch (\Exception $e) {
                $errors[] = "Expiration date selected is invalid";
            }
        } else {
            $expirationDate = (new \DateTime())->setTimestamp(0);
        }
        $currentEntry->setExpirationDate($expirationDate);
        if (!$errors) {
            try {
                $currentEntry->save();
                header('Location: ' . \Config::BASE_URL . '/database/manage?success=' . $currentEntry->getIp() . ((isset($_GET['id'])) ? ' updated' : ' added') . ' with success');
                exit(0);
            } catch (\Exception $e) {
                $errors[] = "Unable to save entry:<br>".$e->getMessage();
            }
        }
    } elseif ($errors) {
        header('Location: ' . \Config::BASE_URL . '/database/manage?error=' . $errors[0]);
        exit(1);
    }
} else {
    $currentEntry = new \Blacklist\Entry();
}

include 'html/header.php';
if (isset($_GET['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_GET['error']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
}
if (isset($_GET['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_GET['success']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
}
?>

<h2>Edit Database</h2>
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
<form name="entityForm" action="<?php echo \Config::BASE_URL . '/database/manage?'. ((in_array('action', array_keys($_GET))) ? $queryString : 'action=new'); ?>" method="POST" onsubmit="return ValidateIPaddress()">
    <div class="form-group">
        <label for="ip">IP</label>
        <input type="text" class="form-control" id="ip" name="ip" value="<?php echo ($currentEntry->getId()) ? $currentEntry->getIp() : ((isset($_POST['ip'])) ? $_POST['ip'] : ''); ?>" onkeyup="ValidateIPaddress()" <?php if ($currentEntry->getId()) { echo 'readonly';} ?> autocomplete="false">
        <div class="invalid-feedback">
          Please provide a valid IP
        </div>
    </div>
    <div class="form-group">
        <label for="expiration_date">Expiration date</label>
        <input type="date" class="form-control" id="expiration_date" name="expiration_date" value="<?php echo ($currentEntry->getExpirationDate()->getTimestamp()) ? $currentEntry->getExpirationDate()->format(\Config::DATE_FORMAT) : ((isset($_POST['expiration_date'])) ? $_POST['expiration_date'] : ''); ?>">
    </div>
    <button type="submit" class="btn btn-primary" value="submit" name="submit">Save</button>
</form>

<h2>Manage Database</h2>
<?php
echo \Blacklist\Database::export('html', true, false);
?>
<script type="text/javascript">
function ValidateIPaddress() {
    ip = $('#ip')
    if (/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/.test(ip.val()))
    {
        ip.addClass('is-valid').removeClass('is-invalid');
        return true
    }
    ip.addClass('is-invalid').removeClass('is-valid');
    return false
}
window.onload = function() {
    $('tr:not(:first-child)').click(function() {
        document.location.replace('<?php echo \Config::BASE_URL . '/database/manage?action=edit&id='; ?>' + this.firstChild.textContent)
    })
}
</script>

<?php include 'html/footer.php';
