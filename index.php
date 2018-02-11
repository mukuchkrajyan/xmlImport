<?php

//error_reporting(0);

$actual_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

require 'XmlParser.php';

require 'Distances.php';

$distances = new Distances();

class Main extends XmlParser
{

    public $distances;

    public $allowedFullIndexTypes = array("varchar", "char", "text");

    public $target_dir = "assets/xml/";

    public $target_file = "";

    public $searched_table_name = "addresses";

    public $srchTblAlwFieldsFullIndex = array();


    public $srchDistancesLess = array();

    public $srchDistancesMiddle = array();

    public $srchDistancesMoreThan = array();

    /*
     * @param Distances $distances
     * Let Use Dependency Injection
     * in __construct magic method
     */
    public function __construct($distances)
    {
        Parent::__construct();

        $this->distances = $distances;

        $this->addFullTextIndex($this->searched_table_name);
    }

    /*
     * @param $search_text,$concret_choosen
     * @return $result encoded to json
     * Search by Fullindex(match against) or primary key
     */
    public function find($search_text, $concret_choosen)
    {
        $searched_table_name = $this->searched_table_name;


        if ($concret_choosen == 0) {

            $allowedFieldsImploded = implode(",", $this->srchTblAlwFieldsFullIndex);

            /* Select match against  with all allowed Fields fulltext */
            $query = "SELECT * FROM $searched_table_name WHERE MATCH(";

            $query .= $allowedFieldsImploded;

            $query .= " ) AGAINST ('$search_text' IN BOOLEAN MODE)";

            //self::dd($query);

            $sth = $this->pdo->prepare($query);

            $sth->execute();

            $result = $sth->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($result);

        }
        else {

            $query = "SELECT * FROM $searched_table_name WHERE id=$concret_choosen";

            $sth = $this->pdo->prepare($query);

            $sth->execute();

            $result = $sth->fetchAll(PDO::FETCH_ASSOC);

            $currAddressLong = $result[0]['addresses_cord_y'];

            $currrAddressLat = $result[0]['addresses_cord_x'];

            $resultCompareWithCrbtLoc = $this->CompareWithCrntLoc($concret_choosen, $currAddressLong, $currrAddressLat);

            print_r($resultCompareWithCrbtLoc);die;
        }
    }

    public function CompareWithCrntLoc($currAddressId, $currAddressLong, $currrAddressLat)
    {
        $searched_table_name = $this->searched_table_name;

        $query = "SELECT * FROM $searched_table_name WHERE id!= $currAddressId";

        $sth = $this->pdo->prepare($query);

        $sth->execute();

        $result = $sth->fetchAll(PDO::FETCH_ASSOC);

        foreach($result as $row){

            $cmpLng = $row['addresses_cord_y'];

            $cmpLat = $row['addresses_cord_x'];

            $currDistance = $this->getDistance($currrAddressLat, $currAddressLong, $cmpLat, $cmpLng, "K");


            if ($currDistance < 5) {                                 //Distance < 5 Km

                $this->srchDistancesLess[] = array(
                    "distance" => $currDistance,

                    "id" => $row["id"],

                    "full_street_addr_name" => $row["addresses_street"] . " " . $row["addresses_address"]);

            } else if ($currDistance > 5 && $currDistance < 30) {   //Distance < 5 Km

                $this->srchDistancesMiddle[] = array(
                    "distance" => $currDistance,

                    "id" => $row["id"],

                    "full_street_addr_name" => $row["addresses_street"] . " " . $row["addresses_address"]);

            } else {                                                //Distance From 5 Km to 30Km

                $this->srchDistancesMoreThan[] = array(
                    "distance" => $currDistance,

                    "id" => $row["id"],

                    "full_street_addr_name" => $row["addresses_street"] . " " . $row["addresses_address"]);

            }
        }
        if (max(array(count($this->srchDistancesLess), count($this->srchDistancesMiddle), count($this->srchDistancesMoreThan))) == count($this->srchDistancesLess)) {
            $maxCountDistanceType = "srchDistancesLess";
        } else if (max(array(count($this->srchDistancesLess), count($this->srchDistancesMiddle), count($this->srchDistancesMoreThan))) == count($this->srchDistancesMiddle)) {
            $maxCountDistanceType = "srchDistancesMiddle";
        } else {
            $maxCountDistanceType = "srchDistancesMoreThan";
        }

        return json_encode(array("maxCountDistanceType" => $maxCountDistanceType, "srchDistancesLess" => $this->srchDistancesLess, "srchDistancesMiddle" => $this->srchDistancesMiddle, "srchDistancesMoreThan" => $this->srchDistancesMoreThan));
    }

    public function getDistance($lat1, $lon1, $lat2, $lon2, $unit)
    {
        return  $this->distances->distance($lat1, $lon1, $lat2, $lon2, $unit);
    }

    public function submit($post_array)
    {

        $xmlFile = $_FILES["xmlFile"];

        $this->target_file = $this->target_dir . basename($xmlFile["name"]);

        $uploadFileResponse = $this->uploadFile($xmlFile, $this->target_file);

        $uploadFileStatus = $uploadFileResponse["status"];

        $uploadFileMsg = $uploadFileResponse["msg"];

        $this->xmlPath = $this->target_file;

        if ($uploadFileStatus == true) {
            echo "<p class='success'>" . $uploadFileMsg . "</p>";

        } else {
            echo "<p class='err'>" . $uploadFileMsg . "</p>";
        }

        $this->index();

    }

    public function uploadFile($xmlFile, $target_file)
    {

        if (move_uploaded_file($xmlFile["tmp_name"], $target_file)) {
            $msg_response = "The file " . basename($xmlFile["name"]) . " has been uploaded.";

            $status = true;
        } else {
            $msg_response = "Sorry, there was an error uploading your file.";

            $status = false;
        }
        return array("msg" => $msg_response, "status" => $status);
    }

    public function addFullTextIndex($table_name)
    {

        $queryDescribe = "DESCRIBE  $table_name";

        $allowedFields = array();

        $allowedFullIndexTypes = $this->allowedFullIndexTypes;

        $sth = $this->pdo->prepare($queryDescribe);

        $sth->execute();

        $resultDescribe = $sth->fetchAll(PDO::FETCH_ASSOC);

        foreach ($resultDescribe as $resultDescribeCurr) {

            foreach ($allowedFullIndexTypes as $allowedFullIndexType) {

                if (strpos($resultDescribeCurr["Type"], $allowedFullIndexType) !== false) {

                    $allowedFields[] = $resultDescribeCurr["Field"];

                    break;
                }
            }
        }

        $this->srchTblAlwFieldsFullIndex = $allowedFields;

        $checkFlTxtIndexExistQuery = "select  group_concat(distinct column_name) from information_Schema.STATISTICS where table_schema = 'roomservice' and table_name = '{$table_name}' and index_type = 'FULLTEXT'";

        $sthFlTxtIndexExistQuery = $this->pdo->prepare($checkFlTxtIndexExistQuery);

        $sthFlTxtIndexExistQuery->execute();

        $rsltFlTxtIndexExistQuery = $sthFlTxtIndexExistQuery->fetchAll(PDO::FETCH_ASSOC);

        //Checks if Fullindex doesnt exist yet
        if (is_null($rsltFlTxtIndexExistQuery[0]["group_concat(distinct column_name)"])) {
            // Checks if where are allowed Fields
            if (count($allowedFields) > 0) {
                $queryFlTxtIndex = "ALTER TABLE $table_name ADD FULLTEXT INDEX `FullText` (";

                $allowedFieldsImploded = implode(",", $allowedFields);

                $queryFlTxtIndex .= $allowedFieldsImploded . ")";

                $this->pdo->query($queryFlTxtIndex);
            }
        }

    }
}


$main = new Main($distances = new Distances()); /* creates Main class object */

/*
 * importing xml file
 */
if (isset($_POST["submit"])) {

    // creates object of XmlParser
    $main = new Main($distances);

    $main->submit($_POST);

    $main->index();  // call to default index function
}


/*
 * ajax Request
 */
if (isset($_POST["search"])) {

    $concret_choosen = $_POST["concret_choosen"];

    $main = new Main($distances);

    if ($concret_choosen == 0) {
        $search_text = $_POST["search_text"];
    } else {
        $search_text = "";
    }

    $main->find($search_text, $concret_choosen);

    exit;
}
?>
<html>
<head>
    <meta charset="utf-8">

    <title>Xml import to mysql</title>

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

    <!-- jQuery library -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

    <!-- Latest compiled JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>


    <!-- Import local css/js files -->
    <link rel="stylesheet" href="assets/css/main.css">

    <script src="assets/js/main.js"></script>
</head>

<body>

<h1>Import your xml to database</h1>


<form enctype="multipart/form-data" action="" method="post">

    <div id="fileInputSexion" class="fileinput fileinput-new" data-provides="fileinput">
        <span id="chooseFile" class="btn btn-default btn-file">
            <span>Choose file</span>
            <input accept="text/xml" name="xmlFile" id="file" type="file" required/>
        </span>
    </div>

    <input type="text" id="fileName" name="fileName">

    <input list="imports" name="importType" required>

    <datalist id="imports">
        <option value="Import">
        <option value="Reset and Import">
    </datalist>

    <input class="btn btn-success" type="submit" name="submit" id="submitForm" value="Upload">

</form>


<hr>
<div class="form-group search-box-part">
    <label id="search_label" for="search_box"></label>

    <input placeholder="Search..." id="search_box" class="form-control" type="text" datasrc="<?= $actual_link; ?>">

    <img id="search_box_button" src="assets/img/serch.png"/>
</div>

<div id="searchResults" class="results">

</div>

<div id="distancesContent" class="results">

</div>

</body>
</html>
