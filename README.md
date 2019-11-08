# MoeTransfer
快速在两台没有公网 IP 的 Linux 服务器之间传输文本或其他数据（服务器与客户端源码）

本项目纯个人娱乐和实验性质，实际传输文件效率很低，传一些文本还可以，不推荐用于生产环境。

## 客户端使用方法
> 正确使用方法：先运行接收端，然后再启动发送端发送数据。

首先确认系统有以下命令：
- curl
- base64
- cat

然后下载安装，第一步是将项目 clone 到本地：
```bash
git clone https://github.com/kasuganosoras/MoeTransfer/ /usr/local/MoeTransfer/
```
接着为客户端设置可执行权限
```bash
chmod -R 755 /usr/local/MoeTransfer/*.sh
```
（可选）编辑 bashrc，增加快捷命令
```bash
alias mg='/usr/local/MoeTransfer/moeget.sh' # 接收端
alias ms='/usr/local/MoeTransfer/moesend.sh' # 从标准输入读取并发送数据
alias msf='/usr/local/MoeTransfer/moesendfile.sh' # 发送文件
```
### moeget.sh
用于接收文件或数据，命令格式：（直接执行就行）
```bash
moeget.sh
```
可以将输出内容重定向，实现保存文件
```bash
moeget.sh > test.txt
```

### moesend.sh
用于从标准输入读取数据并发送给接收端，命令格式：
```bash
moesend.sh 接收端IP地址
```
使用例子：读取 Nginx 日志并发给 `12.34.56.78`
```bash
cat nginx_access.log | ./moesend.sh 12.34.56.78
```

### moesendfile.sh
用于读取文件并发送给接收端，实际上就是自动帮你用 cat 命令把文件读入 stdin 了：
```bash
moesendfile.sh 接收端IP地址 文件名
```
使用例子：读取 mydata.zip 并发给 `12.34.56.78`
```bash
./moesend.sh 12.34.56.78 mydata.zip
```

## 服务器搭建方法
首先要准备好：
1. PHP 7.0+ 版本
2. Swoole 扩展支持

然后安装：
- 将项目 clone 到本地
- 运行命令 `php server.php`

## 注意事项
官方服务器限制了文件最大大小为 32MB，超出后会主动关闭连接

官方服务器限制了接收时间最长为 2 分钟，超出后会主动关闭连接

以上限制你都可以通过自己搭建服务器然后修改源码解除限制。

## 开源协议
本项目使用 GPL v3 协议开放源代码
