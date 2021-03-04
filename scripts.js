function authenticateLogin(validate){
  var providerUsername = "Username";
  var providerPassword = "password";  //Feel free to change username/password
  var patientUsername = "Username";   //for each user at will
  var patientPassword = "password";
  var username, password;
  username = document.getElementById("username").value;
  password = document.getElementById("password").value;

  validate == 1 ? //provider login parsing code
      username == providerUsername && password == providerPassword ? 
          window.location.href = "provider/provider_lander.html" : 
              document.getElementById("authenticationErrors").innerHTML = "Credentials are incorrect." : 

  validate == 2 ? //patient login parsing code
      username == patientUsername && password == patientPassword ? 
          window.location.href = "provider/provider_lander.html" : 
              document.getElementById("authenticationErrors").innerHTML = "Credentials are incorrect." :

  validate == 3 ? //admin login code
          window.location.href = "admin_index.html" :

  document.getElementById("authenticationErrors").innerHTML = "Invalid Authorization Code: " + validate;

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

function tableCreate(rows, cols, cellData) {
  var body = document.getElementsByTagName('body')[0];
  var tbl = document.createElement('table');
  var tbdy = document.createElement('tbody');
  tbl.style.width = '100%';
  tbl.setAttribute('border', '1');
  for (var i = 0; i < rows; i++) {
    var tr = document.createElement('tr');
    for (var j = 0; j < cols; j++) {
      if (i == rows && j == cols) {
        break;
      } else {
        var td = document.createElement('td');
        td.appendChild(document.createTextNode(cellData))
        tr.appendChild(td)
      }
    }
    tbdy.appendChild(tr);
  }
  tbl.appendChild(tbdy);
  body.appendChild(tbl);
}

function emergencyYes() {
var par = document.createElement("p");
var t = document.createTextNode("Hello Wamp");
par.appendChild(t);
document.body.appendChild(par);
break;
}
