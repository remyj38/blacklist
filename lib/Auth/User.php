<?php

namespace Blacklist\Auth;

/**
 * User class
 */
class User {

    /**
     * @var int
     */
    private $id;

    /**
     * @var string 
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $otpSecret;

    /**
     * @var boolean
     */
    private $isAdmin;

    /**
     * @return $this
     */
    public function __construct() {
        return $this;
    }

    public function delete() {
        global $database;
        $query = $database->prepare('DELETE FROM users WHERE id = :id;');
        $query->bindParam(':id', $this->id, \PDO::PARAM_INT);
        if (!$query->execute()) {
            throw new Exception('Error during user delete');
        }
        return new self;
    }

    /**
     * Save the user
     * @global \PDO $database
     * @return $this
     * @throws \Exception
     */
    public function save() {
        global $database;
        try {
            if ($this->id) {
                return $this->update();
            } else {
                return $this->insert();
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Update a user in the database
     * @global \PDO $database
     * @return $this
     * @throws Exception
     */
    private function update() {
        global $database;
        $query = $database->prepare('UPDATE users SET username = :username, `password` = :password, otpSecret = :otpSecret, isAdmin = :isAdmin WHERE id = :id;');
        $query->bindParam(':id', $this->id, \PDO::PARAM_INT);
        $query->bindParam(':isAdmin', $this->isAdmin, \PDO::PARAM_INT);
        $query->bindParam(':otpSecret', $this->otpSecret);
        $query->bindParam(':password', $this->password);
        $query->bindParam(':username', $this->username);

        if (!$query->execute()) {
            throw new Exception('Error during user update');
        }
        return $this;
    }

    /**
     * Instert a new user to the database
     * @global \PDO $database
     * @return $this
     * @throws Exception
     */
    private function insert() {
        global $database;
        $query = $database->prepare('INSERT INTO users (username, `password`, isAdmin) VALUES(:username, :password, :isAdmin);');
        $query->bindParam(':isAdmin', $this->isAdmin, \PDO::PARAM_INT);
        $query->bindParam(':password', $this->password);
        $query->bindParam(':username', $this->username);
        if (!$query->execute()) {
            throw new Exception('Error during user insert');
        }
        $this->id = $this->findUserByUsername($this->username)->getId();
        return $this;
    }

    /**
     * Get all users
     * @global \PDO $database
     * @return boolean | User[]
     */
    public static function findAll() {
        global $database;

        $query = $database->prepare('SELECT * FROM users;');
        $query->setFetchMode(\PDO::FETCH_CLASS|\PDO::FETCH_PROPS_LATE, self::class);
        if (!$query->execute()) {
            return false;
        }
        return $query->fetchAll();
    }

    /**
     * Find a user
     * @global \PDO $database
     * @param string $username
     * @return $this
     */
    public static function findUserByUsername($username) {
        global $database;

        $query = $database->prepare('SELECT * FROM users WHERE username = :username LIMIT 1;');
        $query->bindParam(':username', $username);
        $query->setFetchMode(\PDO::FETCH_CLASS|\PDO::FETCH_PROPS_LATE, self::class);
        if (!$query->execute()) {
            return false;
        }
        return $query->fetch();
    }

    /**
     * Find a user
     * @global \PDO $database
     * @param string $id
     * @return $this
     */
    public static function findUserById($id) {
        global $database;
        $query = $database->prepare('SELECT * FROM users WHERE id = :id LIMIT 1;');
        $query->bindParam(':id', $id, \PDO::PARAM_INT);
        $query->setFetchMode(\PDO::FETCH_CLASS|\PDO::FETCH_PROPS_LATE, self::class);
        if (!$query->execute()) {
            return false;
        }
        return $query->fetch();
    }

    /**
     * Encrypt the given password
     * @param string $password
     * @return string
     */
    public static function encryptPassword($password) {
        return sha1(\Config::SALT . $password . \Config::SALT);
    }

    public function getId() {
        return $this->id;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getPassword() {
        return $this->password;
    }

    public function getOtpSecret() {
        return $this->otpSecret;
    }

    public function isAdmin() {
        return $this->isAdmin;
    }

    public function setUsername($username) {
        if (User::findUserByUsername($username)) {
            throw new Exception("This username already exists");
        }
        $this->username = $username;
        return $this;
    }

    /**
     * @param string $password
     * @param boolean $force If true, skip length test
     * @throws \Exception
     */
    public function setPassword($password, $force=false) {
        if (!$force && strlen($password) < 8) {
            throw new \Exception("Password must be 8 characters long at least.");
        }
        $this->password = $this->encryptPassword($password);
        return $this;
    }

    public function generateOtpSecret() {
        $this->otpSecret = (new \Sonata\GoogleAuthenticator\GoogleAuthenticator())->generateSecret();
        return $this;
    }

    public function disableOtp() {
        $this->otpSecret = null;
        return $this;
    }

    public function setIsAdmin($isAdmin) {
        $this->isAdmin = (bool) $isAdmin;
        return $this;
    }

}
