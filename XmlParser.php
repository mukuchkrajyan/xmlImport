<?php
require 'ConnectDb.php';

class XmlParser extends ConnectDb
{
    public $database = "";

    public $instance;

    public $pdo;

    /*default xml file*/
    public $xmlPath = "addresseseX.xml";

    public $tables_names = array();

    public $tables = array();

    public $dom;

    public $domPath;

    /**
     * magic method __construct
     * automatic do when object created
     */
    public function __construct()
    {
        $dom = new DomDocument();

        $this->dom = $dom;

        $dom->load($this->xmlPath);

        $domPath = new DOMXPath($dom);

        $this->domPath = $domPath;

        $instance = ConnectDb::getInstance();

        $this->instance = $instance;

        $instance->open($this->xmlPath);

        $dbname = $this->getDatabaseNameFromXml();

        $instance->setDatabaseName($dbname);

        $instance->setDbCon($dbname);
        // Parent::__construct();

        $pdo = $instance->getConnection();

        $this->pdo = $pdo;
    }

    public function index()
    {
        $this->getTablesNamesFromXml();

        $this->getTablesFields($this->tables_names);

        $this->createTblsIfNotExist($this->tables);

        //$this->importXmlDataToMySql();
        $this->resetAndImportXmlDataToMySql();
    }

    /**
     * @param string $data
     * dumps data and exit
     * laravel dd function
     */
    public static function dd($data)
    {
        print_r($data);
        die;
    }

    public function getTablesFields($table_names)
    {
        $tables = array();

        foreach ($table_names as $key => $table_name) {
            $curr_table_fields_get = 0;

            $curr_table_fields = array();

            $this->instance->open($this->xmlPath);

            while ($this->instance->read()) {

                if ($this->instance->localName == $table_name) {

                    $tblNameTagReadOuterXml = new SimpleXMLElement($this->instance->readOuterXml());

                    $curr_table_name_tags_data = get_object_vars($tblNameTagReadOuterXml->children());

                    if ($curr_table_fields_get == 0) {
                        foreach ($curr_table_name_tags_data as $key => $value) {
                            $curr_table_fields[] = $key;
                        }
                        $tables[$table_name] = $curr_table_fields;

                        $curr_table_fields_get = 1;
                    }
                }
            }
        }
        //self::dd($tables);
        $this->tables = $tables;

    }

    /**
     * @param array $tables
     * Creates Tables If Not Existing yet
     */
    public function createTblsIfNotExist($tables = array())
    {
        if (count($tables) > 0) {
            foreach ($tables as $table_name => $table_fields) {

                $query = "CREATE TABLE IF NOT EXISTS `$table_name` (  `id` int(11) NOT NULL AUTO_INCREMENT,";

                foreach ($table_fields as $table_field) {
                    $query .= " `$table_field` varchar(111) NOT NULL,";
                }

                $query .= "PRIMARY KEY(`id`) ) ENGINE = MyISAM";

                $this->pdo->query($query);
            }
        }
    }

    /**
     * no params
     * nothing returns
     */
    public function getTablesNamesFromXml()
    {
        $this->instance->open($this->xmlPath);

        $tables_names = array();

        while ($this->instance->read()) {

            if (strpos($this->instance->value, "Table ")) {

                $TableTagExploded = explode("Table ", $this->instance->value);

                $curr_table_name = trim($TableTagExploded[1]);

                //$curr_table_first_data  =   $this->dom->getElementsByTagName($curr_table_name)->item(0)->innerXml;

                $tables_names[] = $curr_table_name;
            }
        }

        $this->tables_names = $tables_names;
    }


    /**
     * @param string $table_name
     * @return count rows of this table
     */
    public function getTableNumRows($table_name = "")
    {
        $count_query = "SELECT count(id) FROM `{$table_name}`";

        $sth = $this->pdo->prepare($count_query);

        $sth->execute();

        $result = $sth->fetchAll(PDO::FETCH_ASSOC);

        $count_rsult = $result[0]['count(id)'];

        return $count_rsult;

    }


    /**
     * no params
     * nothing returns
     * getting dbname
     */
    public function resetAndImportXmlDataToMySql()
    {
        if (count($this->tables_names) > 0) {

            foreach ($this->tables_names as $key => $table_name) {
                $this->resetTableData($table_name);
            }


            $this->importXmlDataToMySql();
        }
    }


    /**
     * @param string $table_name
     * reseting table
     */
    public function resetTableData($table_name = "")
    {
        $count_result = $this->getTableNumRows($table_name);

        if ($count_result > 0) {
            $query_delete = "DELETE FROM `{$table_name}` ";

            $this->pdo->query($query_delete);
        }
    }

    public function importXmlDataToMySql()
    {
        if (count($this->tables_names) > 0) {

            foreach ($this->tables_names as $key => $table_name) {

                $this->instance->open($this->xmlPath);

                while ($this->instance->read()) {

                    if ($this->instance->localName == $table_name) {

                        $tblNameTagReadOuterXml = new SimpleXMLElement($this->instance->readOuterXml());

                        $curr_table_name_tags_data = get_object_vars($tblNameTagReadOuterXml->children());

                        $numInnerItems = count($curr_table_name_tags_data);

                        $i = 0;

                        $insert_query = "INSERT INTO `$table_name`(";


                        foreach ($curr_table_name_tags_data as $key => $value) {
                            if ($i == $numInnerItems - 1) {
                                $insert_query .= "$key) VALUES (";
                            } else {
                                $insert_query .= "$key,";
                            }
                            $i++;
                        }

                        $j = 0;

                        foreach ($curr_table_name_tags_data as $key => $value) {
                            if ($j == $numInnerItems - 1) {
                                $insert_query .= "'$value' )";
                            } else {
                                $insert_query .= "'$value',";
                            }

                            $j++;
                        }


                        if ($numInnerItems > 0) {
                            $this->pdo->query($insert_query);

                        }
                    }
                }
            }
            echo "<p class='success'>successefully imported</p>";
        }
    }

    /**
     * no params
     * nothing returns
     * getting dbname
     */
    public function getDatabaseNameFromXml()
    {
        while ($this->instance->read()) {
            if (strpos($this->instance->value, "Database")) {
                $databaseTagExploded = explode("- Database: ", $this->instance->value);

                $databaseTagExplodedSecondary = explode("'", $databaseTagExploded[1]);

                $dbname = $databaseTagExplodedSecondary[1];
            }
        }
        return $dbname;

    }

    /**
     * magic method __destruct
     */
    public function __destruct()
    {
        $this->close();
    }
}


?>