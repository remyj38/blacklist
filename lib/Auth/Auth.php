<?php

namespace Blacklist\Auth;

class Auth {

    public static $ACL = array(
        'database' => array('admin' => false, 'user' => false),
        'request' => array('admin' => false, 'user' => false),
        'database/download' => array('admin' => false, 'user' => true),
        'database/manage' => array('admin' => true, 'user' => true),
        'user/login' => array('admin' => false, 'user' => false),
        'user/logout' => array('admin' => false, 'user' => true),
        'user/manage' => array('admin' => true, 'user' => true),
        'user/profile' => array('admin' => false, 'user' => true),
    );

    /**
     * Login Level :
     * - 0: not logged
     * - 1: one factor authentified
     * - 2 fully authentified
     * @var int
     */
    private $loginLevel = 0;

    /**
     * @var User;
     */
    private $user;

    /**
     * @return $this
     */
    public function __construct() {
        $this->restoreFromSession();
        return $this;
    }

    /**
     * Test if the user is allowed to access to a page
     * @param string $page
     * @return boolean
     */
    public function isAllowed($page) {
        if (isset($this::$ACL[$page])) {
            $acl = $this::$ACL[$page];
            if (!$acl['user']) { // All users allowed
                return true;
            } else if ($this->loginLevel == 2) { // User connected
                if (!$acl['admin']) { // No admin rights needed
                    return true;
                } else if ($acl['admin'] && $this->user->isAdmin()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Authenticate the user with login and password
     * @param string $username
     * @param string $password
     * @return boolean
     */
    public function login($username, $password) {
        $user = User::findUserByUsername($username);
        if (!$user || $user->getPassword() != User::encryptPassword($password)) {
            return false;
        }
        $this
                ->setLoginLevel(1)
                ->setUser($user);
        return true;
    }

    /**
     * Authenticate user with OTP after 
     * @param string $code
     * @return boolean
     */
    public function loginOTP($code) {
        if ($this->loginLevel < 1) {
            throw new Exception('The user is not logged');
        }
        $authenticator = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();
        $this->setLoginLevel(2);
        return $authenticator->checkCode($this->user->getOtpSecret(), $code);
    }

    /**
     * Logout the user
     */
    public function logout($message = '') {
        unset($_SESSION['auth']);
        if ($message) {
            $_SESSION['message'] = $message;
        }
        header('Location: ' . \Config::BASE_URL . '/user/login');
        exit(0);
    }

    private function restoreFromSession() {
        if (!isset($_SESSION['auth'])) {
            $this->save();
            return $this;
        }
        if (isset($_SESSION['auth']['user']) && $_SESSION['auth']['loginLevel']) {
            $this->loginLevel = $_SESSION['auth']['loginLevel'];
            $this->user = User::findUserById($_SESSION['auth']['user']);
            if (!$this->user) {
                $this->logout("Your user doesn't exist anymore");
            }
        }
        return $this;
    }

    public function getLoginLevel() {
        return $this->loginLevel;
    }

    public function getUser() {
        return $this->user;
    }

    public function setLoginLevel($level) {
        $this->loginLevel = $level;
        $this->save();
        return $this;
    }

    private function setUser($user) {
        $this->user = $user;
        $this->save();
        return $this;
    }

    private function save() {
        $_SESSION['auth']['user'] = (($this->user) ? $this->user->getId() : null);
        $_SESSION['auth']['loginLevel'] = $this->loginLevel;
    }

}
