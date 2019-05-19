<?php

namespace Blacklist;

class Database {

    const FORMATS = array(
        'csv' => 'text/csv',
        'html' => 'text/html',
        'json' => 'application/json',
        'postfix' => 'text/plain',
    );

    /**
     * @param boolean $expired If true, include expired entries
     * @param boolean $list If true, return an array
     * @param boolean $limitedList If true, limit list values to ip and expiration date
     * @param boolean $titledArray If true, entry's array keys are field title
     */
    public function getEntries($expired = false, $list = false, $limitedList = false, $titledArray = true) {
        $entryList = Entry::findAll($expired);
        if (!$list) {
            return $entryList;
        }
        $result = array();
        if ($limitedList) {
            $fields = array(
                'getIp' => 'IP address',
                'getExpirationDate' => 'Expiration Date',
            );
        } else {
            $fields = array(
                'getId' => '#',
                'getIp' => 'IP address',
                'getCreationDate' => 'Creation Date',
                'getExpirationDate' => 'Expiration Date',
                'getCreator' => 'Created by',
                'getUpdator' => 'Updated by',
            );
        }
        foreach ($entryList as $entry) {
            $current = array();
            foreach($fields as $function => $title) {
                $value = $entry->$function();
                $key = ($titledArray) ? $title : lcfirst(\substr($function, 3));
                if ($value instanceof \DateTime) {
                    $current[$key] = ($value->getTimestamp()) ? $value->format(\Config::DATE_FORMAT) : '';
                } else {
                    $current[$key] = $value;
                }
            }
            $result[] = $current;
        }
        return $result;
    }

    /**
     * @param boolean $expired
     * @param boolean $limited Prints only IP and expiration date
     */
    public function exportToCsv($expired, $limited = true) {
        $handle = fopen('php://temp', 'r+');
        $entries = $this->getEntries($expired, true, true);
        if ($entries) {
            fwrite($handle, implode(",", array_keys($entries[0])) . PHP_EOL);
            foreach ($entries as $line) {
                fputcsv($handle, $line, ',', '"');
            }
            rewind($handle);
            $csv = "";
            while (!feof($handle)) {
                $csv .= fread($handle, 8192);
            }
            fclose($handle);
            return $csv;
        } else {
            return '';
        }
    }

    /**
     * @param boolean $expired
     * @param boolean $limited Prints only IP and expiration date
     */
    public function exportToHtml($expired, $limited = false) {
        $entries = $this->getEntries($expired, true, $limited);
        $html = "";
        if (!$entries) {
            $html .= "<table><tr><td>Nothing to show</td></tr></table>";
        } else {
            $html .= '<table class="table table-striped"><tr>';
            foreach (array_keys($entries[0]) as $title) {
                $html .= '<th scope="col">' . $title . "</th>";
            }
            $html .= "</tr>";
            foreach ($entries as $entry) {
                $html .= '<tr scope="row">';
                foreach($entry as $title => $value) {
                    $html .= "<td>" . $value . "</td>";
                }
                $html .= "</tr>";
            }
            $html .= "</table>";
        }
        return $html;
    }

    /**
     * @param boolean $expired
     * @param boolean $limited Prints only IP and expiration date
     */
    public function exportToJson($expired, $limited) {
        return json_encode($this->getEntries($expired, true, $limited, false));
    }
    
    /**
     * @param boolean $expired
     */
    public function exportToPostfix($expired) {
        $entries = $this->getEntries($expired);
        $postfixFile = "";
        foreach ($entries as $entry) {
            $postfixFile .= $entry->getIp() . "\t" . \Config::POSTFIX_CODE . "\t" . \Config::POSTFIX_MESSAGE . PHP_EOL;
        }
        return $postfixFile;
    }

    /**
     * Check if the format exists
     */
    static function formatExists($format) {
        $format = strtolower($format);
        return in_array($format, array_keys(self::FORMATS));
    }

    /**
     * Print the database to a specific format
     * @global \PDO $database
     * @param string $format
     * @param boolean $expired
     * @return boolean
     */
    static public function export($format, $expired = false, $limited = false) {
        $format = strtolower($format);
        if (!self::formatExists($format)) {
            throw new \Exception("Format not found");
        }
        global $database;
        $blacklistDB = new self();
        $format = "exportTo" . ucfirst($format);
        return $blacklistDB->$format($expired, $limited);
    }

    static public function print($format, $expired = false, $limited = false) {
        if (!self::formatExists($format)) {
            http_response_code(404);
            echo "Format not found";
            return false;
        }
        header("Content-Type: " . self::FORMATS[$format]);
        echo self::export($format, $expired, $limited);
    }

}
