<?php
/**
 *
 * Author: abel
 * Date: 17/4/10
 * Time: 22:08
 */


require "../src/TrieTree.php";

$str = file_get_contents("./dict.txt");
$words_arr = explode("\n", $str);


$tree = new \AbelZhou\Tree\TrieTree();

foreach ($words_arr as $str) {
    $tree->addWord($str);
}

$str = <<<EOF
张天明说道，“不劳而获是走不通的。希望大家不要在错误道路上越走越远，能够真正意识到，这种模式的危害。”唐捷克隆" 
EOF;
$t1 = microtime(true);
var_dump($tree->search($str));
$t2 = microtime(true);
echo 'SearchTime{' . ($t2 - $t1) . '}s'.PHP_EOL;

$del_res = $tree->deleteWord("捷克", true);
$t3 = microtime(true);
echo 'DELETE RES:'.$del_res.PHP_EOL;
var_dump($tree->search($str));

