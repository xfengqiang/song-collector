<?php
define('AUDIO_INFO_BIN', '/data/wk/go/audioinfo/src/audioinfo/audioinfo');

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
    if(mb_substr($name, 0, 3, 'utf8')=="10叔"){
        return ltrim($name);
    }
    return ltrim($name, " 0123456789.");
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

function getMetaInfo($info) {
    $meta = json_decode($info);
    if(!$meta) {
        return false;
    }
    $ret = [];
    foreach ($meta as $k=>$v) {
        $v = base64_decode($v);
        $encode = mb_detect_encoding($v, array('UTF-8',"GB2312","GBK","gb18030"));
        if($encode!='UTF-8'){
            $v = mb_convert_encoding($v,'UTF-8', $encode);
        }
        if(isValid($v)) {
            $v = str_replace("/", ",", trim($v));
            $ret[$k] = $v;
        }else{
            $ret[$k] = "";
        }

    }
    return $ret;
}

function mvFile($from, $to) {
    return rename($from, $to);
}


function cleanDir($dir, $okDir, $otherDir, $enableOverwrite, &$stat)
{
    echo "checking dir: $dir\n";
    //1、首先先读取文件夹
    $temp = scandir($dir);
    //遍历文件夹
    foreach ($temp as $v) {
        if($v == "." || $v=="..") {
            continue;
        }
//        if(  $stat["total"] >= 100) {
//            break;
//        }
        $fpath = $dir . '/' . $v;
        if (is_dir($fpath)) {//如果是文件夹则执行
            cleanDir($fpath, $okDir, $otherDir, $enableOverwrite, $stat);
        } else {
            if (strpos($v, 'downloading')) {
                echo  "[ignore downloading] $fpath\n";
                continue ;
            }

            $stat["total"]++;

            $fpath = realpath($fpath);
            $argPath = escapeshellarg($fpath);
            $info = exec(AUDIO_INFO_BIN." -f $argPath", $ret, $status);
            if($status != 0){
                echo "[error] get meta:".json_encode($ret)."\n";
            }

            $meta = getMetaInfo($info);
            $srcName =  basename($fpath);
            $overwrite = 0;

            $byMeta = true;
            if(!is_array($meta) ){
                $byMeta = false;
            }else{
                $wrongList = ['unknown', "未知"];
                foreach ($meta as $key=>$v) {
                    if($v==""){
                        $byMeta = false;
                        break;
                    }
                    if(in_array(strtolower($v), $wrongList)){
                        $byMeta = false;
                        break;
                    }
                    if(strpos("/", $v)!==false){
                        $byMeta = false;
                        break;
                    }
                }
            }

            if(!$byMeta) {
                $destName = getNewName($srcName);
                $stat['fail']++;
                $ok = strpos($destName, '-')>0;
            }else{
                $stat['ok']++;
                $overwrite = true;
                $parts = explode('.',  $srcName);
                $ext = strtolower($parts[count($parts)-1]);
                $title = getNewName($meta['title']);
                $destName  = "{$meta['artist']} - {$title}.{$ext}";
                $ok = true;
            }

            if($ok){
                $descFile = $okDir."/".$destName;
            }else{
                $descFile = $otherDir."/".$destName;
            }

            $ok = intval($ok);
            echo "[overwrite=$overwrite ok=$ok] '$fpath' => '$descFile'\n";

//            var_dump($meta, "encoding:", $encode);
            if(is_file($descFile)) {
                $stat['dup']++;
                if($enableOverwrite && $overwrite){
                    @unlink($descFile);
                    $status = mvFile($fpath, $descFile);
                }else{
                    $status = true;
                    echo "[remove-dup] $fpath\n";
                    @unlink($fpath);
                }
            }else{
                $status = mvFile($fpath, $descFile);
            }

            if(!$status) {
                echo "[mv fail] $fpath\n";
            }else{
                echo "[mv ok] $fpath\n";
            }
        }

    }
}

//把乱码文件移动到单独的目录
function findWrongFiles($dir, $destDir) {
    //1、首先先读取文件夹
    $temp = scandir($dir);
    //遍历文件夹
    foreach ($temp as $v) {
        $fpath = $dir . '/' . $v;
        if (is_dir($fpath)) {//如果是文件夹则执行
            echo "[ignore dir] $fpath\n";
        } else {

            $fpath = realpath($fpath);

            $srcName =  basename($fpath);
            if(!isValid($srcName)) {
                echo "wrong file  $srcName\n";
                rename($fpath,$destDir."/".$srcName);
            }
        }
    }
}


function cleanFolder(){
    $stat = ["total"=>0, "ok"=>0, "fail"=>0, "dup"=>0];
    $okDir = "/mnt/hgfs/mp3/ok";
    $otherDir = "/mnt/hgfs/mp3/other";
    $list = [
        "/mnt/hgfs/mp3-d/明星专辑+CD歌曲",
    ];
    foreach ($list as $srcDir) {
        cleanDir($srcDir, $okDir, $otherDir, 0, $stat);
    }

//findWrongFiles($okDir, $wrongDir);
    echo "stat:".json_encode($stat)."\n";
}

//移动文件
function moveFiles(){
    $list = [
        ['src'=>"/mnt/hgfs/mp3-d/ok", 'dest'=>"/mnt/hgfs/mp3/ok", 'force'=>1],
        ['src'=>"/mnt/hgfs/mp3-d/other", 'dest'=>"/mnt/hgfs/mp3/other", 'force'=>0],
    ];

    $stat = ["total"=>0, "dup"=>0, "add"=>0, "overwrite"=>0];

    foreach ($list as $item) {
        $srcDir = $item['src'];
        $destDir = $item['dest'];

        $temp = scandir($srcDir);
        //遍历文件夹
        foreach ($temp as $v) {
            if ($v == "." || $v == "..") {
                continue;
            }
            $stat["total"]++;
            $fpath  = $srcDir."/".$v;
            $destName = basename($fpath);
            $destPath = $destDir."/".$destName;
            if(is_file($destPath)){
                if($item['force']) {
                    @unlink($destPath);
                    mvFile($fpath, $destPath);
                    echo "[overwrite] $fpath => $destPath\n";
                    $stat["overwrite"]++;
                }else{
                    echo "[remove dup] $fpath\n";
                    @unlink($destPath);
                    $stat["dup"]++;
                }

            }else{
                $stat["add"]++;
                echo "[mv] $fpath => $destPath\n";
                mvFile($fpath, $destPath);
            }
        }
    }

    echo "stat:".json_encode($stat);

}

moveFiles();