<?php

// Define an interface for creating Course objects
interface CourseFactoryInterface {
    public function createCourse($courseid, $coursename, $semester, $department);
}

// Define a concrete CourseFactory class that implements the CourseFactoryInterface
class ConcreteCourseFactory implements CourseFactoryInterface {
    // Define a private database connection property
    private $db;

    // Define a constructor that takes a database connection parameter
    public function __construct($db) {
        $this->db = $db;
    }

    // Implement the createCourse method from the CourseFactoryInterface
    public function createCourse($courseid, $coursename, $semester, $department) {
        // Prepare the query with placeholders for input
        $query = "SELECT Section.CRN, Course.CourseName, Section.Year, Section.Semester, User.Email, Section.Location
            FROM Section
            CROSS JOIN Course ON Section.Course = Course.Code
            INNER JOIN User ON Section.Instructor = User.UserID
            WHERE (CRN LIKE ? OR ?='defaultvalue!') AND
                (Semester LIKE ? OR ?='defaultvalue!') AND
                (Course LIKE ? OR ?='defaultvalue!') AND
                (CourseName LIKE ? OR ?='defaultvalue!')";

        // Prepare the statement
        $stmt = $this->db->prepare($query);

        // Bind the input parameters to the statement
        $stmt->bindValue(1, $courseid, SQLITE3_TEXT);
        $stmt->bindValue(2, $courseid, SQLITE3_TEXT);
        $stmt->bindValue(3, $semester, SQLITE3_TEXT);
        $stmt->bindValue(4, $semester, SQLITE3_TEXT);
        $stmt->bindValue(5, $department, SQLITE3_TEXT);
        $stmt->bindValue(6, $department, SQLITE3_TEXT);
        $stmt->bindValue(7, $coursename, SQLITE3_TEXT);
        $stmt->bindValue(8, $coursename, SQLITE3_TEXT);

        // Execute the query and get the results
        $results = $stmt->execute();

        // Initialize an empty array to hold the sections
        $sections = [];

        // Loop through the results and add each row to the sections array
        while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
            $sections[] = $row;
        }

        // Return the sections array
        return $sections;
    }
}

// Register the autoloader
spl_autoload_register(function ($class_name) {
    $file = __DIR__ . '/classes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Create a new database connection
$GLOBALS['dbPath'] = '../db/persistentconndb.sqlite';
    global $db;
    $db = new SQLite3($GLOBALS['dbPath'], $flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE, $encryptionKey = "");

// Create a new ConcreteCourseFactory object
$factory = new ConcreteCourseFactory($db);

// Get the course id from the request
$courseid = $_POST['courseid'];

// Get the course name from the request
$coursename = $_POST['coursename'];

// Get the semester from the request
$semester = $_POST['semester'];

// Get the department from the request
$department = $_POST['department'];

// Set default values if blank
if ($courseid == "") {
    $courseid = "defaultvalue!";
}
if ($coursename == "") {
    $coursename = "defaultvalue!";
}
if ($semester == "") {
    $semester = "defaultvalue!";
}
if ($department == "") {
    $department = "defaultvalue!";
}



$sections = $factory->createCourse($courseid,$coursename,$semester,$department);
echo json_encode($sections);
$db->close();

?>
