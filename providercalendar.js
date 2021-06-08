//function for retrieving a date from the date input and then adding it's value to a cell.
function dateRec() {
    var par = document.getElementById("testData");
    var dateControl = document.getElementById("calendarInput").value;
    var t = document.createTextNode(dateControl);
    par.appendChild(t);
    var br = document.createElement('br');
    par.appendChild(br);
}

//function for cycling to the next month
function nextMonth() {
    currentYear = (currentMonth === 11) ? currentYear + 1 : currentYear;
    currentMonth = (currentMonth + 1) % 12;
    printProviderCalendar(currentMonth, currentYear, appointmentsArray);
}

//function for cycling to the previous month
function previousMonth() {
    currentYear = (currentMonth === 0) ? currentYear - 1 : currentYear;
    currentMonth = (currentMonth === 0) ? 11 : (currentMonth - 1) % 12;
    printProviderCalendar(currentMonth, currentYear, appointmentsArray);
}

//function to remove a month from the page altogther
function removeTable() {
    var affTable = document.getElementById('calendars');
    var parentEl = affTable.parentElement;
    parentEl.removeChild(affTable);
}

//Global declarations for the printCalendar function
let today = new Date();
let currentMonth = today.getMonth();
let currentYear = today.getFullYear();
let selectYear = document.getElementById("year");
let selectMonth = document.getElementById("month");
let months = [
    "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"
];
let monthAndYear = document.getElementById("monthAndYear");

//Function that creates calendar
function printProviderCalendar(month, year, ApptsArray) {
    //Declarations of month data for later use
    let firstDay = (new Date(year, month)).getDay();
    let daysInMonth = 32 - new Date(year, month, 32).getDate();
    var monthName = months[month];

    //creation of seperate array that stores included patient's ids in a numerically indexed array
    var stringArray = JSON.stringify(ApptsArray);
    var w = 0;
    var numberOfKeys = Object.keys(ApptsArray).length;
    var patientIdArray = new Array();

    for (var z = 1; z <= Object.keys(ApptsArray).length + 1; z++) {
        var searchPattern = `"${z}":`;
        if (stringArray.search(searchPattern) != -1) {
            patientIdArray[w] = z;
            w++;
        } else {
            continue;
        }
    }

    //Creation of the body, table, table body and top row for headers
    var body = document.getElementsByTagName('body')[0];
    var tbl = document.createElement('table');
    tbl.id = "calendars";
    var tbdy = document.createElement('tbody');
    var headerRow = document.createElement('tr');

    //Left button
    var theader = document.createElement('th');
    theader.classList = "calendarHeader";
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
    theader2.classList = "calendarHeader";
    theader2.setAttribute('colspan', '5')
    theader2.appendChild(document.createTextNode(monthName));
    theader2.appendChild(document.createTextNode(` ${year}`));

    //Right button
    var theader3 = document.createElement('th');
    theader3.classList = "calendarHeader";
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
        row.classList = "calendarRow";

        //creating individual cells, filing them up with data.
        for (let j = 0; j < 7; j++) {

            //Adds the blank spaces before the actual dates start appearing
            if (i === 0 && j < firstDay) {
                let cell = document.createElement("td");
                cell.classList = "calendarData";
                let cellText = document.createTextNode("");
                cell.appendChild(cellText);
                row.appendChild(cell);
            }

            //When the current date is greater than the days in the month the function stops
            else if (date > daysInMonth) {
                break;
            } else {
                let cell = document.createElement("td");
                cell.classList = "calendarData";
                var container = document.createElement("p");
                container.id = date;
                container.addEventListener('click', function() {
                    magicDivision(this.id);
                });


                if (date === today.getDate() && year === today.getFullYear() && month === today.getMonth()) {
                    container.style.color = "magenta";
                }

                let dateText = document.createTextNode(date);
                container.appendChild(dateText);
                const apptInfo = document.createElement('span');
                apptInfo.classList = "smallFont";

                for (i = 0; i < patientIdArray.length; i++) {

                    const value = ApptsArray[patientIdArray[i]];

                    if (!value) {
                        break;
                    }

                    for (let y = 0; y < value.length; y++) {
                        let minute = value[y][0];
                        let hour = value[y][1];
                        let apptDay = value[y][2];
                        let apptMonth = value[y][3];
                        let apptYear = value[y][4];

                        if (date == apptDay && apptMonth == currentMonth + 1 && apptYear == currentYear) {
                            if (hour > 12) {
                                hour = hour - 12;
                                var oClock = "PM";
                            } else {
                                var oClock = "AM";
                            }
                            apptTime = ` View Appts`;
                            apptInfo.appendChild(document.createTextNode(apptTime));
                            container.appendChild(apptInfo);
                            break;
                        }
                    }
                }
                cell.appendChild(container);
                row.appendChild(cell);
                date++;

                //Adds the blank spaces after the dates to fill the calendar completely
                if (date > daysInMonth && j < 7) {
                    for (let z = 0; z < 6 - j; z++) {
                        let cell = document.createElement("td");
                        cell.classList = "calendarData";
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

function magicDivision(dayOfApp) {

    var div = document.getElementById('magiDiv');

    var magTable = document.createElement('table');

    var magTableBody = document.createElement('tbody');

    for (let z = 0; z < 3; z++) {

        var magRow = document.createElement('tr');

        for (let q = 0; q < 3; q++) {

            var magData = document.createElement('td');

            var par = document.createElement('p')

            par.appendChild(document.createTextNode(dayOfApp))

            magData.appendChild(par);

            magRow.appendChild(magData);

        }

        magTableBody.appendChild(magRow);
    }

    magTable.appendChild(magTableBody);

    div.appendChild(magTable);

}