<?php

#searches user's database for patients matching search parameter and 
#returns first patient id that matches
function searchDatabase($userId, $searchParameter){
    #defines an array used later to store search results
    $i = 0;
    $array = array();
    #accesses correct user database
    $fileName = "../users\user_data\#" . $userId . ".txt";
    $fileHandle = fopen($fileName, "r");
    
    #find the line where to start searching 
    #(ignore all provider data and skip to patient's stored data)
    $lineContents = fgets($fileHandle);
    $finished = false;
    while($finished == false){
        if(preg_match("/p[0-9]/", $lineContents) != 1){
            $lineContents = fgets($fileHandle);
        }
        else{
            $finished = true;
        }
    }
    #modifies search paramter to regular expression to efficiently search database
    $searchParameter = "/" . $searchParameter . "/i";
    $searchResults = "";
    #searches through each line looking for a match to search parameter
    while(feof($fileHandle) == false){
        #if a line matches the search query, return patient's id
        if(preg_match($searchParameter, $lineContents) == 1){
            #determines the place where the patient id starts and stops in string
            $startRead = strpos($lineContents, "p") + 1;
            $endRead = strpos($lineContents, ".") - 1;
            #if this patient ID has already been added to the array, do not add it
            if(substr($lineContents, $startRead, $endRead) == $searchResults){
                
            }
            #compiles each matching search result into an array
            else{
                $searchResults = substr($lineContents, $startRead, $endRead);
                $array[$i] = $searchResults;
                $i++;
            }
        }
        #advance file pointer one line down and repeat
        $lineContents = fgets($fileHandle);
    }
    fclose($fileHandle);
    #if there are matches, read them into a string to return
    if($i > 0){
        $returnString = "";
        for($i = 0; $i < count($array); $i++){
            $returnString = $returnString . $array[$i];
        }
        return $returnString;
    }
    #return an error if there are no matches
    else{
        return "Patient record not found";
    }    
}

#creates a new patient in the provider's database
function createNewPatient($userId, $patientFirstName, $patientLastName, $patientNotes){
    $fileName = "../users\user_data\#" . $userId . ".txt";
    $fileHandle = fopen($fileName, "r");
    
    #finds the total number of lines in the opened file
    $lineCount = 0;
    while(feof($fileHandle) == false){
        $lineContents = fgets($fileHandle);
        $lineCount ++;
    }
    #calculates the position of the patient user id in file
    $lineNumber = $lineCount - 2;
    #following 4 lines of code from https://stackoverflow.com/users/1268048/phil,
    #and they read the contents of a specific line in the .txt file
    $file = new SplFileObject($fileName);
    if (!$file->eof()) {
        $file->seek($lineNumber);
        $contents = $file->current(); #$contents holds the data in $lineNumber
    }
    fclose($fileHandle);
    #checks to see if there is an existing patient already stored, if so, 
    #increment the patient's identification number by one
    if(is_int(intval(substr($contents, 1, 1))) && (intval(substr($contents, 1, 1)) != 0) == false){
        $patientId = 1;
    }
    else{
        $patientId = strval(intval(substr($contents, 1, 1)) + 1);
    }
    #opens the users file for appending
    $fileHandle = fopen($fileName, "a+");
    #writes patient data to the file with correct labels
    fwrite($fileHandle, "p" . $patientId . ".FirstName=" . trim($patientFirstName) . "\n");
    fwrite($fileHandle, "p" . $patientId . ".LastName=" . trim($patientLastName) . "\n");
    if($patientNotes != ""){
        fwrite($fileHandle, "p" . $patientId . ".Notes=" . trim($patientNotes, "") . "\n");
    }
    fclose($fileHandle);
}