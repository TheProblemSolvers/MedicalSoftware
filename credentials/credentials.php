<?php
#character to seperate username/password
$seperator = "*";
function validateCredentials($inputUsername, $inputPassword){
    #takes user's inputted credentials and makes a single string
    $userCredentials = $inputUsername . $GLOBALS['seperator'] . $inputPassword;
    #tries to match up created string with strings in the patient_credentials
    $fileLocation = fopen('credentials/patient_credentials.txt', 'r');
    while(!feof($fileLocation)){
        $databaseCredentials = fgets($fileLocation);
        if($userCredentials == $databaseCredentials){
            #if there is a match, direct user to patient lander page
            return 1;
        }
        else{
            #if no match, move file pointer down one line and try again
            $databaseCredentials = fgets($fileLocation);
        }
    }
    #if no match is found in the patient_credentials, then move on to provider_credentials
    $fileLocation = fopen('credentials/provider_credentials.txt', 'r');
    while(!feof($fileLocation)){
        $databaseCredentials = fgets($fileLocation);
        if($userCredentials == $databaseCredentials){
            #if there is a match, direct user to provider lander page
            return 2;
        }
        else{
            #if no match, move file pointer down one line and try again
            $databaseCredentials = fgets($fileLocation);
        }
    }
    #if no match is found, return an error message
    return "Username/password incorrect"; 
}