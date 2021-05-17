<?php

#if some errors occured when adding rows to tables, this function resets them  or creates new tables
#
# -------------------------------- (all data is lost when tables are reset!!!) -------------------------------------------
#
function resetTables($reset){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("../config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #sql queries to delete and replace tables in medicalsoftware database
    $sqlQueries1 = [ 
        "DROP TABLE linkedaccounts;",
        "DROP TABLE calendar",
        "DROP TABLE checkin",
        "DROP TABLE textlog",
        "DROP TABLE allusers",
        "DROP TABLE credentials"        
    ];

    $sqlQueries2 = [
        "CREATE TABLE linkedaccounts (
            providerId INT UNSIGNED,
            p1 INT UNSIGNED,
            p2 INT UNSIGNED,
            p3 INT UNSIGNED,
            p4 INT UNSIGNED,
            p5 INT UNSIGNED,
            p6 INT UNSIGNED,
            p7 INT UNSIGNED,
            p8 INT UNSIGNED,
            p9 INT UNSIGNED,
            p10 INT UNSIGNED,
            PRIMARY KEY (providerId)
        );",
        "CREATE TABLE allUsers (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            firstName VARCHAR(100) NOT NULL,
            middleName VARCHAR(100),
            lastName VARCHAR(100) NOT NULL,
            userType VARCHAR(10) NOT NULL,
            dob DATE NOT NULL,
            email VARCHAR(100) NOT NULL,
            PRIMARY KEY (id)
        );",
        "CREATE TABLE credentials (
            id INT UNSIGNED NOT NULL,
            username VARCHAR(100),
            securePassword VARCHAR(100),
            PRIMARY KEY(id)
        );",
        "CREATE TABLE calendar ( 
            providerId INT UNSIGNED,
            p1 DATETIME,
            p2 DATETIME,
            p3 DATETIME,
            p4 DATETIME,
            p5 DATETIME,
            p6 DATETIME,
            p7 DATETIME,
            p8 DATETIME,
            p9 DATETIME,
            p10 DATETIME,
            PRIMARY KEY (providerId)
        );",
        "CREATE TABLE checkin (
            providerId INT UNSIGNED,
            p1 VARCHAR(50),
            p2 VARCHAR(50),
            p3 VARCHAR(50),
            p4 VARCHAR(50),
            p5 VARCHAR(50),
            p6 VARCHAR(50),
            p7 VARCHAR(50),
            p8 VARCHAR(50),
            p9 VARCHAR(50),
            p10 VARCHAR(50),
            PRIMARY KEY (providerId)
        );",
        "CREATE TABLE textlog (
            providerId INT UNSIGNED,
            m1 VARCHAR(50),
            m2 VARCHAR(50),
            m3 VARCHAR(50),
            m4 VARCHAR(50),
            m5 VARCHAR(50),
            m6 VARCHAR(50),
            m7 VARCHAR(50),
            m8 VARCHAR(50),
            m9 VARCHAR(50),
            m10 VARCHAR(50),
            PRIMARY KEY (providerId)
        );"
    ];

    if($reset == true){
        $sqlQueries = array_merge($sqlQueries1, $sqlQueries2);
    }
    elseif($reset == false){
        $sqlQueries = $sqlQueries2;
    }

    foreach($sqlQueries as $sql){
        try{$connection->query($sql);}
        catch(PDOException $error){echo "Error executing query: " . $error->getMessage();}
    }
}

#-------------------------------------------- functions in testing phase -------------------------------------------------------

function sqlTest($browserInput){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("../config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    

    #gather all instances where value in column matches patient's id
    $result = $connection->prepare("
        SELECT
            COLUMN_NAME, ORDINAL_POSITION, DATA_TYPE
        FROM
            INFORMATION_SCHEMA.COLUMNS
        WHERE
            TABLE_NAME = 'linkedaccounts'
        ORDER BY 2;
    ");
    $result->execute();
    $columnData = $result->fetchAll(PDO::FETCH_ASSOC);

    $search = $connection->prepare("SELECT * FROM linkedaccounts");
    $search->execute();
    $result = $search->fetchAll(PDO::FETCH_ASSOC);

    #if no linked provider account, return error
    if($columnData == NULL){
        return "error";
    }
    else{
        return print_r($result);
        
        if($result[0][$columnData[1]['COLUMN_NAME']] == NULL){
            return "Value = NULL";
        }
        return $result[0][$columnData[1]['COLUMN_NAME']];
    }
}