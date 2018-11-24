package main

import (
	"tag"
	"flag"
	"os"
	"fmt"
	"encoding/json"
	"strings"
	"encoding/base64"
	//"github.com/djimenez/iconv-go"
	//"github.com/djimenez/iconv-go"
)

/*
/mnt/hgfs/mp3/流行歌曲/01.迴.mp3
*/
func main()  {
	fpath := flag.String("f", "", "file path")
	flag.Parse()

	lowername := strings.ToLower(*fpath)
	if  strings.HasSuffix(lowername, "wav") {
		fmt.Printf("[error]ignore wav file:%s", *fpath)
		return
	}
	f, err := os.Open(*fpath)
	if err!= nil {
		fmt.Printf("[error]file open %s error:%s", *fpath, err.Error())
		return
	}
	m, err := tag.ReadFrom(f)
	if err != nil {
		fmt.Printf("[error]file:%s read tag error:%s", *fpath, err.Error())
		return
	}
	info := map[string]string{
		"title": base64.StdEncoding.EncodeToString([]byte(m.Title())),
		"artist": base64.StdEncoding.EncodeToString([]byte(m.Artist())),
	}

	strInfo, _ := json.Marshal(info)
	//fmt.Printf(m.Title()+"\n")
	//ret := base64.StdEncoding.EncodeToString(strInfo)
	//output, err:= iconv.ConvertString(m.Title(), "gbk", "utf-8")
	//fmt.Println("convert:", output, "err:", err)
	fmt.Printf(string(strInfo))
}