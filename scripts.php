<?php

/****************************  Global Declarations  *************************************/

#class declarations for the automated email function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

#character to seperate username/password combinations
$seperator = "*";


/****************************  Basic Functions  *************************************/

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

#gets linked provider's account from patients file
function getSqlLinkedAccount($patientId){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #gather all column names from linkedaccounts database
    $result = $connection->prepare("
        SELECT
            COLUMN_NAME, ORDINAL_POSITION, DATA_TYPE
        FROM
            INFORMATION_SCHEMA.COLUMNS
        WHERE
            TABLE_NAME = 'linkedaccounts'
        ORDER BY 2;
    ");
    $result->execute();
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);

    #cycle through all columns looking for a match
    for($i = 1; $i < count($columns); $i++){
        $column = $columns[$i]['COLUMN_NAME'];
        $result = $connection->prepare("SELECT providerId FROM linkedaccounts WHERE $column = $patientId;");
        $result->execute();
        $data = $result->fetchAll(PDO::FETCH_ASSOC);

        #if there is a match in the column, return provider id of that row
        if($data != NULL){
            return $data[$i - 1]['providerId'];
        }
    }   
    #if cycled through each column of linkedaccounts table returns no result, no linked account
    return false;
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

#gets user's full name using their id from allusers table
function userFullName($userId){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #gathers results from searching table into an array
    $contents = $connection->prepare("SELECT firstName, lastName FROM allusers WHERE id='$userId';");
    $contents->execute();
    $fullName = $contents->fetch(PDO::FETCH_ASSOC);
    return implode(" ", $fullName);
}

#checks for duplicates in specified MySQL database, table, and column
function checkSqlDuplicates($table, $column, $data){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #gathers results from searching table into an array
    $contents = $connection->prepare("SELECT $column FROM $table WHERE $column='$data';");
    $contents->execute();
    $duplicate = $contents->fetchAll(PDO::FETCH_ASSOC);

    #if array is empty, then no match was found
    if($duplicate == NULL){
        return false;
    }

    #if array is not empty, then there must be a match, so return true
    else{
        return true;
    }
}

/****************************  Specific Functions  *************************************/

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
            if($validCombo["securePassword"] == "$password"){
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

#adds a new user to medicalsoftware database (replacement for addCredentials function)
function addNewUser($firstName, $middleName, $lastName, $dob, $email, $username, $password, $userType){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #sql query to store user's data to "allusers" table
    $sql = "INSERT INTO allUsers (firstName, middleName, lastName, userType, dob, email) VALUES (trim('$firstName'), 
        trim('$middleName'), trim('$lastName'), trim('$userType'), trim('$dob'), trim('$email'));";
    $connection->query($sql);

    #grabs the new user's id number
    $id = $connection->lastInsertId();

    #set the sql query to store user's credentials to "credentials" table
    $sql = "INSERT INTO credentials VALUES (trim('$id'), trim('$username'), trim('$password'));";
    $connection->query($sql);

    #if usertype = provider, add new rows to provider-sepcific tables
    if($userType == "provider"){
        #add provider row to linked accounts table
        $sql = "INSERT INTO linkedaccounts (providerid) VALUES ($id);";
        $connection->query($sql);

        #add provider row to checkin table
        $sql = "INSERT INTO checkin (providerid) VALUES ($id);";
        $connection->query($sql);

        #add provider row to calendar table
        $sql = "INSERT INTO calendar (providerid) VALUES ($id);";
        $connection->query($sql);

        #add provider row to text log table
        $sql = "INSERT INTO textlog (providerid) VALUES ($id);";
        $connection->query($sql);
    }
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

// #grabs all patient data from user file and returns a full table in html
// function createPatientTable($userId){
//     $fileHandle = accessUserDatabase($userId, "r");
//     $lineContents = getPatientData($fileHandle);

//     #defines the starting variables for the rest of the code to build the table off of
//     $htmlTable = "<br><table><thead><tr><th>First Name</th><th>Last Name</th><th>Patient Notes</th></tr></thead><tbody><tr>";
//     $htmlEndTable = "</tr></tbody></table>";
//     $patientId = "1";
    
//     #read each patient's data, and once a new patient is found by the file pointer, 
//     #end the table row and create a new one
//     while(feof($fileHandle) == false){
//         #creates a new row if the patient id number has changed
//         if($patientId != getPatientId($lineContents)){
//             $htmlTable = $htmlTable . "</tr><tr>";
//             $patientId = getPatientId($lineContents);
//         }
//         #if the datatype is a first name, make it a link to the patient's file
//         if(getDataType($lineContents) == "FirstName"){
//             $startRead = strpos($lineContents, "=") + 1;
//             $htmlTable = $htmlTable . "<td>" . "<a href='provider_singlePatientView.html' onclick='setPatientIdCookie(" . 
//                 $patientId . ")'>" . substr($lineContents, $startRead) . "</a></td>";
//             $lineContents = fgets($fileHandle);
//         }
//         #if the datatype is not a first name, just add it to the table
//         else{
//             $startRead = strpos($lineContents, "=") + 1;
//             $htmlTable = $htmlTable . "<td>" . substr($lineContents, $startRead) . "</td>";
//             $lineContents = fgets($fileHandle);
//         }
//     }

//     #close the file and return the completed table
//     $htmlTable = $htmlTable . $htmlEndTable;
//     fclose($fileHandle);
//     return $htmlTable;
// }

#generates html table from patient data stored in database from provider
function getDatabaseTable($providerId){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #sends SQL command to get all records where providerid matches user id
    $contents = $connection->prepare("SELECT * FROM patientdata WHERE providerId=$providerId;");
    $contents->execute();
    $patientData = $contents->fetchAll(PDO::FETCH_ASSOC);

    #get all column names from patientdata table
    $result = $connection->prepare(
        "SELECT
            COLUMN_NAME, ORDINAL_POSITION, DATA_TYPE
        FROM
            INFORMATION_SCHEMA.COLUMNS
        WHERE
            TABLE_NAME = 'patientdata'
        ORDER BY 2;");
    $result->execute();
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);

    #defines the starting variables for the rest of the code to build the table off of
    $htmlTable = "<br><table><thead><tr><th>First Name</th><th>Middle Name</th><th>Last Name</th><th>DOB</th><th>Height</th>
                  <th>Weight</th><th>Sex</th></thead><tbody><tr>";
    $htmlEndTable = "</tr></tbody></table>";
    $htmlRows = "<tr>";

    #generates HTML page from patient data recieved from SQL query
    foreach($patientData as $rowData){
        #only increment up to gender column, then go to next patient
        for($i = 2; $i < 9; $i++){
            if($columns[$i]['COLUMN_NAME'] == 'firstname'){
                $encodedRowData = serialize($rowData);
                $htmlRows .= "<td>" . "<button onclick='setCookie(" . "rowInfo ," . "$encodedRowData" . ")'>View</button>" . 
                    strval($rowData[$columns[$i]['COLUMN_NAME']]) . "</td>";
            }
            else{
                $htmlRows .= "<td>" . strval($rowData[$columns[$i]['COLUMN_NAME']]) . "</td>";
            }
        }
        $htmlRows .= "</tr>";
    }

    #connects all HTML strings and returns to browser to display
    return $htmlTable . $htmlRows . $htmlEndTable;
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

#creates a new patient and adds their info to the patientdata table in medicalsoftware database
function createNewPatient($providerId, $fName, $mName, $lName, $dob, $height, $weight, $sex, $currentMeds, $currentHealth, 
    $pastHealth, $familyHealth, $notes){

    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #check if provider exists, end execution if no match is found
    $contents = $connection->prepare(
    "INSERT INTO patientdata (providerid, firstname, middlename, lastname, dob, height, weight, sex, currenthealth, 
        currentmeds, pasthealth, familyhealth, notes)
    VALUES ($providerId, '$fName', '$mName', '$lName','$dob', '$height', '$weight', '$sex', '$currentHealth', 
        '$currentMeds', '$pastHealth', '$familyHealth', '$notes');
    ");
    $contents->execute();
}

#links patients account to providers account
function linkToProvider($patientId, $providerId){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #check if provider exists, end execution if no match is found
    $contents = $connection->prepare("SELECT id, usertype FROM allusers;");
    $contents->execute();
    $data = $contents->fetch(PDO::FETCH_ASSOC);
    if($data['id'] != $providerId || $data['usertype'] != 'provider'){
        return "Provider ID does not exist.";
    }

    #check if patient has already linked to a provider account, if so, return error
    $accountId = getSqlLinkedAccount($patientId);
    if($accountId == false){
        #find next column in provider's row in linkedaccounts table where no value has been assigned yet

        #get table column names and store in $columns variable
        $result = $connection->prepare("
            SELECT
                COLUMN_NAME, ORDINAL_POSITION, DATA_TYPE
            FROM
                INFORMATION_SCHEMA.COLUMNS
            WHERE
                TABLE_NAME = 'linkedaccounts'                
            ORDER BY 2;
        ");
        $result->execute();
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);

        #get all data from table and store in $tableData variable
        $search = $connection->prepare("SELECT * FROM linkedaccounts");
        $search->execute();
        $tableData = $search->fetchAll(PDO::FETCH_ASSOC);

        #adjust provider id to correctly correlate to database id
        for($j = 0; $j < count($tableData); $j++){
            if($providerId = $tableData[$j]['providerId']){
                $adjustedId = $j;
                break;
            }
        }

        #finds column where data is NULL, replaces it with patient's id
        for($i = 1; $i < count($columns); $i++){
            #accesses the column value for provider's row in linkedaccounts table
            $columnValue = $tableData[$adjustedId][$columns[$i]['COLUMN_NAME']];

            #adds patients id to provider if it hasn't been linked to an account yet
            if($columnValue == NULL){
                $emptyColumn = $columns[$i]['COLUMN_NAME'];
                $contents = $connection->prepare("
                    UPDATE linkedaccounts 
                    SET $emptyColumn = $patientId 
                    WHERE providerId = $providerId
                ;");
                $contents->execute();
                return true;
            }
        }
        return "Database error: not enough columns in linkedaccounts table. Please contact admin to resolve issue.";
    }
    else{
        return "Already linked to a provider's account. (Provider account number: $accountId)";
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
    if(getSqlLinkedAccount($patientId) == false){
        return "No provider account linked. Please link to your provider's account.";
    }

    #access provider's database
    $providerId = getSqlLinkedAccount($patientId);
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