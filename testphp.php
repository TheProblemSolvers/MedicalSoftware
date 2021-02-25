<?php

#define the string to write and read from the .txt file
$var = "Medical Software";

#creates/opens a txt file and writes to the first line
$fileLocation = fopen("testfile.txt", "w");
fwrite($fileLocation, $var);
fclose($fileLocation);

#opens the file just created and reads the first line
$fileLocation = fopen("testfile.txt", "r");
$recievedVar = fread($fileLocation, strlen($var));

#send read string to the browser to display
echo $recievedVar;