<?php

// Singleton Desighn Pattern using

class ConnectDb extends  XMLReader{

    private static $instance = null;

    /* db params */
    private $conn;

    private $host = 'localhost';
    private $user = 'root';
    private $pass = '';
    private $dbname = '';

    /**
     * magic method __construct
     * automatic do when object created
     * The db connection is established in the  constructor.
     */
    public  function __construct()
    {
         $this->conn = new PDO("mysql:host={$this->host}", $this->user,$this->pass,
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    }

    /**
     * PDO connection set DB.
     */
    public  function setDbCon()
    {
        $this->createDbIfNotExists($this->getDatabaseName());

        $this->conn->exec("USE {$this->getDatabaseName()}");

        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $dbname = $this->getDatabaseName();

        $this->dbname   =   $dbname;

    }

    /**
     * PDO connection set DB.
     */
    public static function getInstance()
    {
        if(!self::$instance)
        {
            self::$instance = new ConnectDb();
        }

        return self::$instance;
    }


    /**
     * @param string $dbname
     * creates Db with $dbname If Not Exists yet
     */
    public function createDbIfNotExists($dbname =   NULL)
    {
        $this->conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
    }


    /**
     * returns PDO connection(private $conn)
     */
    public function getConnection()
    {
        return $this->conn;
    }



    /**
     * returns connection dbname(private property)
     */
    public function getDatabaseName()
    {
        return $this->dbname;
    }

    /**
     * returns PDO connection(private $conn)
     */
    public function setDatabaseName($dbname =   NULL)
    {
        $this->getDatabaseName();

        $this->dbname   =   $dbname;
    }

}

?>