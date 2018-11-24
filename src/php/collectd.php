<?php

function getNewName($name) {
    if(mb_substr($name, 0, 3, 'utf8')=="10叔"){
        return ltrim($name);
    }
    return ltrim($name, " 0123456789.");
}


function mvFile($from, $to) {
//    exec("mv -f ".escapeshellarg($from)." ".escapeshellarg($to), $ret, $status);
//    return $status==0;
    return rename($from, $to);
}

$stat = ["total"=>0, "ok"=>0, "fail"=>0, "dup"=>0];
//按二级目录整理
function collectD($dir, $okDir, &$stat)
{
    echo "processing {$dir}\n";
    //1、首先先读取文件夹
    $temp = scandir($dir);
    //遍历文件夹
    foreach ($temp as $v) {
        if($v == "." || $v=="..") {
            continue;
        }

//        if($stat["total"] >= 100) {
//            break;
//        }

        $fpath = $dir . '/' . $v;
        if (is_dir($fpath)) {//如果是文件夹则执行
            collectD($fpath, $okDir, $stat);
        } else {
            if (strpos($v, 'downloading')) {
                echo  "[ignore downloading] $fpath\n";
                continue ;
            }

            $stat["total"]++;
            $srcName =  basename($fpath);
            if(strpos($srcName, '-') === false) {
                $destName = basename(dirname($fpath))." - {$srcName}";
            }else{
                $destName = $srcName;
            }
            $destName = getNewName($destName);

            $descFile = $okDir."/".$destName;
            $status = mvFile($fpath, $descFile);
            $status = intval($status);
            echo "[mvret=$status] '$fpath' => '$descFile'\n";
        }
    }
}

$okDir = "/mnt/hgfs/mp3-d/明星专辑+CD歌曲/ok";
$list = [
    "/mnt/hgfs/mp3-d/明星专辑+CD歌曲/MP3按字母选择歌手",
    "/mnt/hgfs/mp3-d/明星专辑+CD歌曲/部分歌手列表",
];
//$srcDir = "/mnt/hgfs/mp3/test";
foreach ($list as $srcDir) {
    collectD($srcDir, $okDir, $stat);
}

echo "finished. stat:".json_encode($stat)."\n";
//findWrongFiles($okDir, $wrongDir);