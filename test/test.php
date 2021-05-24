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
        "DROP TABLE calendar;",
        "DROP TABLE checkin;",
        "DROP TABLE textlog;",
        "DROP TABLE allusers;",
        "DROP TABLE patientdata;",
        "DROP TABLE credentials;"
    ];

    $sqlQueries2 = [
        "CREATE TABLE patientdata (
            relid INT UNSIGNED NOT NULL AUTO_INCREMENT,
            providerid INT UNSIGNED,
            patientid INT UNSIGNED,
            firstname VARCHAR(50),
            middlename VARCHAR(50),
            lastname VARCHAR(50),
            dob DATE,
            height FLOAT(5),
            weight FLOAT(5),
            sex VARCHAR(50),
            currenthealth VARCHAR(50),
            currentmeds VARCHAR(50),
            pasthealth VARCHAR(50),
            familyhealth VARCHAR(50),
            notes VARCHAR(50),
            PRIMARY KEY (relid)   
        );",
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
            patientId INT UNSIGNED,
            a1 DATETIME,
            a2 DATETIME,
            a3 DATETIME,
            a4 DATETIME,
            a5 DATETIME,
            a6 DATETIME,
            a7 DATETIME,
            a8 DATETIME,
            a9 DATETIME,
            a10 DATETIME,
            PRIMARY KEY (patientId)
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
            patientId INT UNSIGNED,
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
            m11 VARCHAR(50),
            m12 VARCHAR(50),
            m13 VARCHAR(50),
            m14 VARCHAR(50),
            m15 VARCHAR(50),
            m16 VARCHAR(50),
            m17 VARCHAR(50),
            m18 VARCHAR(50),
            m19 VARCHAR(50),
            m20 VARCHAR(50),
            PRIMARY KEY (patientId)
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
    $result = $connection->prepare("SELECT * FROM allusers;");
    $result->execute();
    $data = $result->fetchAll(PDO::FETCH_ASSOC);
    return print_r($data, true);
}