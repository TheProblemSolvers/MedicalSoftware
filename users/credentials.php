<?php

#character to seperate username/password
$seperator = "*";

#checks to make sure all characters in data are valid, 
#returns false if invalid characters are present
function checkCharacters($data){
    if(preg_match("/\s/", $data) == 1){
        return false;
    }
    else{
        return true;
    }
}

#checks the length of username and password, returns true if data is too short
function checkLength($data){
    $minimumLength = 6;
    if(strlen($data) < $minimumLength){
        return true;
    }
    else{
        return false;
    }
}

#accesses the requested user data file and returns the users full name
function userFullName($userId){
    $fileHandle = fopen("../users\user_data\#" . trim(strval($userId)) . ".txt", 'r');
    $userFullName = trim(fgets($fileHandle));
    fclose($fileHandle);
    return $userFullName;
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
            $userType = trim(fgets($fileLocation));
            #set a cookie with the users unique id
            setcookie("userId", trim(fgets($fileLocation)));
            #close the file
            fclose($fileLocation);
            return $userType;
        }
    }
    #close the file
    fclose($fileLocation);
    #let caller know there was no credentials match
    return false;
}

#adds new users to credentials.txt file, with respective user id and type
function addCredentials($firstName, $lastName, $newUsername, $newPassword, $newUserType){
    #convert form data to formatted strings to write to credentials.txt
    $newCredentials = trim($newUsername) . $GLOBALS['seperator'] . trim($newPassword) . "\n";
    $newUserType = $newUserType . "\n";

    #creates new unique user id based on last one in file
    #opens file and counts how many lines there are
    $fileLocation = fopen("credentials.txt", "r");
    $lineCount = 0;
    while(feof($fileLocation) == false){
        $lineContents = fgets($fileLocation);
        $lineCount ++;
    }
    #calculates the position of the last user id in file
    $lineNumber = $lineCount - 2;
    #following 4 lines of code from https://stackoverflow.com/users/1268048/phil,
    #and they read the contents of a specific line in the .txt file
    $file = new SplFileObject("credentials.txt");
    if (!$file->eof()) {
        $file->seek($lineNumber);
        $contents = $file->current(); // $contents would hold the data from line x
    }
    fclose($fileLocation);
    #creates new user id by adding 1 to preceding id, and adding linebreak
    $newUserId = strval(intval($contents) + 1) . "\n";

    #writes credentials, usertype and userid to open file
    #opens the file credential storage file
    $fileLocation = fopen('credentials.txt', 'a+');
    fwrite($fileLocation, $newCredentials);
    fwrite($fileLocation, $newUserType);
    fwrite($fileLocation, $newUserId);
    #closes the credentials file
    fclose($fileLocation);

    #creates a unique text file for the users data to be stored and accessed over time
    $newUserId = trim($newUserId);
    $fileName = "user_data\#" . $newUserId . ".txt";
    $fileLocation = fopen($fileName, "w");
    fwrite($fileLocation, trim($firstName) . " " . trim($lastName) . "\n");
    fwrite($fileLocation, $newUserType);
    fclose($fileLocation);

    return false;
}