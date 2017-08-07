# PHP-TrieTree
## 这是一个PHP的字典树

- v1.0
	- 命中一个后返回
- v2.0
	- 支持命中多个返回
	- 支持在树梢增加自定义数组 [替换内容] 
	- 性能提升10倍
- v3.0
    - 增加删除特性 
    - 解决命中不全BUG

## 示例
```php
<?php
require "../src/TrieTree.php";


$testArr = array("张三","张四","王五","张大宝","张三四","张氏家族","王二麻子");

$tree = new \AbelZhou\Tree\TrieTree();

foreach ($testArr as $str){
    $tree->append($str);
}

$res = $tree->getTree();

var_dump($res);

$res = $tree->search("有一个叫张三的哥们");
var_dump($res);

$res = $tree->search("我叫李四喜");
var_dump($res);

//删除
$res = $tree->delete("张三");
$res = $tree->search("张三");
var_dump($res);
```

## 使用场景
- 敏感词过滤
- 内链建设

## 性能
test目录下有个1.5w左右的敏感词。
mac下检索耗时2~5毫秒左右
这些敏感词来自网络，不是很全。

## composer安装
```
composer require abelzhou/php-trie-tree
```

