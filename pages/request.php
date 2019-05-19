<?php
function file_upload_max_size() {
    static $max_size = -1;

    if ($max_size < 0) {
        // Start with post_max_size.
        $post_max_size = parse_size(ini_get('post_max_size'));
        if ($post_max_size > 0) {
        $max_size = $post_max_size;
        }

        // If upload_max_size is less, then reduce. Except if upload_max_size is
        // zero, which indicates no limit.
        $upload_max = parse_size(ini_get('upload_max_filesize'));
        if ($upload_max > 0 && $upload_max < $max_size) {
        $max_size = $upload_max;
        }
    }
    return $max_size;
}

function convert_filesize($bytes, $decimals = 0){
    $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}

function parse_size($size) {
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
    $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
    if ($unit) {
        // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    }
    else {
        return round($size);
    }
}


$formStatus = array(
    'submitted' => isset($_POST['submit']),
    'error' => false,
    'errors' => array(
        'first_name' => false,
        'last_name' => false,
        'email' => false,
        'message' => false,
        'email_response' => false,
        'captcha' => false,
    ),
    'mailerError' => false,
);

if (empty($_FILES) && empty($_POST) && isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
    $formStatus['error'] = true;
    $formStatus['submitted'] = true;
    $formStatus['errors']['email_response'] = "The uploaded file exceeds the maximum size. Maximum size: " . convert_filesize(file_upload_max_size());
}

if ($formStatus['submitted'] && !$formStatus['error']) {
    if (!isset($_POST['first_name']) || empty($_POST['first_name'])) {
        $formStatus['errors']['first_name'] = 'You need to provide your firstname.';
        $formStatus['error'] = true;
    }
    if (!isset($_POST['last_name']) || empty($_POST['last_name'])) {
        $formStatus['errors']['last_name'] = 'You need to provide your lastname.';
        $formStatus['error'] = true;
    }
    if (!isset($_POST['email']) || empty($_POST['email'])) {
        $formStatus['errors']['email'] = 'You need to provide your email address.';
        $formStatus['error'] = true;
    } elseif(!filter_var($_POST['email'], \FILTER_VALIDATE_EMAIL)){
        $formStatus['errors']['email'] = 'This email address is not valid.';
        $formStatus['error'] = true;
    }
    if (!isset($_POST['message']) || empty($_POST['message'])) {
        $formStatus['errors']['message'] = 'You need to say something.';
        $formStatus['error'] = true;
    }
    if (isset($_FILES['email_response'])) {
        if ($_FILES['email_response']['error'] !== \UPLOAD_ERR_OK) {
            switch ($_FILES['email_response']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $formStatus['errors']['email_response'] = "The uploaded file exceeds the maximum size. Maximum size: " . convert_filesize(file_upload_max_size());
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $formStatus['errors']['email_response'] = "The uploaded file was only partially uploaded.";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $formStatus['errors']['email_response'] = "No file was uploaded.";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                case UPLOAD_ERR_CANT_WRITE:
                case UPLOAD_ERR_EXTENSION:
                    $formStatus['errors']['email_response'] = "Internal error during file upload.";
                    break;
                default:
                    $formStatus['errors']['email_response'] = "Unknown upload error.";
                    break;
            }
            $formStatus['error'] = true;
        } elseif (!in_array(mime_content_type($_FILES['email_response']['tmp_name']), array_keys(\Config::CONTACT_CONFIG['allowed_attachments']))) {
            $formStatus['errors']['email_response'] = 'Only ' . implode(', ', \Config::CONTACT_CONFIG['allowed_attachments']) . ' are allowed.';
            $formStatus['error'] = true;
        }
    }
    if (isset($_POST['captcha_code'])){
        $image = new \Securimage();
        if (!$image->check($_POST['captcha_code'])) {
            $formStatus['errors']['captcha'] = 'Captcha failed.';
            $formStatus['error'] = true;
        }
    } else {
        $formStatus['errors']['captcha'] = 'You need to complete the captcha.';
        $formStatus['error'] = true;
    }

    if (!$formStatus['error']) { // If no errors, send the mail
        $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            // Configure PHPMailer
            if (\Config::CONTACT_CONFIG['host'] && !empty(\Config::CONTACT_CONFIG['host'])) {
                $mailer->isSMTP();
                $mailer->Host = \Config::CONTACT_CONFIG['host'];
                if (\Config::CONTACT_CONFIG['username'] && !empty(\Config::CONTACT_CONFIG['username'])) {
                    $mailer->SMTPAuth = true;
                    $mailer->Username = \Config::CONTACT_CONFIG['username'];
                    $mailer->Password = \Config::CONTACT_CONFIG['password'];
                }
                if (\Config::CONTACT_CONFIG['secure'] && !empty(\Config::CONTACT_CONFIG['secure'])) {
                    $mailer->SMTPSecure = \Config::CONTACT_CONFIG['secure'];
                }
                $mailer->Port = \Config::CONTACT_CONFIG['port'];
            }

            $mailer->setFrom(htmlspecialchars($_POST['email']), htmlspecialchars(ucfirst($_POST['first_name']) . ' ' . strtoupper($_POST['last_name'])));

            // Recipients
            foreach(\Config::CONTACT_CONFIG['recipients'] as $recipient) {
                $mailer->addAddress($recipient);
            }

            // Message attachment if uploaded
            if (isset($_FILES['email_response'])) {
                $mailer->addAttachment($_FILES['email_response']['tmp_name'], $_FILES['email_response']['name']);
            }
            $mailer->isHTML(false);
            $mailer->Subject = \Config::CONTACT_CONFIG['subject'];
            $mailer->Body = htmlspecialchars($_POST['message']);
            $mailer->send();
        } catch (\Exception $e) {
            $formStatus['mailerError'] = 'Message could not be sent. Mailer Error: '. $mailer->ErrorInfo;
        }
    }
}

include 'html/header.php';
?>
<h1><?php echo \Config::SITE_NAME; ?></h1>
<p>You certainly have found this web page because you had problems to send email to our servers.
You can contact us to have more information.</p>

<h3>Contact us</h3>
<?php
if ($formStatus['submitted'] && !$formStatus['error']) {
    if ($formStatus['mailerError']) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . $formStatus['mailerError'] . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
    } else {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">Contact form submitted successfully !<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
    }
}
?>
<form method="post" action="<?php echo \Config::BASE_URL; ?>/request" enctype="multipart/form-data">
    <div class="form-group">
        <label for="first_name">First name</label>
        <input type="text" class="form-control" id="first_name" name="first_name" required <?php if ($formStatus['submitted'] && $formStatus['error'] && isset($_POST['first_name'])) { echo 'value="' . $_POST['first_name'] . '"'; } ?>>
        <?php
        if ($formStatus['submitted'] && $formStatus['errors']['first_name']) {
            echo '<div class="form-text text-danger">
            ' . $formStatus['errors']['first_name'] . '
          </div>';
        }
        ?>
    </div>
    <div class="form-group">
        <label for="last_name">Last name</label>
        <input type="text" class="form-control" id="last_name" name="last_name" required <?php if ($formStatus['submitted'] && $formStatus['error'] && isset($_POST['last_name'])) { echo 'value="' . $_POST['last_name'] . '"'; } ?>>
        <?php
        if ($formStatus['submitted'] && $formStatus['errors']['last_name']) {
            echo '<div class="form-text text-danger">
            ' . $formStatus['errors']['last_name'] . '
          </div>';
        }
        ?>
    </div>
    <div class="form-group">
        <label for="email">Email adress</label>
        <input type="email" class="form-control" id="email" name="email" required <?php if ($formStatus['submitted'] && $formStatus['error'] && isset($_POST['email'])) { echo 'value="' . $_POST['email'] . '"'; } ?>>
        <?php
        if ($formStatus['submitted'] && $formStatus['errors']['email']) {
            echo '<div class="form-text text-danger">
            ' . $formStatus['errors']['email'] . '
          </div>';
        }
        ?>
    </div>
    <div class="mb-3">
        <label for="message">Message</label>
        <textarea class="form-control" id="message" name="message" required minlength="10"><?php if ($formStatus['submitted'] && $formStatus['error'] && isset($_POST['message'])) { echo $_POST['message']; } ?></textarea>
        <?php
        if ($formStatus['submitted'] && $formStatus['errors']['message']) {
            echo '<div class="form-text text-danger">
            ' . $formStatus['errors']['message'] . '
          </div>';
        }
        ?>
    </div>
    <div class="form-group">
        <label for="email_response">Email response from our server</label>
        <input type="file" class="form-control-file" id="email_response" name="email_response">
        <?php
        if ($formStatus['submitted'] && $formStatus['errors']['email_response']) {
            echo '<div class="form-text text-danger">
            ' . $formStatus['errors']['email_response'] . '
          </div>';
        }
        ?>
        <small class="form-text text-muted">
            Maximum size: <?php echo convert_filesize(file_upload_max_size()); ?><br>
            Allowed extensions: <?php echo implode(', ', \Config::CONTACT_CONFIG['allowed_attachments']); ?>
        </small>
    </div>
    <div class="form-group">
        <label for="captcha_code">Captcha</label>
        <div>
            <?php
            echo \Securimage::getCaptchaHtml(array('securimage_path' => \Config::BASE_URL . '/vendor/dapphp/securimage/'));
            ?>
        </div>
        <?php
        if ($formStatus['submitted'] && $formStatus['errors']['captcha']) {
            echo '<div class="form-text text-danger">
            ' . $formStatus['errors']['captcha'] . '
          </div>';
        }
        ?>
    </div>
    <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo file_upload_max_size();?>">
    <button class="btn btn-primary" type="submit" name="submit">Submit form</button>
</form>

<?php
include 'html/footer.php';
