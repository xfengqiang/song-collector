
### 工具库
1. 实现go程序，支持从文件中读取元数据信息信息，输入参数为文件名，输出参数为元数据信息json数据，支持wav,mp3,flac格式
2. PHP实现文件整理
如果从文件中找到了歌曲名字和歌手，文件重命名为 "歌手名字 - 歌曲名";
如果两者任意一个为空
    如果文件名签名有数字，替换为正常名字xxx.mp3，排名前缀有
```
1. xxx.mp3
01 xxx.mp3
12.xxx.mp3
```
3. 目录整理
把整理后的文件移动到一个公共目录下，未找到歌手的放到单独的目录下
如果有重复，则删除源文件，统计重复文件数量

### 遇到的问题
- mp3中的元数据编码有utf8,有gbk, golang jsonencode只能正确处理utf8编码, 导致提取后的信息乱码。
解决办法 ： golang进行 json编码 之前先进行base64, 利用php的 mb_detect_encoding方法 检查字符编码，并做适当的转码
- php exec 路径名中包含引号空格等特殊字符
解决办法：使用escapeshellarg进行转义


### 引用库
[wav解析工具](https://github.com/NeowayLabs/waveparser)
[mp3+flac解析](https://github.com/dhowden/tag)
