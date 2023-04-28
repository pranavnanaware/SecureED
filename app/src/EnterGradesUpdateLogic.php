<?php
require_once "../src/FileUploader.php";

try {
    if (isset($_POST['submit'])) { //checks if submit var is set
        $uploader = new FileUploader($_FILES['file']);

        //validate file type
        if (!$uploader->validateFileType('csv')) {
            throw new Exception("Invalid file type. Only CSV files are allowed.");
        }

        $uploader->uploadFile();
	$GLOBALS['dbPath'] = '../db/persistentconndb.sqlite';
   	global $db;
     	$dbController = new SQLite3($GLOBALS['dbPath'], $flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE, $encryptionKey = "");
        
        $handle = fopen($uploader->getFilePath(), "r"); //sets a read-only pointer at beginning of file
        $crn = $_POST['crn']; //grabs CRN from form

        //insert data into the database
        while (($data = fgetcsv($handle, 9001, ",")) !== FALSE) { //iterate through csv
            $crn = $dbController->escapeString($crn); //sanitize the crn
            $query = "INSERT INTO Grade VALUES ('$crn', '$data[0]', '$data[1]')";//create query for db
            $dbController->exec($query);
        }

        $dbController->backup($dbController, "temp", $GLOBALS['dbPath']);
        fclose($handle);

        header("Location: ../public/dashboard.php");
    } else {
        throw new Exception("entergrades failed");
    }
} catch(Exception $e) {
    //prepare page for content
    include_once "ErrorHeader.php";

    //Display error information
    echo 'Caught exception: ',  $e->getMessage(), "<br>";
    var_dump($e->getTraceAsString());
    echo 'in ' . 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] . "<br>";

    $allVars = get_defined_vars();
    debug_zval_dump($allVars);
}
