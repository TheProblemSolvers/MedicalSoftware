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

#returns an array of all patients linked to provider
function getAllLinked($providerId){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #grabs row data from provider's row in linkedaccounts table
    $result = $connection->prepare("SELECT * FROM linkedaccounts WHERE providerId='$providerId'");
    $result->execute();
    $linkedAccounts = $result->fetch(PDO::FETCH_NUM);

    #if no accounts have been linked, 

    #removes all null values from array and returns numerically indexed array of all patients
    array_shift($linkedAccounts);
    $length = count($linkedAccounts);
    for($i = 0; $i < $length; $i++){
        if($linkedAccounts[$i] == NULL){
            unset($linkedAccounts[$i]);
        }
    }

    #reindexes array after removing key/value pairs
    $i = 0;
    $accounts = NULL;
    foreach($linkedAccounts as $patientId){
        $accounts[$i] = $patientId;
        $i++;
    }

    #return array of patients, otherwise NULL
    return $accounts;
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
function userFullName($userId, $indicator){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #gathers results from searching table into an array
    if($indicator == true){
        $contents = $connection->prepare("SELECT firstName, middleName, lastName FROM allusers WHERE id='$userId';");
    } else{
        $contents = $connection->prepare("SELECT firstName, lastName FROM allusers WHERE id='$userId';");
    }
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
    $HTML[$i] = "<label for='name'>Name: </label><input class='ipt' type='text' id='name' name='name' style='width:250px' value='" . 
        $patientData[$columns[$j]['COLUMN_NAME']] . " " . $patientData[$columns[$j + 1]['COLUMN_NAME']] . " " .
        $patientData[$columns[$j + 2]['COLUMN_NAME']] . "'readonly>";
    $i++;
    $j += 3;
    $HTML[$i] = "<label for='dob'>DOB: </label><input class='ipt' type='date' id='dob' name='dob' value='" . 
        $patientData[$columns[$j]['COLUMN_NAME']] . "'readonly>";
    $i++;
    $j++;
    $HTML[$i] = "<label for='height'>Height: </label><input class='ipt' type='number' id='height' name='height' value='" . 
        $patientData[$columns[$j]['COLUMN_NAME']] . "'required><label for='height'>in.</label>";
    $i++;
    $j++;
    $HTML[$i] = "<label for='weight'>Weight: </label><input class='ipt' type='number' id='weight' name='weight' value='" . 
        $patientData[$columns[$j]['COLUMN_NAME']] . "'required><label for='weight'>lbs.</label>";
    $i++;
    $j++;
    $HTML[$i] = "<label for='sex'>Sex: </label><input class='ipt' type='text' id='sex' name='sex' value='" . 
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
    $htmlTable = "<div class='dbTable'><table><thead><tr><th>Edit/View</th><th>First Name</th><th>Middle Name</th><th>Last Name</th>
        <th>DOB</th><th>Height</th><th>Weight</th><th>Sex</th></thead><tbody><tr>";
    $htmlEndTable = "</tr></tbody></table></div><br>";
    $htmlRows = "";

    #define variable to increment as each row is generated
    $j = 0;

    #generates HTML page from patient data recieved from SQL query
    foreach($patientData as $rowData){
        #set class of each row to either even or odd to create various colors for each row
        if($j % 2 == 0){
            $class = "evenRow";
        }else{
            $class = "oddRow";
        }

        #only increment up to gender column, then go to next patient
        for($i = 2; $i < 10; $i++){
            if($i == 2){
                $htmlRows .= "<tr class='$class'><td class='editData'>" . "<button class='edit' onclick='setPatientIdCookie(" . 
                    $rowData['relid'] . ")'><i class='fas fa-user-edit'></i></button></td>";
            }
            else{
                $htmlRows .= "<td>" . strval($rowData[$columns[$i]['COLUMN_NAME']]) . "</td>";
            }
        }
        $htmlRows .= "</tr>";
        $j++;
    }

    #connects all HTML strings and returns to browser to display
    return $htmlTable . $htmlRows . $htmlEndTable;
}

#searches user's database for patients matching search parameter and 
#returns first patient id that matches
function searchDatabase($userId, $searchParameter){
    return "Unfortunately, this feature has not been fully developed yet.";
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
    #get provider's linked account and patients name
    $fullName = explode(" ", userFullName($patientId, false));
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

    #define array storing data tags, makes it easy to loop the html composition
    $infoLabels = ["Full Name: ", "DOB: ", "Height: ", "Weight: ", "Sex: ", "Current Health Conditions: ", "Current Medications: ", 
        "Past Health Conditions: ", "Family Health History: ", "Additional Notes: "];
    $html = "";

    #combine first, middle, and last names to one line of data
    $fullName = userFullName($patientId, true);
    $html .= "<p>Basic Information:</p><div class='data' id='data1'><p class='label'>$infoLabels[0]" . "
        </p><p class='info'>$fullName</p></div>";

    #loop through remaining data for patient and compile html elements
    for($i = 6; $i < count($patientData); $i++){
        #set variable to increment division class and label array index for each element, respectively
        $j = $i - 4;
        $k = $i - 5;

        #if the sex data html division has been created, add in another div (turned into horizontal line in css)
        if($i == 10){
            $html .= "<div class='line'></div><p>Detailed Information:</p>";
        }

        #compile html element for this particular data
        $html .= "<div class='data' id='data$j'><p class='label'>$infoLabels[$k]</p><p class='info'>" . 
            $patientData[$columns[$i]['COLUMN_NAME']] . "</p></div>";
    }

    #returns html elements to browser to display
    return $html;
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
    $data = $contents->fetchAll(PDO::FETCH_ASSOC);
    $flag = false;
    foreach($data as $userInfo){
        if($userInfo['id'] == $providerId && $userInfo['usertype'] == 'provider'){
            $flag = true;
        }
    }

    #if the flag was not set to true by preceeding loop, then return error message
    if($flag == false){
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
        $sql = "INSERT INTO calendar (patientid) VALUES ($patientId);";
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

        #adjust provider id to correctly correlate to row number where their provider id is stored in linkedaccounts table
        for($j = 0; $j < count($tableData); $j++){
            if($providerId == $tableData[$j]['providerId']){
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
    if($providerId == false){
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
            $fullName = userFullName($patientId, false);
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
    $body = 'Dr. ' . userFullName($providerId, false) . " is ready for you. Please enter the building and 
        navigate to the check-in desk.<br><br>Thank you, <br>Management";
    $altBody = 'Dr. ' . userFullName($providerId, false) . " is ready for you. Please enter the building and 
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
    $patientFullName = userFullName($patientId, false);
    $patientEmail = getEmail($patientId);

    #runs through provider's data file and gets full name and email
    $providerId = getSqlLinkedAccount($patientId);
    $providerFullName = userFullName($providerId, false);
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
        $patientTag = userFullName($patientId, false) . " Said: ";
        $providerTag = "You Said: ";
    }
    if($userType == "patient"){
        $providerId = getSqlLinkedAccount($patientId);
        $patientTag = "You Said: ";
        $providerTag = "Dr. " . userFullName($providerId, false) . " Said: ";
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
            $message = html_entity_decode($textData[1]);
            $html .= "<div class='patientMessage'><p class='patientTag'>$patientTag</p><p class='patientText'>$message</p></div>";
        }
        elseif($textData[0] == $providerId){
            $message = html_entity_decode($textData[1]);
            $html .= "<div class='providerMessage'><p class='providerTag'>$providerTag</p><p class='providerText'>$message</p></div>";
        }
    }

    if($html == NULL){
        $message = "No text history found.";
        return "<div class='patientMessage'><p class='patientText'>$message</p></div>";
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
    $message = htmlentities($message);
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
            $patientName = userFullName($patientId, false);
            $html .= "<a class='link' href='provider_textLog.html' onclick='setPatientIdCookie($patientId)'>$patientName</a><br>";
        }
    }

    #returns html to browser
    return $html;
}

/* -------------------------------------------------------------------------------------------------------------------------- */
/* ---------------------------------------------Appointment Scheduling------------------------------------------------------- */
/* -------------------------------------------------------------------------------------------------------------------------- */

#takes appointment date and time and stores it into patient's appointment row in calendar table
function storeApptData($patientId, $apptType, $addtlInfo, $date, $time){
    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    #formats appointment data in a way that is easy to parse
    $apptData = "$date<<<$time<<<$apptType<<<$addtlInfo";
    $time = explode(":", $time);
    $date = explode("-", $date);

    #get row of data corresponding to patient's id number
    $result = $connection->prepare("SELECT * FROM calendar WHERE patientid=$patientId;");
    $result->execute();
    $rowData = $result->fetch(PDO::FETCH_ASSOC);

    #get table column names and store in $columns variable
    $result = $connection->prepare(
        "SELECT
            COLUMN_NAME, ORDINAL_POSITION, DATA_TYPE
        FROM
            INFORMATION_SCHEMA.COLUMNS
        WHERE
            TABLE_NAME = 'calendar'                
        ORDER BY 2;
    ");
    $result->execute();
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);

    #finds column where data is NULL, replaces it with sender's id and message
    for($i = 1; $i < count($rowData); $i++){
        #accesses the column value for patient's row in textlog table
        $columnValue = $rowData[$columns[$i]['COLUMN_NAME']];

        #adds message to patient's row in first empty column
        if($columnValue == NULL){
            #inserts appointment data into correct row in calendar table
            $column = $columns[$i]['COLUMN_NAME'];
            $result = $connection->prepare(
                "UPDATE calendar SET $column='$apptData' WHERE patientid=$patientId;"
            );
            $result->execute();

            #turns military time into std time for success message to patient/confirmation email
            $timeTag = "AM";
            if($time[0] > 12){
                $time[0] = $time[0] - 12;
                $timeTag = "PM";
            }

            #compiles confirmation message to email to patient
            $confirmationMessage = "Appointment has been made for " . userFullName($patientId, false) . " on " . $date[1] . 
                "/" . $date[2] . "/" . $date[0] . " at " . $time[0] . ":" . $time[1] . " " . $timeTag;
            sendEmail($patientId, "Appointment Confirmation", $confirmationMessage, $confirmationMessage);

            #returns success message with date and time of appointment to browser
            return $confirmationMessage;
        }
    }
    return "Database error has occured. Please contact admin with following details: Not enough columns in calendar table.";
}

#gets all appointments from a patient from calendar table and sends to browser as array
function getAppointmentDates($patientId){
    #returns an error message if no linked provider's account is found
    if(getSqlLinkedAccount($patientId) == false){
        return "No provider account linked. Please link to your provider's account.";
    }

    #open config.ini.php file and get configuration
    $ini = parse_ini_file("config.ini.php");

    #open connection to medicalsoftware database and set error mode to exception
    $connection = new PDO("mysql:host=$ini[host];dbname=$ini[dbname]", $ini['dbusername'], $ini['dbpassword']);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    #grab all appointment dates for given patient
    $result = $connection->prepare("SELECT * FROM calendar WHERE patientid=$patientId;");
    $result->execute();
    $rowData = $result->fetch(PDO::FETCH_NUM);

    #converts gibberish array into useful mulidimensional array
    #data form from calendar table: $date<<<$time<<<$apptType<<<$addtlInfo
    $flag = 1;
    for($i = 1; $i < count($rowData); $i++){
        if($rowData[$i] != NULL){
            $apptData = explode("<<<", $rowData[$i]);
            $apptDate = explode("-", $apptData[0]);
            $apptDay = $apptDate[2];
            $apptMonth = $apptDate[1];
            $apptYear = $apptDate[0];
    
            $apptTime = explode(":", $apptData[1]);
            $apptMinute = $apptTime[1];
            $apptHour = $apptTime[0];
    
            $apptArray[$i - 1] = [$apptMinute, $apptHour, $apptDay, $apptMonth, $apptYear, $apptData[2], $apptData[3]];
        } else{
            $flag += 1;
        }
    }

    #if every data value in patient's row in calendar was NULL, then return null
    if($flag == count($rowData)){
        return NULL;
    }
    else{
        return $apptArray;
    }
}

#gathers all appointment data for patients linked to a given provider
function getAllAppts($userId){
    #gathers necessary variables depending on whether patient id or provider id was passed
    if(sqlUserType($userId) == 'patient'){
        $providerId = getSqlLinkedAccount($userId);
    } else {
        $providerId = $userId;
    }

    #compile list of all linked patients, if none are linked, return error
    $patientIds = getAllLinked($providerId);
    if($patientIds == NULL){
        return NULL;
    }

    #for each patient, compile all appointments
    foreach($patientIds as $patientId){
        $allAppts[$patientId] = getAppointmentDates($patientId);
    }
    #return multidimenstional array w following syntax: array[patientId][apptNumber][apptDataType]
    return $allAppts;
}