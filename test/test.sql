---------Create new database and initialize tables necessary--------
CREATE DATABASE medicalSoftware;

CREATE TABLE allUsers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    firstName VARCHAR(100) NOT NULL,
    middleName VARCHAR(100),
    lastName VARCHAR(100) NOT NULL,
    userType VARCHAR(10) NOT NULL,
    dob DATE NOT NULL,
    email VARCHAR(100) NOT NULL,
    PRIMARY KEY (id)
);

CREATE TABLE credentials (
    id INT UNSIGNED NOT NULL,
    username VARCHAR(100),
    securePassword VARCHAR(100),
    PRIMARY KEY(id)
);

-----------------Tables and insertions that are created when a new patient is made-----------------
INSERT INTO allUsers (firstName, lastName, userType, dob, email) VALUES ($firstName, $lastName, $userType, $dob, $email);
INSERT INTO credentials (username, securePassword) VALUES ($username, $password);

SELECT id FROM allusers;

--insert php script here to decipher the last id number from column, and then pass that id number to the follwing query

CREATE TABLE <[unique id].textLog> (
    sender INT UNSIGNED NOT NULL,
    textMessage VARCHAR(300),
    sendTime TIMESTAMP,
    PRIMARY KEY(sender)
);

-------------------Tables that are created when a new provider is made------------------
INSERT INTO allUsers (firstName, lastName, userType, dob, email) VALUES ($firstName, $lastName, $userType, $dob, $email);
INSERT INTO credentials (username, securePassword) VALUES ($username, $password);

SELECT id FROM allusers;

--insert php script here to decipher the last id number from column, and then pass that id number to the follwing queries

CREATE TABLE <[unique id].calendar> (
    patientId INT UNSIGNED NOT NULL,
    appointmentTime DATETIME,
);

CREATE TABLE <[unique id].checkinLog> (
    patientId INT UNSIGNED NOT NULL,
);

-------------------------------SQL Query for Reset Tables function---------------------------------------------

    "DROP TABLE linkedaccounts;",
    "DROP TABLE calendar",
    "DROP TABLE checkin",
    "DROP TABLE textlog",
    "DROP TABLE allusers",
    "DROP TABLE credentials",
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
        p10 INT UNSIGNED
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
    );"
