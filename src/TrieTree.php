<?php
/**
 *
 * Author: abel
 * Date: 17/4/10
 * Time: 17:26
 */

namespace AbelZhou\Tree;

/**
 * 这是一个支持中文的字典树
 * Class TrieTree
 * @package AbelZhou\Tree
 */
class TrieTree {
    protected $nodeTree = [];

    /**
     * 构造
     * TrieTree constructor.
     */
    public function __construct() {

    }

    /**
     * ADD words [UTF8]
     * 增加新特性，在质感末梢增加自定义数组
     * @param $str 添加的词
     * @param array $data 添加词的附加属性
     * @return $this
     */
    public function append($str, $data = array()) {
        $str = trim($str);
        $childTree = &$this->nodeTree;
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $ascii_code = ord($str[$i]);
            $code = NULL;
            $word = NULL;
            $is_end = false;

            if (($ascii_code >> 7) == 0) {
                $code = dechex(ord($str[$i]));
                $word = $str[$i];

            } else if (($ascii_code >> 4) == 15) {    //1111 xxxx, 四字节
                if ($i < $len - 3) {
                    $code = dechex(ord($str[$i])) . dechex(ord($str[$i + 1])) . dechex(ord($str[$i + 2])) . dechex(ord($str[$i + 3]));
                    $word = $str[$i] . $str[$i + 1] . $str[$i + 2] . $str[$i + 3];
                    $i += 3;
                }
            } else if (($ascii_code >> 5) == 7) {    //111x xxxx, 三字节
                if ($i < $len - 2) {
                    $code = dechex(ord($str[$i])) . dechex(ord($str[$i + 1])) . dechex(ord($str[$i + 2]));
                    $word = $str[$i] . $str[$i + 1] . $str[$i + 2];
                    $i += 2;
                }
            } else if (($ascii_code >> 6) == 3) {    //11xx xxxx, 2字节
                if ($i < $len - 1) {
                    $code = dechex(ord($str[$i])) . dechex(ord($str[$i + 1]));
                    $word = $str[$i] . $str[$i + 1];
                    $i++;
                }
            }
            if ($i == ($len - 1)) {
                $is_end = true;
            }
            $childTree = &$this->_appendWordToTree($childTree, $code, $word, $is_end, $data, $str);

        }
        unset($childTree);
        return $this;
    }

    /**
     * 最佳一个字[中英文]到树中
     * @param $tree
     * @param $code
     * @param $word
     * @param bool $end
     * @param array $data
     * @param string $full_str
     * @return mixed
     */
    private function &_appendWordToTree(&$tree, $code, $word, $end = false, $data = array(), $full_str = '') {
        if (!isset($tree[$code])) {
            $tree[$code] = array(
                'end' => $end,
                'child' => array(),
                'value' => $word
            );
        }
        if ($end) {
            $tree[$code]['end'] = true;
            $tree[$code]['data'] = $data;
            $tree[$code]['full'] = $full_str;
        }

        return $tree[$code]['child'];
    }

    /**
     * 获得整棵树
     * @return array
     */
    public function getTree() {
        return $this->nodeTree;
    }

    /**
     * overwrite tostring.
     * @return string
     */
    public function __toString() {
        // TODO: Implement __toString() method.
        return json_encode($this->nodeTree);
    }


    /**
     * 检索
     * @param $search
     * @return array|bool
     */
    public function search($search) {
        $search = trim($search);
        if (empty($search)) {
            return false;
        }
        $search_keys = $this->_convertStrToH($search);
        //命中集合
        $hit_arr = array();
        $tree = $this->nodeTree;
        $arr_len = count($search_keys);
        $current_index = 0;
        for ($i = 0; $i < $arr_len; $i++) {
//            print_r($i . '+' . PHP_EOL);
            //若命中了一个索引 则继续向下寻找
            if (isset($tree[$search_keys[$i]])) {
                $node = $tree[$search_keys[$i]];
                if ($node['end']) {
                    //发现结尾 将原词以及自定义属性纳入返回结果集中
                    $hit_arr[md5($node['full'])] = array(
                        'words' => $node['full'],
                        'data' => $node['data']
                    );
                    if (empty($node['child'])) {
                        //若不存在子集，检索游标还原，字码游标下移
                        $i = $current_index;
                        $current_index++;
                        continue;
                    } else {
                        //存在子集重定义检索tree
                        $tree = $tree[$search_keys[$i]]['child'];
                        $current_index++;
                        continue;
                    }
                } else {
                    //没发现结尾继续向下检索
                    $tree = $tree[$search_keys[$i]]['child'];
                    continue;
                }
            } else {
                //还原tree
                $current_index++;
                $tree = $this->nodeTree;
                continue;
            }
        }

        unset($tree, $search_keys);
        return $hit_arr;

    }

    /**
     * 将字符转为16进制标示
     * @param $str
     * @return array
     */
    private
    function _convertStrToH($str) {
        $len = strlen($str);
        $chars = [];
        for ($i = 0; $i < $len; $i++) {
            $ascii_code = ord($str[$i]);
            if (($ascii_code >> 7) == 0) {
                $chars[] = dechex(ord($str[$i]));
            } else if (($ascii_code >> 4) == 15) {    //1111 xxxx, 四字节
                if ($i < $len - 3) {
                    $chars[] = dechex(ord($str[$i])) . dechex(ord($str[$i + 1])) . dechex(ord($str[$i + 2])) . dechex(ord($str[$i + 3]));
                    $i += 3;
                }
            } else if (($ascii_code >> 5) == 7) {    //111x xxxx, 三字节
                if ($i < $len - 2) {
                    $chars[] = dechex(ord($str[$i])) . dechex(ord($str[$i + 1])) . dechex(ord($str[$i + 2]));
                    $i += 2;
                }
            } else if (($ascii_code >> 6) == 3) {    //11xx xxxx, 2字节
                if ($i < $len - 1) {
                    $chars[] = dechex(ord($str[$i])) . dechex(ord($str[$i + 1]));
                    $i++;
                }
            }
        }
        return $chars;
    }
}