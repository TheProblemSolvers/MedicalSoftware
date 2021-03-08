<?php

function testFunction(){
    $lineContents = "p12938469369.FirstName=Patient hurt his knee while jumping on a track.";
    $startRead = strpos($lineContents, ".") + 1;
    $endRead = strpos($lineContents, "=");
    return substr($lineContents, $startRead, ($endRead - $startRead));
    //return $endRead;
}