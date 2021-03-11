<?php

function accessUserDatabase($userId, $openType){
    $fileName = "../users\user_data\#" . $userId . ".txt";
    $fileHandle = fopen($fileName, $openType);
    return $fileHandle;
}

function checkDuplicates($userId, $data){
    //$userId = 3;
    //$data = "ProviderAccount";
    $fileHandle = accessUserDatabase($userId, "r");
    $searchParameter = "/" . $data . "/i";
    while(feof($fileHandle) == false){
        $lineContents = fgets($fileHandle);
        if(preg_match($searchParameter, $lineContents) == 1){
            fclose($fileHandle);
            return true;
        }
    }
    fclose($fileHandle);
    return false;
}

function testFunction(){
    $userId = 3;
    $linkId = 5;
    if(checkDuplicates($userId, "ProviderAccount") == false){
        $fileHandle = accessUserDatabase($userId, "a");
        if(file_exists("../users\user_data\#" . $linkId . ".txt") != false){
            $linkData = "ProviderAccount=" . strval($linkId) . "\n";
            fwrite($fileHandle, $linkData);
            fclose($fileHandle);
            return "true";
        }
        else{
            return "Provider ID does not exist.";
        }
    }
    else{
        return "Provider ID already linked to this account.";
    }    
}