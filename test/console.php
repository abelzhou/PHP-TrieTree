<?php
/**
 *
 * Author: abel
 * Date: 17/4/10
 * Time: 22:08
 */


require "../src/TrieTree.php";


$testArr = array("张三","张四","王五","张大宝","张三四","张氏家族","王二麻子");

$trieTree = new \AbelZhou\Tree\TrieTree();

foreach ($testArr as $str){
    $trieTree->append($str);
}

$res = $trieTree->getTree();

var_dump($res);

$res = $trieTree->search("有一个叫张三的哥们");
var_dump($res);

$res = $trieTree->search("我叫李四喜");
var_dump($res);