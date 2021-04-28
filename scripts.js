
//sets a cookie for a page based on what link is clicked
function setPatientIdCookie(id){
  document.cookie = "patientId=" + id;
}

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

//function that writes a message based on the emergency question input
function emergencyButton(buttonNumber) {

  switch (buttonNumber) {

      case 'Yes':

          var par = document.getElementById("emergencyYes");
          var t = document.createTextNode("Please contact your local emergency services if immediate medical attention is required.");
          par.appendChild(t);
          var br = document.createElement('br');
          par.appendChild(br);
          break;

      case 'No':

          var par = document.getElementById("emergencyYes");
          var t = document.createTextNode("Please continue.");
          par.appendChild(t);
          var br = document.createElement('br');
          par.appendChild(br);
          break;

      default:
      
      return false;

  }
}


//function for adding words to a cell
function cellChanger(wordsToAdd) {

  var par = document.getElementById("testData");
  var t = document.createTextNode(wordsToAdd);
  par.appendChild(t);
  var br = document.createElement('br');
  par.appendChild(br);

}

//function for retrieving a date from the date input and then adding it's value to a cell.

function dateRec()  {
  
  var par = document.getElementById("testData");
  var dateControl = document.getElementById("calendarInput").value;
  var t = document.createTextNode(dateControl);
  par.appendChild(t);
  var br = document.createElement('br');
  par.appendChild(br);  

}

//function for modifying an attribute of all elements in a class

  const cloneTables = document.getElementsByClassName('random_test');
  
  Array.from(cloneTables).forEach(function (cloneTable) {

      cloneTable.style.transform = 'translate(-50%)';

    });

//function for cycling to the next month

function nextMonth () {

  currentYear = (currentMonth === 11) ? currentYear + 1 : currentYear;

  currentMonth = (currentMonth + 1) % 12;

  printCalendar(currentMonth, currentYear);

}

//function for cycling to the previous month

function previousMonth () {

  currentYear = (currentMonth === 0) ? currentYear - 1 : currentYear;

  currentMonth = (currentMonth === 0) ? 11 : (currentMonth - 1) % 12;

  printCalendar(currentMonth, currentYear);

}

//function to remove a month from the page altogther

function removeTable() {

  var affTable = document.getElementById('Id1');

  var parentEl = affTable.parentElement;

  parentEl.removeChild(affTable);

}



//Global declarations for the printCalendar function

let today = new Date();
let currentMonth = today.getMonth();
let currentYear = today.getFullYear();
let selectYear = document.getElementById("year");
let selectMonth = document.getElementById("month");

let months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

let monthAndYear = document.getElementById("monthAndYear");


//Function that creates calendar

function printCalendar(month, year) {

  //Declarations of month data for later use


  let firstDay = (new Date(year, month)).getDay();

  let daysInMonth = 32 - new Date(year, month, 32).getDate();

  var monthName=months[month];

  //Creation of the body, table, table body and top row for headers

  var body = document.getElementsByTagName('body')[0];

  var tbl = document.createElement('table');

  tbl.id = "Id1";

  var tbdy = document.createElement('tbody');

  var headerRow = document.createElement('tr');

  //Left button

    var theader = document.createElement('th');
    theader.id = "arrowLeftHeader";

      var btn1 = document.createElement('button');
      btn1.id = "arrowLeft";

      btn1.onclick = function() {

        removeTable();
        previousMonth();

      };

      var iElement = document.createElement('i');
      iElement.setAttribute('class', 'arrow left');

      btn1.appendChild(iElement);
    theader.appendChild(btn1);

  //Center header w/ month name

  var theader2 = document.createElement('th');

  theader2.setAttribute('colspan', '5')

  theader2.appendChild(document.createTextNode(monthName));

  //Right button

    var theader3 = document.createElement('th');
    theader3.id = "arrowRightHeader";

      var btn2 = document.createElement('button');
      btn2.id = "arrowRight";

      btn2.onclick = function() {
        
        removeTable();
        nextMonth();

      };

      var iElement2 = document.createElement('i');
      iElement2.setAttribute('class', 'arrow right');

      btn2.appendChild(iElement2);
    theader3.appendChild(btn2);

  //Appending all of the sub headers to the main header row

  headerRow.appendChild(theader);
  headerRow.appendChild(theader2);
  headerRow.appendChild(theader3);

  //Appending the main header row to the body of the table

  tbdy.appendChild(headerRow);

  // creating all cells

  let date = 1;

  for (let i = 0; i < 6; i++) {

      // creates a table row

      let row = document.createElement("tr");

      //creating individual cells, filing them up with data.

      for (let j = 0; j < 7; j++) {

        //Adds the blank spaces before the actual dates start appearing

          if (i === 0 && j < firstDay) {

              let cell = document.createElement("td");
              let cellText = document.createTextNode("");
              cell.appendChild(cellText);
              row.appendChild(cell);

          }

          //When the current date is greater than the days in the month the function stops

          else if (date > daysInMonth) {

              break;

          }

          else {

            let cell = document.createElement("td");

            var container = document.createElement("span");

            let cellText = document.createTextNode(date);

              if (date === today.getDate() && year === today.getFullYear() && month === today.getMonth()) {

                container.style.color = "magenta";

              }

            container.appendChild(cellText);

            cell.appendChild(container);

            row.appendChild(cell);
            
            date++;

              //Adds the blank spaces after the dates to fill the calendar completely

          if (date > daysInMonth && j < 7) {

                for (let z = 0; z < 6 - j; z++) {

                  let cell = document.createElement("td");

                  let cellText = document.createTextNode("");

                  cell.appendChild(cellText);

                  row.appendChild(cell);

                }

              }
    
          }

      }

      //Appending the rows into the calendar

      tbdy.appendChild(row);

  }

  //Appending the table body to the table and the table to the body

  tbl.appendChild(tbdy);
  
  body.appendChild(tbl);

}