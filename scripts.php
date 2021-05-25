<?php

/* -------------------------------------------------------------------------------------------------------------------------- */
/* ------------------------------------------------Global declarations------------------------------------------------------- */
/* -------------------------------------------------------------------------------------------------------------------------- */

#class declarations for the automated email function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/* -------------------------------------------------------------------------------------------------------------------------- */
/* ----------------------------------------------Building Block functions---------------------------------------------------- */
/* -------------------------------------------------------------------------------------------------------------------------- */

#gets a patient's name from patient data file based on a relative id passed to function
function relIdName($relId){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #gathers first and last name from patientdata table based on relId
    $contents = $connection->prepare("SELECT firstname, lastname FROM patientData WHERE relid='$relId';");
    $contents->execute();
    $nameArray = $contents->fetch(PDO::FETCH_ASSOC);

    #return first and last name
    return implode(" ", $nameArray);
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
        $data = $result->fetch(PDO::FETCH_ASSOC);

        #if there is a match in the column, return provider id of that row
        if($data != NULL){
            return $data['providerId'];
        }
    }   
    #if cycled through each column of linkedaccounts table returns no result, no linked account
    return false;
}

#gets user's email from allusers table
function getEmail($id){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #gets email that matches users id
    $contents = $connection->prepare("SELECT email FROM allusers WHERE id='$id';");
    $contents->execute();
    $duplicate = $contents->fetch(PDO::FETCH_ASSOC);

    return $duplicate['email'];
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

/* -------------------------------------------------------------------------------------------------------------------------- */
/* -------------------------------------------------Login/add new user------------------------------------------------------- */
/* -------------------------------------------------------------------------------------------------------------------------- */

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
    }
    return true;
}

/* -------------------------------------------------------------------------------------------------------------------------- */
/* -------------------------------------------------Provider Database-------------------------------------------------------- */
/* -------------------------------------------------------------------------------------------------------------------------- */

#grabs data from patientdata and displays it in editable form elements
function createInduvidualTable($relId){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #sends SQL command to get record where relId matches cookie stored in browser
    $contents = $connection->prepare("SELECT * FROM patientdata WHERE relid=$relId;");
    $contents->execute();
    $patientData = $contents->fetchAll(PDO::FETCH_ASSOC);
    $patientData = $patientData[0];

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

    #format name and dob data
    $i = 0;
    $j = 3;
    $HTML[$i] = "<label for='name'>Name: </label><input type='text' id='name' name='name' style='width:250px' value='" . 
        $patientData[$columns[$j]['COLUMN_NAME']] . " " . $patientData[$columns[$j + 1]['COLUMN_NAME']] . " " .
        $patientData[$columns[$j + 2]['COLUMN_NAME']] . "'readonly>";
    $i++;
    $j += 3;
    $HTML[$i] = "<label for='dob'>DOB: </label><input type='date' id='dob' name='dob' value='" . 
        $patientData[$columns[$j]['COLUMN_NAME']] . "'readonly>";
    $i++;
    $j++;
    $HTML[$i] = "<label for='height'>Height: </label><input class='inputNumber' type='number' id='height' name='height' value='" . 
        $patientData[$columns[$j]['COLUMN_NAME']] . "'required><label for='height'>in.</label>";
    $i++;
    $j++;
    $HTML[$i] = "<label for='weight'>Weight: </label><input class='inputNumber' type='number' id='weight' name='weight' value='" . 
        $patientData[$columns[$j]['COLUMN_NAME']] . "'required><label for='weight'>lbs.</label>";
    $i++;
    $j++;
    $HTML[$i] = "<label for='sex'>Sex: </label><input type='text' id='sex' name='sex' value='" . 
        $patientData[$columns[$j]['COLUMN_NAME']] . "'readonly><br><br>";
    $i++;
    $j++;
    $HTML[$i] = "<label class='textLabel' for='currHealth'>Current Health Conditions:</label><textarea class='textarea'" . 
        "id='curHealth' name='currHealth'>" . $patientData[$columns[$j]['COLUMN_NAME']] . "</textarea><br>";
    $i++;
    $j++;
    $HTML[$i] = "<label class='textLabel' for='currMeds'>Current Medications:</label><textarea class='textarea' id='currMeds'" . 
    "name='currMeds'>" . $patientData[$columns[$j]['COLUMN_NAME']] . "</textarea><br>";
    $i++;
    $j++;
    $HTML[$i] = "<label class='textLabel' for='pastHealth'>Past Health Conditions:</label><textarea class='textarea'" . 
        "id='pastHealth' name='pastHealth'>" . $patientData[$columns[$j]['COLUMN_NAME']] . "</textarea><br>";
    $i++;
    $j++;
    $HTML[$i] = "<label class='textLabel' for='familyHealth'>Family Health History:</label><textarea class='textarea'" . 
        "id='familyHealth' name='familyHealth'>" . $patientData[$columns[$j]['COLUMN_NAME']] . "</textarea><br>";
    $i++;
    $j++;
    $HTML[$i] = "<label class='textLabel' for='notes'>Additional Notes:</label><textarea class='textarea'" . 
        "id='notes' name='notes'>" . $patientData[$columns[$j]['COLUMN_NAME']] . "</textarea><br>";

    #compiles array and sends to browser
    return implode("\n", $HTML);
}

#updates patient data in patientdata table to changes provider has made
function modifyPatientData($relId, $height, $weight, $currMeds, $currHealth, $pastHealth, $familyHealth, $notes){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #sends SQL command to get record where relId matches cookie stored in browser
    $contents = $connection->prepare(
        "UPDATE patientdata
        SET height = '$height', weight = '$weight', currenthealth = '$currHealth', currentmeds = '$currMeds',
            pasthealth = '$pastHealth', familyhealth = '$familyHealth', notes = '$notes'
        WHERE relid=$relId;"
    );
    $contents->execute();

    return true;
}

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
    $htmlTable = "<div class='dbTable'><table><thead><tr><th>First Name</th><th>Middle Name</th><th>Last Name</th>
        <th>DOB</th><th>Height</th><th>Weight</th><th>Sex</th></thead><tbody><tr>";
    $htmlEndTable = "</tr></tbody></table></div>";
    $htmlRows = "<tr>";

    #generates HTML page from patient data recieved from SQL query
    foreach($patientData as $rowData){
        #only increment up to gender column, then go to next patient
        for($i = 3; $i < 10; $i++){
            if($columns[$i]['COLUMN_NAME'] == 'firstname'){
                $htmlRows .= "<td>" . "<button onclick='setPatientIdCookie(" . $rowData['relid'] . ")'>View</button>" . 
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
    return "You haven't rewritten me yet nimrod.";
}

#creates a new patient and adds their info to the patientdata table in medicalsoftware database
function createNewPatient($providerId, $fullName, $dob, $height, $weight, $sex, $currentMeds, $currentHealth, 
    $pastHealth, $familyHealth, $notes){

    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #convert full name to first, middle, and last
    $name = explode(" ", $fullName);

    #if not enough names are given, return error
    if(count($name) < 2){
        return "Patient must have at least a first and last name";
    }
    
    #makes middle name equal to null if only two names are present
    elseif(count($name) < 3){
        $lastName = $name[1];
        $name[1] = NULL;
        $name[2] = $lastName;
    }

    #makes middle name equal to all middle names if more than 3 names are present
    elseif(count($name) > 3){
        $firstName = $name[0];
        $lastName = $name[count($name) - 1];
        array_pop($name);
        array_shift($name);
        $middleName = implode(" ", $name);
        $name = [$firstName, $middleName, $lastName];
    }

    #check if provider exists, end execution if no match is found
    $contents = $connection->prepare(
    "INSERT INTO patientdata (providerid, firstname, middlename, lastname, dob, height, weight, sex, currenthealth, 
        currentmeds, pasthealth, familyhealth, notes)
    VALUES ($providerId, '$name[0]', '$name[1]', '$name[2]','$dob', '$height', '$weight', '$sex', '$currentHealth', 
        '$currentMeds', '$pastHealth', '$familyHealth', '$notes');
    ");
    $contents->execute();

    return true;
}

/* -------------------------------------------------------------------------------------------------------------------------- */
/* --------------------------------------------------Patient Database-------------------------------------------------------- */
/* -------------------------------------------------------------------------------------------------------------------------- */

#generates an html table with only patient's data from their linked provider's account
function generatePatientTable($patientId){
    #get provider's linked account and patient's full name
    $fullName = explode(" ", userFullName($patientId));
    $firstName = $fullName[0];
    $lastName = $fullName[1];
    $providerId = getSqlLinkedAccount($patientId);

    #returns error if no linked account was found
    if($providerId == false){
        return "No linked provider account found";
    }

    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #get dob from allusers table where patient gave their dob originally
    $contents = $connection->prepare(
        "SELECT dob FROM allusers 
        WHERE id='$patientId';"
    );
    $contents->execute();
    $dob = $contents->fetch(PDO::FETCH_ASSOC);
    $dob = $dob['dob'];

    #gather data provider stored on patient only if name, provider, and dob match
    $contents = $connection->prepare(
        "SELECT * FROM patientdata 
        WHERE providerid=$providerId AND firstname='$firstName' AND lastname='$lastName' AND dob='$dob';"
    );
    $contents->execute();
    $patientData = $contents->fetch(PDO::FETCH_ASSOC);

    #if provider has not created profile for patient, return error
    if($patientData == NULL){
        return "Provider has not made your profile yet";
    }

    #get all column names from patientdata table
    $result = $connection->prepare(
        "SELECT
            COLUMN_NAME, ORDINAL_POSITION, DATA_TYPE
        FROM
            INFORMATION_SCHEMA.COLUMNS
        WHERE
            TABLE_NAME = 'patientdata'
        ORDER BY 2;"
    );
    $result->execute();
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);

    #defines the starting variables for the rest of the code to build the table off of
    $htmlTable = "<div class='dbTable'><table><thead><tr><th>First Name</th><th>Middle Name</th><th>Last Name</th><th>DOB</th>
        <th>Height</th><th>Weight</th><th>Sex</th><th>Current Health</th><th>Current Medications</th><th>Past Health</th>
        <th>Family Health History</th><th>Additional Notes</th></thead><tbody><tr>";
    $htmlEndTable = "</tr></tbody></table></div>";
    $htmlRows = "<tr>";

    #increment from first name until the end
    for($i = 3; $i < count($patientData); $i++){
        $htmlRows .= "<td>" . strval($patientData[$columns[$i]['COLUMN_NAME']]) . "</td>";
    }
    $htmlRows .= "</tr>";

    #connects all HTML strings and returns to browser to display
    return $htmlTable . $htmlRows . $htmlEndTable;
}

/* -------------------------------------------------------------------------------------------------------------------------- */
/* ---------------------------------------------------Link accounts---------------------------------------------------------- */
/* -------------------------------------------------------------------------------------------------------------------------- */

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
        #if there is no linked account, do everything to link account and enable patient-provider features

        #add patient's row to text log table
        $sql = "INSERT INTO textlog (patientId) VALUES ($patientId);";
        $connection->query($sql);

        #add patient's row to calendar table
        $sql = "INSERT INTO calendar (patientid) VALUES ($id);";
        $connection->query($sql);

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

        #adjust provider id to correctly correlate to row number where their provider id is stored
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

/* -------------------------------------------------------------------------------------------------------------------------- */
/* ----------------------------------------------Remote checkin feature------------------------------------------------------ */
/* -------------------------------------------------------------------------------------------------------------------------- */

#adds the patient's id to checkin table
function patientCheckIn($patientId){
    #get providers linked account
    $providerId = getSqlLinkedAccount($patientId);

    #return an error if no linked account is found
    if($providerId = false){
        return "No linked provider account";
    }

    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #get table column names and store in $columns variable
    $result = $connection->prepare("
        SELECT
            COLUMN_NAME, ORDINAL_POSITION, DATA_TYPE
        FROM
            INFORMATION_SCHEMA.COLUMNS
        WHERE
            TABLE_NAME = 'checkin'                
        ORDER BY 2;
    ");
    $result->execute();
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);

    #get all data from table and store in $tableData variable
    $search = $connection->prepare("SELECT * FROM checkin");
    $search->execute();
    $tableData = $search->fetchAll(PDO::FETCH_ASSOC);

    #adjust provider id to correctly correlate to row number where their provider id is stored
    for($j = 0; $j < count($tableData); $j++){
        if($providerId = $tableData[$j]['providerId']){
            $adjustedId = $j;
            break;
        }
    }

    #finds column where data is NULL, replaces it with patient's id
    for($i = 1; $i < count($columns); $i++){
        #accesses the column value for provider's row in checkin table
        $columnValue = $tableData[$adjustedId][$columns[$i]['COLUMN_NAME']];

        #checks first to see if patient has checked in yet
        if($columnValue == $patientId){
            return "Provider has already been notified of your arrival";
        }

        #adds patients id to checkin file if it has not already
        elseif($columnValue == NULL){
            $emptyColumn = $columns[$i]['COLUMN_NAME'];
            $contents = $connection->prepare("
                UPDATE checkin 
                SET $emptyColumn = $patientId 
                WHERE providerId = $providerId
            ;");
            $contents->execute();
            return true;
        }
    }
    return "Database error: not enough columns in checkin table. Please contact admin to resolve issue.";
}

#returns list of patients who have checked in
function readCheckinFile($providerId){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #get list of patients who have checked in
    $result = $connection->prepare("SELECT * FROM checkin WHERE providerid=$providerId");
    $result->execute();
    $checkedIn = $result->fetch(PDO::FETCH_ASSOC);
    array_shift($checkedIn);

    #loops through array and complie form containing buttons/text/hidden value element for each patient
    $readyPatientsHTML = '';
    $serverVar = htmlspecialchars($_SERVER['PHP_SELF']);
    foreach($checkedIn as $patientId){
        if($patientId == NULL){
            continue;
        }
        else{
            $fullName = userFullName($patientId);
            $readyPatientsHTML .= <<<EOD
            <p style='display:inline'>$fullName </p>
            <form method="POST" action="$serverVar" style="display: inline;">
            <input type="hidden" name="form" value="$patientId"><input type='submit' value='Submit'>
            </form><br>
            EOD;
        }
    }

    #return list to browser if there is contents in variable, otherwise return error
    if($readyPatientsHTML == ""){
        return "No patients are checked in";
    }
    else{
        return $readyPatientsHTML;
    }
}

#recieves check in instructions, deletes patients id from checkin table, and sends notification email
function checkInPatient($providerId, $patientId){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #get table column names and store in $columns variable
    $result = $connection->prepare("
        SELECT
            COLUMN_NAME, ORDINAL_POSITION, DATA_TYPE
        FROM
            INFORMATION_SCHEMA.COLUMNS
        WHERE
            TABLE_NAME = 'checkin'                
        ORDER BY 2;
    ");
    $result->execute();
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);

    //return print_r($columns, true);

    #find column name where patient's id is stored
    for($i = 1; $i < count($columns); $i++){
        $column = $columns[$i]['COLUMN_NAME'];
        $result = $connection->prepare("SELECT '$column' FROM checkin WHERE $column=$patientId;");
        $result->execute();
        $checkedIn = $result->fetch(PDO::FETCH_ASSOC);
        if($checkedIn != NULL){
            break;
        }
    }

    #reset column where patient's id is back to null to indicate they have been checked in
    $result = $connection->prepare(
        "UPDATE checkin
        SET $column=NULL
        WHERE providerId=$providerId;"
    );
    $result->execute();



    #send notification email to patient
    $subject = "We are ready for your appointment!";
    $body = 'Dr. ' . userFullName($providerId) . " is ready for you. Please enter the building and 
        navigate to the check-in desk.<br><br>Thank you, <br>Management";
    $altBody = 'Dr. ' . userFullName($providerId) . " is ready for you. Please enter the building and 
        navigate to the check-in desk. Thank you, Management";
    sendEmail($patientId, $subject, $body, $altBody);
    header("Location: provider_lander.html");
}

/* -------------------------------------------------------------------------------------------------------------------------- */
/* --------------------------------------------------Automated Email--------------------------------------------------------- */
/* -------------------------------------------------------------------------------------------------------------------------- */


#sends an email to the patient letting them know to enter the building, uses PHPMailer library
function sendEmail($patientId, $subject, $body, $altBody){
    #accesses email library files
    require("../vendor\phpmailer\src\Exception.php");
    require("../vendor\phpmailer\src\PHPMailer.php");
    require("../vendor\phpmailer\src\SMTP.php");

    #<------------------------gathers necessary info to send email------------------------->

    #runs through patient's data file and gets full name and email
    $patientFullName = userFullName($patientId);
    $patientEmail = getEmail($patientId);

    #runs through provider's data file and gets full name and email
    $providerId = getSqlLinkedAccount($patientId);
    $providerFullName = userFullName($providerId);
    $providerEmail = getEmail($providerId);

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

/* -------------------------------------------------------------------------------------------------------------------------- */
/* ------------------------------------------------------Text Log------------------------------------------------------------ */
/* -------------------------------------------------------------------------------------------------------------------------- */

#accesses text log and displays patient's convo with provider
function displayTextLog($patientId, $userType){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #get table column names and store in $columns variable
    $providerId = getSqlLinkedAccount($patientId);
    $result = $connection->prepare("SELECT * FROM textlog WHERE patientId='$patientId';");
    $result->execute();
    $textLog = $result->fetch(PDO::FETCH_NUM);
    
    
    #sets html element id's based on whether viewer is patient or provider
    if($userType == "provider"){
        $patientTag = userFullName($patientId) . " Said: ";
        $providerTag = "You Said: ";
    }
    if($userType == "patient"){
        $providerId = getSqlLinkedAccount($patientId);
        $patientTag = "You Said: ";
        $providerTag = "Dr. " . userFullName($providerId) . " Said: ";
    }

    #compiles text log string to send to browser
    $html = "";
    for($i = 1; $i < count($textLog); $i++){
        #split message on seperation character, first element in array is sender id, second is message
        $textData = explode('>', $textLog[$i]);

        #if message is empty, proceed to next string of text
        if(!array_key_exists(1, $textData)){
            continue;
        }

        #compile text log if message string is present
        elseif($textData[0] == $patientId){
            $html .= "<p id='patientText'>$patientTag $textData[1]</p>";
        }
        elseif($textData[0] == $providerId){
            $html .= "<p id='providerText'>$providerTag $textData[1]</p>";
        }
    }
    #return completed html to browser
    return $html;
}

#adds a line of text to patient's row in textlog table
function addTextMessage($patientId, $userId, $message){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #gather text log data from textlog table
    $providerId = getSqlLinkedAccount($patientId);
    $result = $connection->prepare("SELECT * FROM textlog WHERE patientId='$patientId';");
    $result->execute();
    $tableData = $result->fetch(PDO::FETCH_ASSOC);

    #get table column names and store in $columns variable
    $result = $connection->prepare("
        SELECT
            COLUMN_NAME
        FROM
            INFORMATION_SCHEMA.COLUMNS
        WHERE
            TABLE_NAME = 'textlog';
    ");
    $result->execute();
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);

    #format message properly
    $message = "$userId>$message";

    #finds column where data is NULL, replaces it with sender's id and message
    for($i = 1; $i < count($columns); $i++){
        #accesses the column value for patient's row in textlog table
        $columnValue = $tableData[$columns[$i]['COLUMN_NAME']];

        #adds message to patient's row in first empty column
        if($columnValue == NULL){
            $emptyColumn = $columns[$i]['COLUMN_NAME'];
            $contents = $connection->prepare(
                "UPDATE textlog 
                SET $emptyColumn = '$message'
                WHERE patientId = '$patientId';"
            );
            $contents->execute();
            return true;
        }
    }
}

#displays a list of hyperlinked names for the provider to click and view induvidual text chat
function displayTextLogMenu($providerId){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #gathers all patient id's linked to provider
    $result = $connection->prepare("SELECT * FROM linkedaccounts WHERE providerId='$providerId';");
    $result->execute();
    $linkedAccounts = $result->fetch(PDO::FETCH_ASSOC);

    #compiles hyperlinked list of all linked patient's text logs
    $html = "";
    array_shift($linkedAccounts);
    foreach($linkedAccounts as $patientId){
        if($patientId != NULL){
            $patientName = userFullName($patientId);
            $html .= "<a href='provider_textLog.html' onclick='setPatientIdCookie($patientId)'>$patientName</a><br>";
        }
    }

    #returns html to browser
    return $html;
}

/* -------------------------------------------------------------------------------------------------------------------------- */
/* ---------------------------------------------Appointment Scheduling------------------------------------------------------- */
/* -------------------------------------------------------------------------------------------------------------------------- */

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