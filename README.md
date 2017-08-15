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
        - 删除整棵关键词树
    - 解决命中不全BUG
    - 3.1
        - 增加词频统计

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
//删除整棵树 连带“张三”和张三下的“张三四”一并删除
$tree->delete("张三",true);
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

