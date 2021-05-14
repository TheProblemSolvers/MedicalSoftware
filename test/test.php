<?php

#function that opens connection and queries SQL to MySQL medicalsoftware database
function accessMySQL(){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("../config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #add queries here to modify tables in database manually
    $sqlQueries = [
    
    "SELECT * FROM credentials WHERE username='Provider';"
    
    ];

    #send SQL code to database
    foreach($sqlQueries as $sql){
        try{$connection->query($sql);}
        catch(PDOException $error){echo "Error executing query: " . $error->getMessage();}
    }
}

#if some errors occured when adding rows to tables, this function resets them (all data is lost!!!)
function resetTables(){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("../config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #sql query to store user's data to "allusers" table
    $sqlQueries = [ 
    "CREATE TABLE calendar ('1.id' INT UNSIGNED, '1.date' DATETIME);",
    "CREATE TABLE checkin ('1.id' INT UNSIGNED, '1.date' DATETIME);",
    "CREATE TABLE linkedaccounts ('1.linked' INT UNSIGNED);",
    "CREATE TABLE textlog ('1.sender' INT UNSIGNED, '1.message' VARCHAR(20));"
    ];
    foreach($sqlQueries as $sql){
        try{$connection->query($sql);}
        catch(PDOException $error){echo "Error executing query: " . $error->getMessage();}
    }
}

#-------------------------------------------- functions in testing phase -------------------------------------------------------

#adds a new user to medicalsoftware database
function addNewUser($firstName, $lastName, $dob, $email, $username, $password, $userType){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("../config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #sql query to store user's data to "allusers" table
    $sql = "INSERT INTO allUsers (firstName, lastName, userType, dob, email) VALUES ($firstName, $lastName, $userType, $dob, $email);";
    try{$connection->query($sql);}
    catch(PDOException $error){echo "Error executing query: " . $error->getMessage();}

    #grabs the new user's id number
    $id = $connection->lastInsertId();

    #set the sql query to store user's credentials to "credentials" table
    $sql = "INSERT INTO credentials VALUES ($id, $username, $password);";
    try{$connection->query($sql);}
    catch(PDOException $error){echo "Error executing query: " . $error->getMessage();}
}

function testInsertion($testData){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("../config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #sql query to store user's data to "allusers" table
    $sql = "INSERT INTO test (text) VALUES ('" . $testData . "');";
    try{$connection->query($sql);}
    catch(PDOException $error){echo "Error executing query: " . $error->getMessage();}
}

#returns user type based on user id
function sqlUserType($id){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("../config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #gathers results from searching table into an array
    $contents = $connection->prepare("SELECT usertype FROM allusers WHERE id='$id';");
    $contents->execute();
    $data = $contents->fetch(PDO::FETCH_ASSOC);

    #returns user type as a string (either 'provider' or 'patient')
    foreach($data as $key=>$value){
        echo $key."-".$value."<br>";		
    }
    return false;
}