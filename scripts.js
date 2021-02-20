function authenticateLogin(validate){
    var providerUsername = "Username";
    var providerPassword = "password";  //Feel free to change username/password
    var patientUsername = "Username";   //for each user at will
    var patientPassword = "password";
    var username, password;
    username = document.getElementById("username").value;
    password = document.getElementById("password").value;

    if(validate == 1){ //validation for provider credentials
        if(username == providerUsername){
            if(password == providerPassword){ //if credentials match, go to linked page
                window.location.href = "provider/provider_lander.html";
            }
            else{ //displays error message if password is incorrect
                document.getElementById("authenticationErrors").innerHTML = "password is incorrect";
            }
        }
        else{  //displays error message if username is incorrect
            document.getElementById("authenticationErrors").innerHTML = "Username is incorrect";
        }
    }

    if(validate == 2){ //validation for patient credentials
        if(username == patientUsername){
            if(password == patientPassword){ //if credentials match, go to linked page
                window.location.href = "patient/patient_lander.html";
            }
            else{ //displays error message if password is incorrect
                document.getElementById("authenticationErrors").innerHTML = "password is incorrect";
            }
        }
        else{ //displays error message if username is incorrect
            document.getElementById("authenticationErrors").innerHTML = "Username is incorrect";
        }
    }

    if(validate == 3){  //jumps to admin index to allow for easy debugging, avoids password jawns
        window.location.href = "admin_index.html";
    }
    
    if(validate > 3){  //throws error if there is an invalid 
        document.getElementById("authenticationErrors").innerHTML = "Invalid Authorization Code: " + validate;
    }
}

//incomplete function, once Mike learns php he will complete this function and it will replace above authenticateLogin function
function passwordProtection (validationCode){
    //Validation code of 1 = provider, 2 = patient, 3 = admin
    var username, password;
    if(validationCode ==  1){
        
    }
    if(validationCode == 2){

    }
    if(validationCode == 3){

    }
    if(validationCode > 3){

    }
}

//collects stored patient data files and converts it to html data displayed by the provider_database.html table
function displayPatientRecord(){
    var patient1 = ["John Doe", "Male", "46", "5'11&quot", "165"];
    var patient2 = []; //eventually replace with php script to get data from sql server
    var txt = "<tr>"
    var i;
    //patient arrays will be replaced by php database reading code to avoid manual entry
    for(i = 0; i < patient1.length; i++){
        txt = txt + "<td>" + patient1[i] + " </td>"; //cycles trhough each array element and makes each a table cell
    }
    txt = txt + "</tr>"
    document.getElementById("patientDatabase").innerHTML = txt;
}

function displayError(errorCode){
    
}