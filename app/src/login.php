<?php

class SecureAccessLayer{
    private $db;

    public function __construct() {
        require_once "../src/DBController.php";
        $this->db = $db;
    }

    public function login($username, $password) {
        try {
            // Validate email format
            if (!preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/",$username)) {
                throw new Exception("Invalid email format");
            }

            // Validate password format
            if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,16}$/',$password)) {
                throw new Exception("Invalid password format. Password must be 8-16 characters long and contain at least one capital letter and one number.");
            }

            // Convert password to 80 byte hash using ripemd256 before comparing
            $hashpassword = hash("ripemd256", $password);

            $username = strtolower($username); // Make username noncase-sensitive

            $stmt = $this->db->prepare( "SELECT COUNT(*) as count FROM User WHERE Email=? AND (Password=? OR Password=?)");
            $stmt->bindValue(1, $username, SQLITE3_TEXT);
            $stmt->bindValue(2, $password, SQLITE3_TEXT);
            $stmt->bindValue(3, $hashpassword, SQLITE3_TEXT);

            $count = $stmt->execute()->fetchArray()["count"];

            // Prepare the second statement for the rows
            $stmt = $this->db->prepare("SELECT * FROM User WHERE Email=? AND (Password=? OR Password=?)");
            $stmt->bindValue(1, $username, SQLITE3_TEXT);
            $stmt->bindValue(2, $password, SQLITE3_TEXT);
            $stmt->bindValue(3, $hashpassword, SQLITE3_TEXT);

            $results = $stmt->execute();

            if ($results !== false) {
                // Query succeeded
                if (($userinfo = $results->fetchArray()) !== (null || false)) {
                    // User found
                    $error = false;
                    $acctype = $userinfo[2];
                } else {
                    // User was not found
                    $error = true;
                }
            } else {
                // Query failed
                $error = true;
            }

            // Determine if an account that met the credentials was found
            if ($count >= 1 && !$error) {
                // Login success
                session_start();
                $_SESSION["email"] = $username;
                $_SESSION["acctype"] = $acctype;

                // Redirect
                header("Location: ../public/dashboard.php");
            } else {
                // Login failed
                header("Location: ../public/index.php?login=fail");
            }

            // Note: since the database is not changed, it is not backed up
        } catch (Exception $e) {
            // Prepare page for content
            include_once "ErrorHeader.php";

            // Display error information
            echo "Caught exception: ", $e->getMessage(), "<br>";
            var_dump($e->getTraceAsString());
            echo "in " .
                "http://" .
                $_SERVER["SERVER_NAME"] .
                $_SERVER["REQUEST_URI"] .
                "<br>";
            $allVars = get_defined_vars();
            debug_zval_dump($allVars);
        }
    }
}

// Example usage
$loginController = new SecureAccessLayer();
$loginController->login($_POST["username"], $_POST["password"]);
