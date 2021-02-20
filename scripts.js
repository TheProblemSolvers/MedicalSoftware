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