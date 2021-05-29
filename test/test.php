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
            firstname VARCHAR(100),
            middlename VARCHAR(100),
            lastname VARCHAR(100),
            dob DATE,
            height FLOAT(5),
            weight FLOAT(5),
            sex VARCHAR(15),
            currenthealth LONGTEXT,
            currentmeds LONGTEXT,
            pasthealth LONGTEXT,
            familyhealth LONGTEXT,
            notes LONGTEXT,
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
            a1 LONGTEXT,
            a2 LONGTEXT,
            a3 LONGTEXT,
            a4 LONGTEXT,
            a5 LONGTEXT,
            a6 LONGTEXT,
            a7 LONGTEXT,
            a8 LONGTEXT,
            a9 LONGTEXT,
            a10 LONGTEXT,
            PRIMARY KEY (patientId)
        );",
        "CREATE TABLE checkin (
            providerId INT UNSIGNED,
            p1 LONGTEXT,
            p2 LONGTEXT,
            p3 LONGTEXT,
            p4 LONGTEXT,
            p5 LONGTEXT,
            p6 LONGTEXT,
            p7 LONGTEXT,
            p8 LONGTEXT,
            p9 LONGTEXT,
            p10 LONGTEXT,
            PRIMARY KEY (providerId)
        );",
        "CREATE TABLE textlog (
            patientId INT UNSIGNED,
            m1 LONGTEXT,
            m2 LONGTEXT,
            m3 LONGTEXT,
            m4 LONGTEXT,
            m5 LONGTEXT,
            m6 LONGTEXT,
            m7 LONGTEXT,
            m8 LONGTEXT,
            m9 LONGTEXT,
            m10 LONGTEXT,
            m11 LONGTEXT,
            m12 LONGTEXT,
            m13 LONGTEXT,
            m14 LONGTEXT,
            m15 LONGTEXT,
            m16 LONGTEXT,
            m17 LONGTEXT,
            m18 LONGTEXT,
            m19 LONGTEXT,
            m20 LONGTEXT,
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
    $result = $connection->prepare($browserInput);
    $result->execute();
    $data = $result->fetchAll(PDO::FETCH_ASSOC);
    return print_r($data, true);
}

function test($browserInput){
    return "Call a function first";
}