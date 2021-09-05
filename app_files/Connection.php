<?php

namespace TVI\Flights;

require_once "Config.php";

class Connection{

    static public function get(){        
    
        $conn = null;
        $servername   = Config::$DBSERVER;
        $dbname       = Config::$DB_NAME;
        $username     = Config::$DB_USER;
        $password     = Config::$DB_PWD;

        try {
          $conn = new \PDO("mysql:host=$servername;dbname=$dbname", $username, $password, array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
          $conn->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);
          return $conn;
        } catch (\PDOException $e) {                 
          return null;
        }
    }

}