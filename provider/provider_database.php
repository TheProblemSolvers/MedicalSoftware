<!DOCTYPE html>
<html lang="en-US">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Medical Software</title>
        <script src="../scripts.js"></script>
    </head>
    <body onload="displayPatientRecord(1)">
        <button onclick="document.location='provider_lander.php'">Back to Lander</button>
        <style>
        table, th, td {
            border: 1px solid black;
            border-collapse: collapse;
            padding: 5px;
        }
        </style>
        <h1>Patient Records</h1>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Sex</th>
                    <th>Age</th>
                    <th>Height</th>
                    <th>Weight</th>
                </tr>
            </thead>
            <tbody id="patientDatabase"></tbody><!--JavaScript fills this division w patient data-->
        </table>
    </body>
</html>