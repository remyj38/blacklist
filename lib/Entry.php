<?php

namespace Blacklist;

class Entry {

    /**
     * @var int 
     */
    private $id;

    /**
     * @var string
     */
    private $ip;

    /**
     * @var int 
     */
    private $creation_date;

    /**
     * @var int 
     */
    private $expiration_date;

    /**
     * @var string
     */
    private $creator;

    /**
     * @var string
     */
    private $updator;

    /**
     * @global \Blacklist\Auth\Auth $auth
     * @return $this
     */
    public function __construct() {
        global $auth;
        $this->creator = ($auth->getUser()) ? $auth->getUser()->getUsername() : '';
        $this->creation_date = time();
        $this->expiration_date = (new \DateTime(\Config::DEFAULT_EXPIRATION))->getTimestamp();
        return $this;
    }

    /**
     * Save the entry
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
     * Update an entry in the database
     * @global \PDO $database
     * @return $this
     * @throws \Exception
     */
    private function update() {
        global $database;
        global $auth;
        $this->updator = $auth->getUser()->getUsername();
        $query = $database->prepare('UPDATE entries SET expiration_date = :expiration_date, updator = :updator WHERE id = :id;');
        $query->bindParam(':expiration_date', $this->expiration_date, \PDO::PARAM_INT);
        $query->bindParam(':updator', $this->updator);
        $query->bindParam(':id', $this->id, \PDO::PARAM_INT);
        if (!$query->execute()) {
            throw new \Exception('Error during entry update');
        }
        return $this;
    }

    /**
     * Instert a new entry to the database
     * @global \PDO $database
     * @return $this
     * @throws \Exception
     */
    private function insert() {
        global $database;
        $query = $database->prepare('INSERT INTO entries (`ip`, `creation_date`, `expiration_date`, `creator`) VALUES (:ip, :creation_date, :expiration_date, :creator);');
        $query->bindParam(':ip', $this->ip);
        $query->bindParam(':creation_date', $this->creation_date, \PDO::PARAM_INT);
        $query->bindParam(':expiration_date', $this->expiration_date, \PDO::PARAM_INT);
        $query->bindParam(':creator', $this->creator);
        if (!$query->execute()) {
            throw new \Exception('Error during entry insert');
        }
        $this->id = $this->findEntryByIP($this->ip)->getId();
        return $this;
    }

    /**
     * Get all entries
     * @global \PDO $database
     * @return boolean | Entry[]
     */
    public static function findAll($expired = false) {
        global $database;


        $query = $database->prepare('SELECT * FROM entries;');
        $query->setFetchMode(\PDO::FETCH_CLASS|\PDO::FETCH_PROPS_LATE, self::class);
        if (!$query->execute()) {
            return false;
        }
        $result = $query->fetchAll();
        if (!$expired) {
            $result = array_filter($result, function($entry) {
                return !$entry->isExpired();
            });
        }
        return $result;
    }

    /**
     * Find an entry
     * @global \PDO $database
     * @param string $ip
     * @return $this
     */
    public static function findEntryByIP($ip) {
        global $database;

        $query = $database->prepare('SELECT * FROM entries WHERE ip = :ip LIMIT 1;');
        $query->bindParam(':ip', $ip);
        $query->setFetchMode(\PDO::FETCH_CLASS|\PDO::FETCH_PROPS_LATE, self::class);
        if (!$query->execute()) {
            return false;
        }
        return $query->fetch();
    }

    /**
     * Find an entry
     * @global \PDO $database
     * @param string $id
     * @return $this|boolean
     */
    public static function findEntryById($id) {
        global $database;
        $query = $database->prepare('SELECT * FROM entries WHERE id = :id LIMIT 1;');
        $query->bindParam(':id', $id, \PDO::PARAM_INT);
        $query->setFetchMode(\PDO::FETCH_CLASS|\PDO::FETCH_PROPS_LATE, self::class);
        if (!$query->execute()) {
            return false;
        }
        return $query->fetch();
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getIp() {
        return $this->ip;
    }

    /**
     * @return \DateTime
     */
    public function getCreationDate() {
        return (new \DateTime())->setTimestamp($this->creation_date);
    }

    /**
     * @return \DateTime
     */
    public function getExpirationDate() {
        return (new \DateTime())->setTimestamp($this->expiration_date);
    }

    /**
     * @return string
     */
    public function getCreator() {
        return $this->creator;
    }

    /**
     * @return string
     */
    public function getUpdator() {
        return $this->updator;
    }

    /**
     * @return boolean
     */
    public function isExpired() {
        if ($this->expiration_date) {
            return $this->getExpirationDate() < new \DateTime();
        } else {
            return false;
        }
    }

    /**
     * @param string $ip
     * @return $this
     * @throws IPValidateException
     */
    public function setIp($ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw(new IPValidateException($ip . " is not a valid IP address"));
        }
        $this->ip = $ip;
        return $this;
    }

    /**
     * @param \DateTime $creation_date
     * @return $this
     */
    public function setCreationDate(\DateTime $creationDate) {
        $this->creation_date = $creationDate->getTimestamp();
        return $this;
    }

    /**
     * @param \DateTime $expirationDate
     * @return $this
     */
    public function setExpirationDate(\DateTime $expirationDate) {
        $this->expiration_date = $expirationDate->getTimestamp();
        return $this;
    }

    public function setCreator($creator) {
        $this->creator = $creator;
        return $this;
    }

}

class IPValidateException extends \Exception {

}
