<?php

function mvFile($from, $to) {
//    exec("mv -f ".escapeshellarg($from)." ".escapeshellarg($to), $ret, $status);
//    return $status==0;
    return rename($from, $to);
}


function testNewName(){
    $names = [
        '02 夜空中最亮的星.WAV',
        '10.华晨宇 - 我管你.mp3',
        '01. 星星小夜曲 SERENADE DELETOILE_track1.mp3',
        '01旧情绵绵.mp3',
        '10叔 - 山坡上的两头牛.flac'
    ];

    foreach ($names as $n){
        echo "$n => [".getNewName($n)."]\n";
    }
}

function getNewName($name) {
    //高凌风(1) - 不忍心让你走.mp3 => 高凌风(1) - 不忍心让你走.mp3
    if(preg_match('/\(\d\)/i', $name, $matches)) {
        $name[0] = trim(preg_replace('/\(\d\)/i', '', $name));
    }

    //Justin Bieber (贾斯汀比伯) - Where Are You Now_.mp3=>Justin Bieber  - Where Are You Now_.mp3
    $parts = explode('-', $name);
    if(count($parts)==2) {
        $replaceList =['(贾斯汀比伯)'];
        foreach ($replaceList as  $str){
            $parts[0] = str_replace($str, '', $parts[0]);
        }
    }

    $newParts = [];
    foreach ($parts as $part){
        $part = trim($part);
        if($part!=''){
            $newParts[] = $part;
        }
    }
    $name = implode(" - ", $newParts);

    //去掉开头的数字排名
    if(mb_substr($name, 0, 3, 'utf8')=="10叔"){
        $name = ltrim($name);
    }else{
        $name = ltrim($name, " 0123456789.");
    }
    return $name;
}


function isValid($pstr){
    //utf8
    if (preg_match("/^([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}/",$pstr) == true
        || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}$/",$pstr) == true
        || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){2,}/",$pstr) == true){
        return true;
    }
    //ascii
    return !preg_match('/[^\x20-\x7f、]/', $pstr);
}
