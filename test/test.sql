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

--insert php script here to decipher the last id number from column, and then pass that id number to the follwing create table query

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

--insert php script here to decipher the last id number from column, and then pass that id number to the follwing create table queries

CREATE TABLE <[unique id].calendar> (
    patientId INT UNSIGNED NOT NULL,
    appointmentTime DATETIME,
);

CREATE TABLE <[unique id].checkinLog> (
    patientId INT UNSIGNED NOT NULL,
);