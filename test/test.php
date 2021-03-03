<?php

function testFunction($data){
    if(preg_match("/\s/", $data) == 1){
        return false;
    }
    else{
        return true;
    }
}