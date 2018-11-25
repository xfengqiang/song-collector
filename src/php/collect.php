<?php
require __DIR__.'/common.php';

define('AUDIO_INFO_BIN', '/data/wk/go/audioinfo/src/audioinfo/audioinfo');

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
    $total = 0;
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
                $total++;
                rename($fpath,$destDir."/".$srcName);
            }
        }
    }
    echo "total wrong:$total\n";
}

//提取歌曲中的meta信息，并重命名文件
function cleanFolder(){
    $stat = ["total"=>0, "ok"=>0, "fail"=>0, "dup"=>0];
    $configs = [
//        ['src'=>"/mnt/hgfs/mp3-d/明星专辑+CD歌曲", 'ok'=>'', 'other'=>"/mnt/hgfs/mp3-d/明星专辑+CD歌曲", 'force'=>0]
        ['src'=>"/mnt/hgfs/mp3/暂不处理/2017/mom", 'ok'=>'/mnt/hgfs/mp3/mom', 'other'=>"/mnt/hgfs/mp3/mom", 'force'=>0]
    ];

    foreach ($configs as $item){
        $okDir = $item['ok'];
        $otherDir = $item['other'];;
        $srcDir = $item['src'];
        cleanDir($srcDir, $okDir, $otherDir, $item['force'], $stat);
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

function formatName($list, &$stat) {
    foreach ($list as $dir){
        $files = scandir($dir);
        echo "processing dir $dir\n";
        foreach ($files as $file){
            if($file=="." || $file==".."){
                continue;
            }
            $path = $dir."/".$file;
            if(is_dir($path)){
                formatName([$path], $stat);
            }else{
                $srcName = basename($path);

                $newName = getNewName($srcName);

                if($srcName!=$newName){
                    $newPath = "$dir/{$newName}";
                    if(is_file($newPath)){
                        $stat['remove']++;
                        @unlink($path);
                        echo "[remove dup] {$path}=>{$newPath}\n";
                    }else{
                        echo "[rename] {$path}=>{$newPath}\n";
                        mvFile($path, $newPath);
                        $stat['rename']++;
                    }

                }

            }
        }
    }
}

//规范化英文歌名
function formatEnName($list) {
    $stat = [];
    foreach ($list as $dir){
        $files = scandir($dir);
        echo "processing dir $dir\n";
        foreach ($files as $file){
            if($file=="." || $file==".."){
                continue;
            }
            $path = $dir."/".$file;
            if(is_dir($path)){
                continue;
            }else{
                $srcName = basename($path);
                $replaceStr = [
                    ['k'=>'火星哥 Bruno Mars', 'v'=>'Bruno Mars'],
                    ['k'=>'迈克杰克逊', 'v'=>'Michanel Jackson'],
                    ['k'=>'阿黛尔  Adele', 'v'=>'Adele'],
                    ['k'=>'后街男孩   Backstreet boys', 'v'=>'Backstreet boys'],
                    ['k'=>'艾薇儿.拉维尼 Avril Lavigne', 'v'=>'Avril Lavigne'],
                ];
                $newName = $srcName;
                foreach ($replaceStr as $item) {
                    $newName = str_replace($item['k'], $item['v'], $newName);
                }

                if($srcName!=$newName){
                    $newPath = "$dir/{$newName}";
                    if(is_file($newPath)){
                        $stat['remove']++;
                        @unlink($path);
                        echo "[remove dup] {$path}=>{$newPath}\n";
                    }else{
                        echo "[rename] {$path}=>{$newPath}\n";
                        mvFile($path, $newPath);
                        $stat['rename']++;
                    }

                }

            }
        }
    }
    echo "stat:".json_encode($stat)."\n";
}


//通过读取元数据，重命名文件
cleanFolder();


//移动文件目录
//moveFiles();

////规范化英文歌名
//formatEnName(['/mnt/hgfs/mp3/en']);

////规范化文件名
//$stat=[];
//formatName(['/mnt/hgfs/mp3-d/ok', '/mnt/hgfs/mp3/ok','/mnt/hgfs/mp3/other'], $stat);
//echo "format stat:".json_encode($stat)."\n";

//找出乱码文件
//$strDir = '/mnt/hgfs/mp3-d/ok';
//$wrongDir = '/mnt/hgfs/mp3/wrong';
//findWrongFiles($strDir, $wrongDir);