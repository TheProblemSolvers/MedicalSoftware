<?php

#accesses correct user database
function accessUserDatabase($userId){
    $fileName = "../users\user_data\#" . $userId . ".txt";
    $fileHandle = fopen($fileName, "r");
    return $fileHandle;
}

#returns the type of information contained in a line of data from user's database
function getDataType($lineContents){
    $startRead = strpos($lineContents, ".") + 1;
    $endRead = strpos($lineContents, "=");
    return substr($lineContents, $startRead, ($endRead - $startRead));
}

#reads patient identification number from a given string
function getPatientId($lineContents){
    $startRead = strpos($lineContents, "p") + 1;
    $endRead = strpos($lineContents, ".");
    $patientId = substr($lineContents, $startRead, ($endRead - $startRead));
    return $patientId;
}

#gets patients first and last name based on patients id
function patientFullName($userId, $patientId){
    $fileHandle = accessUserDatabase($userId);
    $lineContents = getPatientData($fileHandle);
    while(feof($fileHandle) == false){
        if($patientId == getPatientId($lineContents)){
            $startRead = strpos($lineContents, "=") + 1;
            $firstName = substr($lineContents, $startRead);
            $lineContents = fgets($fileHandle);
            $startRead = strpos($lineContents, "=") + 1;
            $lastName = substr($lineContents, $startRead);
            return trim($firstName . " " . $lastName);
        }
        else{
            $lineContents = fgets($fileHandle);
        }
    }
}

#Allows patient and provider to sync their accounts
function accountSync($userId, $linkId){
    
}

#adds the user's name to the ready list on the checkin.txt file
function patientArrived($userId){
    
}