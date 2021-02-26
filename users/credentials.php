<?php

#character to seperate username/password
$seperator = "*";

#takes users inputted credentials, combines it into string, and returns to caller
function convertInputCredentials($inputUsername, $inputPassword){
    $userCredentials = $inputUsername . $GLOBALS['seperator'] . $inputPassword;
    return $userCredentials;
}

#returns all of the patient credential combinations to calling page
function testFileRead(){
    $fileLocation = fopen('users/patient_credentials.txt', 'r');
    $databaseCredentials = fgets($fileLocation);
    while(feof($fileLocation) == false){
        #loop through all valid credentials in database and compile into string
        $databaseCredentials = $databaseCredentials . fgets($fileLocation);
    }  
    return $databaseCredentials;
}

#compares users credentils to credential combinations in database
function validateCredentials($inputUsername, $inputPassword){
    #takes user's inputted credentials and makes a single string
    $userCredentials = $inputUsername . $GLOBALS['seperator'] . $inputPassword;
    #tries to match up created string with strings in the patient_credentials
    $fileLocation = fopen('users/credentials.txt', 'r');
    while(feof($fileLocation) == false){
        $databaseCredentials = fgets($fileLocation);
        if($userCredentials == trim($databaseCredentials)){
            #if there is a match, send user to correct side of software
            return trim(fgets($fileLocation));
        }
    }
    #close the file
    fclose($fileLocation);
    #let caller know there was no credentials match
    return false;
}