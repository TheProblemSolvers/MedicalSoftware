<?php

#accesses correct user database
function accessUserDatabase($userId, $openType){
    $fileName = "../users\user_data\#" . $userId . ".txt";
    $fileHandle = fopen($fileName, $openType);
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

#checks a file for any duplicates of data, returns false if no duplicates
function checkDuplicates($userId, $data){
    $fileHandle = accessUserDatabase($userId, "r");
    $searchParameter = "/" . $data . "/i";
    while(feof($fileHandle) == false){
        $lineContents = fgets($fileHandle);
        if(preg_match($searchParameter, $lineContents) == 1){
            fclose($fileHandle);
            return true;
        }
    }
    fclose($fileHandle);
    return false;
}

#gets patients first and last name based on patients id
function patientFullName($userId, $patientId){
    $fileHandle = accessUserDatabase($userId, "r");
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

#Allows patient to sync their account to their provider
function patientToProvider($userId, $linkId){
    if(checkDuplicates($userId, "ProviderAccount") == false){
        $fileHandle = accessUserDatabase($userId, "a");
        if(file_exists("../users\user_data\#" . $linkId . ".txt") == true){
            $linkData = "ProviderAccount=" . strval($linkId) . "\n";
            fwrite($fileHandle, $linkData);
            fclose($fileHandle);
            return true;
        }
        else{
            return "Provider ID does not exist.";
        }
    }
    else{
        return "Provider ID already linked to this account.";
    }
}

#generates an html table with only patient's data
function generatePatientTable($userId){
    $fileHandle = accessUserDatabase($userId, "r");

    $htmlTable = "<br><table><thead><tr><th>First Name</th><th>Last Name</th><th>Patient Notes</th></tr></thead><tbody><tr>";
    $htmlEndTable = "</tr></tbody></table>";
    $providerId = null;

    #filters through patient's file and finds linked provider's account
    while(feof($fileHandle) == false){
        $lineContents = fgets($fileHandle);
        if(preg_match("/ProviderAccount/i", $lineContents) == 1){
            
            $startRead = strpos($lineContents, "=") + 1;
            $providerId = trim(substr($lineContents, $startRead));
            break;
        }
    }
    fclose($fileHandle);

    #if there was no linked provider account, return an error
    if($providerId == null){
        return "No linked provider account.";
    }

    #opens the provider's file and finds the patient's information
    $fileHandle = accessUserDatabase($providerId, "r");

    #filters through provider's file and lands on patient's info
    while($userId != getPatientId($lineContents)){
        $lineContents = fgets($fileHandle);
    }
    #compiles all of selected patient's data into a table to display
    while($userId == getPatientId($lineContents)){
        $startRead = strpos($lineContents, "=") + 1;
        $htmlTable = $htmlTable . "<td>" . substr($lineContents, $startRead) . "</td>";
        $lineContents = fgets($fileHandle);
    }
    $htmlTable = $htmlTable . $htmlEndTable;

    fclose($fileHandle);
    return $htmlTable;
}

#adds the user's name to the ready list on the checkin.txt file
function patientArrived($userId){
    
}