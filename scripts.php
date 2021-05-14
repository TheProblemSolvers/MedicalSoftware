<?php

/****************************  Global Declarations  *************************************/

#class declarations for the automated email function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

#character to seperate username/password combinations
$seperator = "*";


/****************************  Basic Functions  *************************************/

#replaced with SQL compatible function

#checks whether a user is a patient or provider, returns user type
function userType($userId){
    $fileHandle = accessUserDatabase($userId, "r");
    $lineContents = fgets($fileHandle);
    $lineContents = fgets($fileHandle);
    return trim($lineContents);
}

#returns user type based on user id
function sqlUserType($id){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #gathers results from searching table into an array
    $contents = $connection->prepare("SELECT usertype FROM allusers WHERE id='$id';");
    $contents->execute();
    $duplicate = $contents->fetch(PDO::FETCH_ASSOC);

    #returns user type as a string (either 'provider' or 'patient')
    return $duplicate['usertype'];
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

#replaced by new MySQL compatible function

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

#checks for duplicates in specified MySQL database, table, and column
function checkSqlDuplicates($database, $table, $column, $data){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #gathers results from searching table into an array
    $contents = $connection->prepare("SELECT $column FROM $table WHERE $column='$data';");
    $contents->execute();
    $duplicate = $contents->fetch(PDO::FETCH_ASSOC);

    #if array is empty, then no match was found
    if($duplicate == NULL){
        return false;
    }
    else{
        return true;
    }
}

/****************************  Specific Functions  *************************************/

#compares users credentials to credential combinations in database
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

#compares credentials entered with valid combinations
function validateSqlCredentials($username, $password){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #gathers results from searching table into an array
    $contents = $connection->prepare("SELECT * FROM credentials WHERE username='$username';");
    $contents->execute();
    $validCombo = $contents->fetch(PDO::FETCH_ASSOC);
    
    #if no matches are found, deny access
    if($validCombo == NULL){
        return false;
    }

    #loop through to see if there is a matching username/password pair
    else{
        if($validCombo['username'] == "$username"){
            #if username/password match, set cookie=id, proceed to repsective lander page
            if($validCombo["password"] == "$password"){
                setcookie("userId", $validCombo['id']);
                return sqlUserType($validCombo['id']);
            }
            #if username/password do not match, deny access
            else{
                return false;
            }
        }
    }
}

#replaced by new MySQL compatible function

#adds new users to credentials.txt file and creates their folder and standard database file
function addCredentials($firstName, $middleName, $lastName, $emailAddress, $newUsername, $newPassword, $newUserType){
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

#adds a new user to medicalsoftware database (replacement for addCredentials function)
function addNewUser($firstName, $middleName, $lastName, $dob, $email, $username, $password, $userType){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #sql query to store user's data to "allusers" table
    $sql = "INSERT INTO allUsers (firstName, middleName, lastName, userType, dob, email) VALUES (trim('$firstName'), trim('$middleName'), 
        trim('$lastName'), trim('$userType'), trim('$dob'), trim('$email'));";
    try{$connection->query($sql);}
    catch(PDOException $error){echo "Error executing query: " . $error->getMessage();}

    #grabs the new user's id number
    $id = $connection->lastInsertId();

    #set the sql query to store user's credentials to "credentials" table
    $sql = "INSERT INTO credentials VALUES (trim('$id'), trim('$username'), trim('$password'));";
    try{$connection->query($sql);}
    catch(PDOException $error){return $error->getMessage();}

    return true;
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
    #return an message if there are no matches
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

#replaced by new MySQL compatible function

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

#links patients account to providers account
function linkToProvider($patientId, $providerId){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #check if provider has their own column or not
    $column = "linked" . strval(trim($providerId));
    $contents = $connection->prepare("SELECT $column FROM linkedaccounts;");
    $contents->execute();
    $data = $contents->fetch(PDO::FETCH_ASSOC);

    #-----------------need to check if provider exists and if patient has already been linked-------------------------------

    #if column does not exist, add it, otherwise continue
    if($data == NULL){
        $contents = $connection->prepare("ALTER TABLE linkedaccounts ADD $column INT UNSIGNED;");
        $contents->execute();
    }

    #add patients id into correct column and row
    $contents = $connection->prepare("INSERT INTO test ($column) VALUES ($patientId);");
    $contents->execute();
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
    $subject = "We are ready for your appointment!";
    $body = 'Dr. ' . $providerFullName . " is ready for you. Please enter the building and 
        navigate to the check-in desk.<br><br>Thank you, <br>Management";
    $altBody = 'Dr. ' . $providerFullName . " is ready for you. Please enter the building and 
        navigate to the check-in desk. Thank you, Management";
    sendEmail($patientId, $subject, $body, $altBody);
    header("Location: provider_lander.html");
}

#sends an email to the patient letting them know to enter the building, uses PHPMailer library
function sendEmail($patientId, $subject, $body, $altBody){
    #accesses email library files
    require("../vendor\phpmailer\src\Exception.php");
    require("../vendor\phpmailer\src\PHPMailer.php");
    require("../vendor\phpmailer\src\SMTP.php");

    #<------------------------gathers necessary info to send email------------------------->

    #runs through patient's data file and gets full name and email
    $fileHandle = fopen("../users\user_data\#" . $patientId . "\data.txt", "r");
    $patientFullName = fgets($fileHandle);
    fgets($fileHandle);
    $lineContents = fgets($fileHandle);
    $startRead = strpos($lineContents, "=");
    $patientEmail = substr($lineContents, $startRead + 1);
    fclose($fileHandle);

    #runs through provider's data file and gets full name and email
    $providerId = getLinkedAccount($patientId);
    $fileHandle = fopen("../users\user_data\#" . trim($providerId) . "\data.txt", "r");
    $providerFullName = fgets($fileHandle);
    fgets($fileHandle);
    $lineContents = fgets($fileHandle);
    $startRead = strpos($lineContents, "=");
    $providerEmail = substr($lineContents, $startRead);
    fclose($fileHandle);

    #<------------------------------sends email------------------------------------->

    #Instantiation and passing `true` enables exceptions
    $mail = new PHPMailer(true);

    #gather configurations from config.ini file
    $ini = parse_ini_file('config.ini.php');

    try {
        #Server settings
        $mail->SMTPDebug  = 0;                      
        $mail->isSMTP();                                            
        $mail->Host       = 'smtp.gmail.com';                     
        $mail->SMTPAuth   = true;                                  
        $mail->Username   = $ini['email'];                  
        $mail->Password   = $ini['password'];                              
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;       
        $mail->Port       = 587;                                   

        #Recipients
        $mail->setFrom($providerEmail, $providerFullName);
        $mail->addAddress($patientEmail, $patientFullName);     

        #Content
        $mail->isHTML(true);                            
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody;
        $mail->send();
        //echo 'Message has been sent';
    } 
    catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
    return;
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

#parses data from the format the appointment date is sent to the browser in
function parseApptData($apptDate){
    #parses cookie data into seperate numbers for simple use, then returns all data as an array
    #date format:  <(patientId)>(minute)A(hour)B(day)C(month)D(year)
    $patientId = trim(substr($apptDate, strpos($apptDate, "<") + 1, strpos($apptDate, ">") - 1));
    $apptMinute = trim(substr($apptDate, strpos($apptDate, ">") + 1, 2));
    $apptHour = trim(substr($apptDate, strpos($apptDate, "A") + 1, 2));
    $apptDay = trim(substr($apptDate, strpos($apptDate, "B") + 1, 2));
    $apptMonth = trim(substr($apptDate, strpos($apptDate, "C") + 1, 2));
    $apptYear = trim(substr($apptDate, strpos($apptDate, "D") + 1, 4));
    return array($patientId, $apptMinute, $apptHour, $apptDay, $apptMonth, $apptYear);
}

#seperates day, month, and year from HTML date format
function seperateHTMLDate($date){
    #finds where year data stops, and stores year data
    $endRead = strpos($date, "-");
    $apptYear = substr($date, 0, $endRead);

    #finds where month data starts, and stores month data
    $startRead = $endRead + 1;
    $endRead = strpos($date, "-", $endRead + 1);
    $apptMonth = substr($date, $startRead, ($endRead - $startRead));

    #finds where day data starts, and stores day data
    $startRead = $endRead + 1;
    $apptDay = substr($date, $startRead);

    #returns data in array format to calling function
    return array($apptDay, $apptMonth, $apptYear);
}

#seperates hour and minute from HTML time format
function seperateHTMLTime($time){
    $endRead = strpos($time, ":");
    $hour = substr($time, 0, $endRead);
    $minute = substr($time, $endRead + 1);
    return array($minute, $hour);
}

#takes appointment date and time and stores it into provider's database
function storeApptData($patientId, $apptType, $addtlInfo, $date, $time){
    #returns an error message if no linked provider's account is found
    if(getLinkedAccount($patientId) == false){
        return "No provider account linked. Please link to your provider's account.";
    }

    #compile data into format server can easily read
    $date = seperateHTMLDate($date);
    $time = seperateHTMLTime($time);
    $apptData = "<" . $patientId . ">" . $time[0] . "A" . $time[1] . "B" . $date[0] . "C" . $date[1] . "D" . $date[2] . "\n";

    #access provider's database
    $providerId = getLinkedAccount($patientId);
    $fileName = "../users\user_data\#" . trim($providerId) . "\calendar.txt";

    #checks if appointment slot is availible
    $startRead = strpos($apptData, ">") + 1;
    if(checkDuplicates($fileName, substr($apptData, $startRead)) == true){
        return "Appointment slot is taken.";
    }

    #open file, write data, close it
    $fileHandle = fopen($fileName, "a");
    fwrite($fileHandle, $apptData);
    fclose($fileHandle);

    #turns military time into std time for success message to patient
    $timeTag = "AM";
    if($time[1] > 12){
        $time[1] = $time[1] - 12;
        $timeTag = "PM";
    }

    #compiles confirmation message to email to patient
    $confirmationMessage = "Appointment has been made for " . userFullName($patientId) . " on " . $date[1] . 
        "/" . $date[0] . "/" . $date[2] . " at " . $time[1] . ":" . $time[0] . " " . $timeTag;
    sendEmail($patientId, "Appointment Confirmation", $confirmationMessage, $confirmationMessage);

    #returns success message with date and time of appointment
    return $confirmationMessage;
}

#gets a user's appointment from database and sends to browser so JavaScript can use data
function getAppointmentDates($patientId){
    #returns an error message if no linked provider's account is found
    if(getLinkedAccount($patientId) == false){
        return "No provider account linked. Please link to your provider's account.";
    }

    #access provider's database
    $providerId = getLinkedAccount($patientId);
    $fileName = "../users\user_data\#" . trim($providerId) . "\calendar.txt";
    $fileHandle = fopen($fileName, "r");

    #searches for the patient's id in beginning of appointment data string
    $i = 0;
    while(feof($fileHandle) == false){
        $lineContents = fgets($fileHandle);
        if(preg_match("/<" . $patientId . ">/", $lineContents) == true){
            $apptArray[$i] = parseApptData($lineContents);
            $i++;
        }
    }

    #if appointments were found, return array if not, return message
    if($apptArray == null){
        return "No appointment found";
    }
    else{
        return $apptArray;
    }
}