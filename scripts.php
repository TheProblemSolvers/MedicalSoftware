<?php

/****************************  Global Declarations  *************************************/

#class declarations for the automated email function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

#character to seperate username/password
$seperator = "*";


/****************************  Basic Functions  *************************************/

#checks whether a user is a patient or provider, returns user type
function userType($userId){
    $fileHandle = accessUserDatabase($userId, "r");
    $lineContents = fgets($fileHandle);
    $lineContents = fgets($fileHandle);
    return trim($lineContents);
}

#gets linked provider's account from patient's file
function getLinkedAccount($userId){
    $fileHandle = accessUserDatabase($userId, "r");
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
        return false;
    }
    #opens the provider's file and finds the patient's information
    return $providerId;
}

#accesses correct user database
function accessUserDatabase($userId, $accessType){
    $fileName = "../users\user_data\#" . $userId . "\data.txt";
    $fileHandle = fopen($fileName, $accessType);
    return $fileHandle;
}

#sets file pointer to where stored patient data begins, returns first line
function getPatientData($fileHandle){
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
    return $lineContents;
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
    $fileHandle = fopen("../users\user_data\#" . trim(strval($userId)) . "\data.txt", 'r');
    $userFullName = trim(fgets($fileHandle));
    fclose($fileHandle);
    return $userFullName;
}

#checks a file for any duplicates of data, returns false if no duplicates
function checkDuplicates($fileLocation, $data){
    if(intval($fileLocation) != 0){
        $fileHandle = accessUserDatabase($fileLocation, "r");
    }
    else{
        $fileHandle = fopen($fileLocation, "r");
    }
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

/****************************  Specific Functions  *************************************/

#compares users credentils to credential combinations in database
function validateCredentials($inputUsername, $inputPassword){
    #takes user's inputted credentials and makes a single string
    $userCredentials = $inputUsername . $GLOBALS['seperator'] . $inputPassword;
    #tries to match up created string with strings in the patient_credentials
    $fileLocation = fopen('users\credentials.txt', 'r');
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

#adds new users to credentials.txt file and creates their folder and standard database file
function addCredentials($firstName, $lastName, $emailAddress, $newUsername, $newPassword, $newUserType){
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
    $newDirectory = "user_data\#" . $newUserId;
    mkdir($newDirectory);
    $fileName = "user_data\#" . $newUserId . "\data.txt";
    $fileLocation = fopen($fileName, "w");
    fwrite($fileLocation, trim($firstName) . " " . trim($lastName) . "\n");
    fwrite($fileLocation, $newUserType);
    fwrite($fileLocation, "email=" . $emailAddress);
    fclose($fileLocation);

    return false;
}

#grabs a single patient's data from user file and returns an html table
function createInduvidualTable($userId, $patientId){
    $fileHandle = accessUserDatabase($userId, "r");
    $lineContents = getPatientData($fileHandle);
    $htmlTable = "<table><thead><tr><th>First Name</th><th>Last Name</th><th>Patient Notes</th></tr></thead><tbody><tr>";
    $htmlEndTable = "</tr></tbody></table>";
    
    #changes file pointer to correct patient in database
    while($patientId != getPatientId($lineContents)){
        $lineContents = fgets($fileHandle);
    }

    #compiles all of selected patient's data into a table
    while($patientId == getPatientId($lineContents)){
        $startRead = strpos($lineContents, "=") + 1;
        $htmlTable = $htmlTable . "<td>" . substr($lineContents, $startRead) . "</td>";
        $lineContents = fgets($fileHandle);
    }
    $htmlTable = $htmlTable . $htmlEndTable;
    fclose($fileHandle);
    return $htmlTable;
}

#grabs all patient data from user file and returns a full table in html
function createPatientTable($userId){
    $fileHandle = accessUserDatabase($userId, "r");
    $lineContents = getPatientData($fileHandle);

    #defines the starting variables for the rest of the code to build the table off of
    $htmlTable = "<br><table><thead><tr><th>First Name</th><th>Last Name</th><th>Patient Notes</th></tr></thead><tbody><tr>";
    $htmlEndTable = "</tr></tbody></table>";
    $patientId = "1";
    
    #read each patient's data, and once a new patient is found by the file pointer, 
    #end the table row and create a new one
    while(feof($fileHandle) == false){
        #creates a new row if the patient id number has changed
        if($patientId != getPatientId($lineContents)){
            $htmlTable = $htmlTable . "</tr><tr>";
            $patientId = getPatientId($lineContents);
        }
        #if the datatype is a first name, make it a link to the patient's file
        if(getDataType($lineContents) == "FirstName"){
            $startRead = strpos($lineContents, "=") + 1;
            $htmlTable = $htmlTable . "<td>" . "<a href='provider_singlePatientView.html' onclick='setPatientIdCookie(" . 
                $patientId . ")'>" . substr($lineContents, $startRead) . "</a></td>";
            $lineContents = fgets($fileHandle);
        }
        #if the datatype is not a first name, just add it to the table
        else{
            $startRead = strpos($lineContents, "=") + 1;
            $htmlTable = $htmlTable . "<td>" . substr($lineContents, $startRead) . "</td>";
            $lineContents = fgets($fileHandle);
        }
    }

    #close the file and return the completed table
    $htmlTable = $htmlTable . $htmlEndTable;
    fclose($fileHandle);
    return $htmlTable;
}

#searches user's database for patients matching search parameter and 
#returns first patient id that matches
function searchDatabase($userId, $searchParameter){
    #defines an array used later to store search results
    $i = 0;
    $array = array();

    $fileHandle = accessUserDatabase($userId, "r");
    $lineContents = getPatientData($fileHandle);

    #modifies search paramter to regular expression to efficiently search database
    $searchParameter = "/" . $searchParameter . "/i";
    $searchResults = "";
    #searches through each line looking for a match to search parameter
    while(feof($fileHandle) == false){
        #if a line matches the search query, return patient's id
        if(preg_match($searchParameter, $lineContents) == 1){
            #determines the place where the patient id starts and stops in string
            $startRead = strpos($lineContents, "p") + 1;
            $endRead = strpos($lineContents, ".");
            #if this patient ID has already been added to the array, do not add it
            if(substr($lineContents, $startRead, ($endRead - $startRead)) == $searchResults){
                
            }
            #compiles each matching search result into an array
            else{
                $searchResults = substr($lineContents, $startRead, ($endRead - $startRead));
                $array[$i] = $searchResults . ", ";
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
    $fileName = "../users\user_data\#" . $userId . "\data.txt";
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

    #increment the patient's identification number by one
    $startRead = strpos($lineContents, "p") + 1;
    $endRead = strpos($lineContents, ".");
    $patientId = strval(intval(substr($contents, $startRead, ($endRead - $startRead))) + 1);

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

#Allows patient to sync their account to their provider
function patientToProvider($userId, $linkId){
    if(checkDuplicates($userId, "ProviderAccount") == false){
        $fileHandle = accessUserDatabase($userId, "a");
        if(file_exists("../users\user_data\#" . $linkId . "\data.txt") == true){
            $linkData = "ProviderAccount=" . strval($linkId) . "\n";
            fwrite($fileHandle, $linkData);
            fclose($fileHandle);
            
            #adds patient's full name and database-referenced id to provider's account
            $fileHandle = fopen("../users\user_data\#" . $linkId . "\linkedAccounts.txt", "a");
            fwrite($fileHandle, userFullName($userId) . "=" . $userId . ".\n");
            fclose($fileHandle);
            
            return true;
        }
        else{
            fclose($fileHandle);
            return "Provider ID does not exist.";
        }
    }
    else{
        return "Provider ID already linked to this account.";
    }
}

#generates an html table with only patient's data from their linked provider's account
function generatePatientTable($userId){
    $userFullName = userFullName($userId);
    $userName = explode(' ', $userFullName);
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

    #filters through provider's file and finds user's full name in the provider's database
    while(feof($fileHandle) == false){
        while(preg_match("/" . $userName[0] . "/i", $lineContents) == 0){
            $lineContents = fgets($fileHandle);
        }
        $lineContents = fgets($fileHandle);
        if(preg_match("/" . $userName[1] . "/i", $lineContents) == 1){
            $patientId = getPatientId($lineContents);
            break;
        }
        else{
            continue;
        }
    }
    #resets the file pointer to the beginning of the file
    fclose($fileHandle);
    $fileHandle = accessUserDatabase($providerId, "r");
    
    #filters through provider's file and finds the correct line where patient data starts
    $lineContents = fgets($fileHandle);
    while($patientId != getPatientId($lineContents)){
        $lineContents = fgets($fileHandle);
    }
    
    #compiles all of selected patient's data into a table to display
    while($patientId == getPatientId($lineContents)){
        $startRead = strpos($lineContents, "=") + 1;
        $htmlTable = $htmlTable . "<td>" . substr($lineContents, $startRead) . "</td>";
        $lineContents = fgets($fileHandle);
    }
    $htmlTable = $htmlTable . $htmlEndTable;
    fclose($fileHandle);
    return $htmlTable;
}

#adds the patient's first and last name to the checked in file in linked provider's database
function patientCheckIn($userId){
    #gathers patients first and last name, and their provider's ID
    $fullName = userFullName($userId);
    $providerId = getLinkedAccount($userId);
    if($providerId == false){
        return "No linked Provider Account";
    }
    
    #creates/opens checkinLog file, and writes patients name who has checked in if not already present
    $providerFileName = "../users\user_data\#" . $providerId . "\checkinLog.txt";
    if(file_exists($providerFileName) == true){
        if(checkDuplicates($providerFileName, "=" . $userId . ".") == true){
            return "Provider Already Notified";
        }
    }
    $fileHandle = fopen($providerFileName, "a");
    fwrite($fileHandle, "\n" . $fullName . "=" . $userId . ".");
    fclose($fileHandle);
    return true;   

}

#accesses user's checkinLog.txt file and returns patients who have checked in
function readCheckinFile($providerId){
    $fileHandle = fopen("../users\user_data\#" . $providerId . "\checkinLog.txt", "r");
    $readyPatients = "";
    #advances the file pointer to start of checkinLog.txt
    fgets($fileHandle);
    #loops through file and creates list
    while(feof($fileHandle) == false){
        $lineContents = fgets($fileHandle);
        $endRead = strpos($lineContents, "=");
        $patientFullName = substr($lineContents, 0, $endRead);
        $patientId = substr($lineContents, $endRead + 1);
        $readyPatients = $readyPatients . "<p>" . $patientFullName . "</p>" . 
            "<a href='provider_executeCmd.html' onclick='setPatientIdCookie(" . $patientId . ")'>Check In</a>";
    }
    return $readyPatients;
}

#recieves check in instructions, deletes users name from check in file, and sends notification email
function checkInPatient($providerId, $patientId){
    $fileHandle = fopen("../users\user_data\#" . $providerId . "\data.txt", "r");
    #runs through provider's data file and gets full name and email
    $providerFullName = fgets($fileHandle);
    fgets($fileHandle);
    $lineContents = fgets($fileHandle);
    $startRead = strpos($lineContents, "=");
    $providerEmail = substr($lineContents, $startRead);
    fclose($fileHandle);

    $fileHandle = fopen("../users\user_data\#" . $patientId . "\data.txt", "r");
    #runs through patient's data file and gets full name and email
    $patientFullName = fgets($fileHandle);
    fgets($fileHandle);
    $lineContents = fgets($fileHandle);
    $startRead = strpos($lineContents, "=");
    $patientEmail = substr($lineContents, $startRead + 1);
    fclose($fileHandle);

    #deletes user's name from the checkin file
    $fileHandle = fopen("../users\user_data\#" . $providerId . "\checkinLog.txt", "r");
    #increment file pointer one line to avoid initial space
    $lineContents = fgets($fileHandle);
    #loop through checkin file and get all names except the checked in name
    $i = 0;
    while(feof($fileHandle) == false){
        $lineContents = fgets($fileHandle);
        if(preg_match('/' . $patientId . '/', $lineContents) == 1){
            continue;
        }
        else{
            $checkinList[$i] = $lineContents;
            $i++;
        }
    }
    fclose($fileHandle);

    #rewrite all stored names to the checkin file
    $i = 0;
    $namesLeft = "";
    $fileHandle = fopen("../users\user_data\#" . $providerId . "\checkinLog.txt", "w");
    $numberOfNames = count($checkinList);
    while($i < $numberOfNames){
        $namesLeft = $namesLeft . "\n" . $checkinList[$i];
        $i++;
    }
    fwrite($fileHandle, $namesLeft);
    fclose($fileHandle);

    #sends all relevant info to email functions
    sendEmail($patientEmail, $patientFullName, $providerFullName);
}

#sends an email to the patient letting them know to enter the building, uses PHPMailer library
function sendEmail($patientEmail, $patientFullName, $providerFullName){
    #accesses email library files
    require("../vendor\phpmailer\src\Exception.php");
    require("../vendor\phpmailer\src\PHPMailer.php");
    require("../vendor\phpmailer\src\SMTP.php");

    #Instantiation and passing `true` enables exceptions
    $mail = new PHPMailer(true);

    try {
        #Server settings
        $mail->SMTPDebug  = SMTP::DEBUG_SERVER;                      
        $mail->isSMTP();                                            
        $mail->Host       = 'smtp.gmail.com';                     
        $mail->SMTPAuth   = true;                                  
        $mail->Username   = 'pltwmedicalsoftware@gmail.com';                  
        $mail->Password   = 'Cherokee.2021';                              
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;       
        $mail->Port       = 587;                                   

        #Recipients
        $mail->setFrom('pltwmedicalsoftware@gmail.com', $providerFullName);
        $mail->addAddress($patientEmail, $patientFullName);     

        #Content
        $mail->isHTML(true);                            
        $mail->Subject = "We are ready for your appointment!";
        $mail->Body    = 'Dr. ' . $providerFullName . " is ready for you. Please enter the building and 
            navigate to the check-in desk.<br><br>Thank you, <br>Management";
        $mail->AltBody = 'Dr. ' . $providerFullName . " is ready for you. Please enter the building and 
            navigate to the check-in desk. Thank you, Management"; #email body for non-HTML mail clients

        $mail->send();
        echo 'Message has been sent';
    } 
    catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
    header("Location: provider_lander.html");
}

#accesses patient's text log and displays conversation
function displayTextLog($patientId, $userType){
    #opens text log, creates a new one if one doesn't exist
    if(file_exists('../users\user_data\#' . $patientId . '\text_log.txt') == false){
        $fileHandle = fopen('../users\user_data\#' . $patientId . '\text_log.txt', 'w');
        fclose($fileHandle);
    }
    $fileHandle = fopen('../users\user_data\#' . $patientId . '\text_log.txt', 'r');

    #compiles text string
    $message = "";
    while(feof($fileHandle) == false){
        #sets the tags for the text line based on user type
        if($userType == "provider"){
            $patientTag = userFullName($patientId) . " Said: ";
            $providerTag = "You Said: ";
        }
        if($userType == "patient"){
            $providerId = getLinkedAccount($patientId);
            $patientTag = "You Said: ";
            $providerTag = "Dr. " . userFullName($providerId) . " Said: ";
        }
        #compiles list of text messaging
        $lineContents = fgets($fileHandle);
        $startRead = strpos($lineContents, ">") + 1;
        $lineLength = strlen($lineContents);
        if(substr($lineContents, $startRead, $lineLength - $startRead - 2) == ""){
            continue;
        }
        elseif(preg_match("/<" . $patientId . ">/", $lineContents) == 1){
            $message = $message . "<p class='patientText'>" . $patientTag .  
                substr($lineContents, $startRead, $lineLength - $startRead - 2) . "</p>";
        }
        else{
            $message = $message . "<p class='providerText'>" . $providerTag . 
                substr($lineContents, $startRead, $lineLength - $startRead - 2) . "</p>";
        }
    }
    fclose($fileHandle);
    return $message;
}

#adds a line of text to patient's text log
function addTextMessage($patientId, $userId, $message){
    $fileHandle = fopen('../users\user_data\#' . $patientId . '\text_log.txt', 'a');
    $line = "<" . $userId . ">" . $message . "<\n";
    fwrite($fileHandle, $line);
    fclose($fileHandle);
}

#displays a list of hyperlinked names for the provider to click and view induvidual text log
function displayTextLogMenu($providerId){
    $providerId = trim($providerId);
    $fileHandle = accessUserDatabase($providerId, "r");
    $list = "";
    #loops through the provider's file and gathers first/last name, patient ID
    while(feof($fileHandle) == false){
        $lineContents = fgets($fileHandle);
        $linkedAccountExists = false;
        if(preg_match("/firstname/i", $lineContents) == 1){
            $firstName = trim(substr($lineContents, strpos($lineContents, "=") + 1));
            $lineContents = fgets($fileHandle);
            $lastName = trim(substr($lineContents, strpos($lineContents, "=") + 1));
            
            #checks the provider's linked accounts text file and gather's patient's database-relative ID
            $patientFullName = $firstName . " " . $lastName;
            $fileHandle2 = fopen("../users\user_data\#" . $providerId . "\linkedAccounts.txt", "r");
            while(feof($fileHandle2) == false){
                $lineContents = fgets($fileHandle2);
                if(preg_match("/" . $patientFullName . "/i", $lineContents) == true){
                    $linkedAccountExists = true;
                    $startRead = strpos($lineContents, "=") + 1;
                    $endRead = strpos($lineContents, ".");
                    $patientId = trim(substr($lineContents, $startRead, ($endRead - $startRead)));
                }
            }
            fclose($fileHandle2);
            #html link is only compiled if the patient has linked their account to the provider
            if($linkedAccountExists == true){
                $cookie = "patientId=" . $patientId;
                $list = $list . "<a href='provider_textLog.html' onclick='setPatientIdCookie(" . 
                $patientId .")'>" . $firstName . " " . $lastName . "</a><br>";
            }
        }
    }
    fclose($fileHandle);
    if($list == ""){
        return "No Patients Found";
    }
    else{
        return $list;
    }
}