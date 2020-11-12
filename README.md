<h1><p align="center">novel</p></h1>
<p align="center"></p>


## 环境要求

1. php >= 7.0
2. php 需要安装 curl 和 mb_string 扩展

## 说明
> 获取在线小说的内容和标题，章节目录，过滤广告，仅供学习交流使用

## 安装

```shell
$ composer require lffwl/novel
```

## 使用方式

示例：

```php
//框架开发可以省略一般会自动引入
require_once './vendor/autoload.php';

$NovelRead = new \Lffwl\Novel\NovelRead();
//获取小说内容
//catalog - 目录地址 ，upper - 上一章地址，next - 下一章地址
$data = $NovelRead->toRead('小说具体某一章url地址');
//获取小说章节
$catalogData = $NovelRead->catalog($data['catalog']);
        
```

## License

Apache-2.0
