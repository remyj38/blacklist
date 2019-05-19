<?php
namespace Blacklist;

/**
 * SQLite connnection
 */
class DatabaseConnection {
    /**
     * PDO instance
     * @var type
     */
    private $pdo;

    /**
     * return in instance of the PDO object that connects to the SQLite database
     * @return \PDO
     * @throws \PDOException
     */
    public function connect() {
        if ($this->pdo == null) {
            try {
                $this->pdo = new \PDO("sqlite:" . \Config::DATABASE_PATH);
            } catch (PDOException $e) {
                throw $e;
            }
        }
        return $this->pdo;
    }
}
