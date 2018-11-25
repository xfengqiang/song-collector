<?php

require __DIR__.'/common.php';

//按二级目录整理
function collectByDirname($dir, $okDir, &$stat)
{
    echo "processing {$dir}\n";
    if(!is_dir($dir)) {
        return;
    }
    //1、首先先读取文件夹
    $temp = scandir($dir);
    //遍历文件夹
    foreach ($temp as $v) {
        if($v == "." || $v=="..") {
            continue;
        }

        $fpath = $dir . '/' . $v;
        if (is_dir($fpath)) {//如果是文件夹则执行
            collectByDirname($fpath, $okDir, $stat);
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

function collectDir() {
    $stat = ["total"=>0, "ok"=>0, "fail"=>0, "dup"=>0];
    $okDir = "/mnt/hgfs/mp3/en";
    $list = [
        "/mnt/hgfs/mp3-d/英文歌手",
        "/mnt/hgfs/mp3-d/明星专辑+CD歌曲",
    ];
    foreach ($list as $srcDir) {
        collectByDirname($srcDir, $okDir, $stat);
    }

    echo "finished. stat:".json_encode($stat)."\n";
}
collectDir();