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
     * 从树种摘除一个文本
     * @param $index_str
     */
    public function delete($index_str, $deltree = false, $is_py = false, $chinese = "") {
        $str = trim($index_str);
        $chinese = trim($chinese);
        if ($is_py && empty($chinese)) {
            return false;
        }

        $delstr_arr = $this->convertStrToH($str);
        $len = count($delstr_arr);
        //提取树
        $childTree = &$this->nodeTree;
        $del_index = array();
        //提取树中的相关索引
        for ($i = 0; $i < $len; $i++) {
            $code = $delstr_arr[$i];
            //命中将其纳入索引范围
            if (isset($childTree[$code])) {
                //del tree
                $del_index[$i] = [
                    'code' => $code,
                    'index' => &$childTree[$code]
                ];
                //若检索到最后一个字，检查是否是一个关键词的末梢
                if ($i == ($len - 1) && !$childTree[$code]['end']) {
                    return false;
                }
                $childTree = &$childTree[$code]['child'];
            } else {
                //发现没有命中 删除失败
                return false;
            }
        }
        $idx = $len - 1;
        //删除整棵树
        if ($deltree) {
            //清空子集
            $del_index[$idx]['index']['child'] = array();
        }
        //只有一个字 直接删除
        if ($idx == 0) {
            //如果是拼音 只删除相应的拼音索引
            if ($is_py) {
                //清除单个拼音索引
                if (isset($this->nodeTree[$del_index[$idx]['code']]['chinese_list'])) {
                    $is_del = false;
                    foreach ($this->nodeTree[$del_index[$idx]['code']]['chinese_list'] as $key=>$node) {
                        if ($node['word'] == $chinese){
                            unset($this->nodeTree[$del_index[$idx]['code']]['chinese_list'][$key]);
                            $is_del = true;
                            break;
                        }
                    }
                    if($is_del && 0 != count($this->nodeTree[$del_index[$idx]['code']]['chinese_list'])){
                         return true;
                    }
                    if(!$is_del){
                        return false;
                    }
                    //如果依然存在中文数据 则继续向下跑删除节点
                }
            }else{
                if (count($del_index[$idx]['index']['child']) == 0) {
                    unset($this->nodeTree[$del_index[$idx]['code']]);
                    return true;
                }
            }

        }
        //末梢为关键词结尾，且存在子集 清除结尾标签
        if (count($del_index[$idx]['index']['child']) > 0) {
            $del_index[$idx]['index']['end'] = false;
            $del_index[$idx]['index']['data'] = array();
            unset($del_index[$idx]['index']['full']);
            return true;
        }
//        var_dump($this->nodeTree['e59bbd']);exit();
        //以下为末梢不存在子集的情况
        //倒序检索 子集大于2的 清除child
        for (; $idx >= 0; $idx--) {
            //检测子集 若发现联字情况 检测是否为其他关键词结尾
            if (count($del_index[$idx]['index']['child']) > 0) {
                //遇到结束标记或者count>1的未结束节点直接清空子集跳出
                if ($del_index[$idx]['index']['end'] == true || $del_index[$idx]['index']['child'] > 1) {
                    //清空子集
                    $child_code = $del_index[$idx + 1]['code'];
                    unset($del_index[$idx]['index']['child'][$child_code]);
                    return true;
                }

            }
        }
        return false;
    }

    /**
     * ADD word [UTF8]
     * 增加新特性，在质感末梢增加自定义数组
     * @param $index_str 添加的词
     * @param array $data 添加词的附加属性
     * @return $this
     */
    public function append($index_str, $data = array(), $is_py = false, $chinese = '') {
        $str = trim($index_str);
        $chinese = trim($chinese);
        if ($is_py && empty($chinese)) {
            return false;
        }

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
                if ($is_py) {
                    $str = $chinese;
                }
            }
            $childTree = &$this->_appendWordToTree($childTree, $code, $word, $is_end, $data, $str, $is_py);
        }
        unset($childTree);
        return $this;
    }

    /**
     * 追加一个字[中英文]到树中
     * @param $tree
     * @param $code
     * @param $word
     * @param bool $end
     * @param array $data
     * @param string $full_str
     * @return mixed
     */
    private function &_appendWordToTree(&$tree, $code, $word, $end = false, $data = array(), $full_str = '', $is_py) {
        if (!isset($tree[$code])) {
            $tree[$code] = array(
                'end' => $end,
                'child' => array(),
                'value' => $word,
            );
        }
        if ($end) {
            $tree[$code]['end'] = true;
            $tree[$code]['is_py'] = $is_py;
            //拼音不需要full 拼音根据读音多样性对应多个词语 重复词语覆盖data
            if ($is_py) {
                $is_change = false;
                if(isset($tree[$code]["chinese_list"]) && count($tree[$code]["chinese_list"])>0) {
                    foreach ($tree[$code]["chinese_list"] as $key => &$node) {
                        if ($node['word'] == $full_str) {
                            $node['data'] = $data;
                            $is_change = true;
                            break;
                        }
                    }
                }
                if(!$is_change){
                    $tree[$code]['chinese_list'][] = ["word" => $full_str, "data" => $data];
                }
            } else {
                $tree[$code]['full'] = $full_str;
                $tree[$code]['data'] = $data;
            }
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
     * 匹配下面的全部词语
     * @param $word
     * @param int $deep 检索深度 检索之后的词语数量可能会大于这个数字
     * @return array|bool
     */
    public function getTreeWord($word, $deep = 0) {
        $search = trim($word);
        if (empty($search)) {
            return false;
        }
        if ($deep === 0) {
            $deep = 999;
        }

        $word_keys = $this->convertStrToH($search);
        $tree = &$this->nodeTree;
        $key_count = count($word_keys);
        $words = [];
        foreach ($word_keys as $key => $val) {
            if (isset($tree[$val])) {
                //检测当前词语是否已命中
                if ($key == $key_count - 1 && $tree[$val]['end'] == true) {
                    if (isset($tree[$val]['chinese_list'])) {
                        $words = array_merge($words, $tree[$val]['chinese_list']);
                    } else {
                        $words[] = ["word" => $tree[$val]['full'], "data" => $tree[$val]['data']];
                    }
                }
                $tree = &$tree[$val]["child"];
            } else {
                //遇到没有命中的返回
//                if ($key == 0) {
                    return [];
//                }
            }
        }
        $this->_getTreeWord($tree, $deep, $words);
        return $words;
    }

    private function _getTreeWord(&$child, $deep, &$words = array()) {
        foreach ($child as $node) {
            if ($node['end'] == true) {
                if (isset($node['chinese_list'])) {
                    $words = array_merge($words, $node['chinese_list']);
                } else {
                    $words[] = ["word" => $node['full'], "data" => $node['data']];
                }
            }
            if (!empty($node['child']) && $deep >= count($words)) {
                $this->_getTreeWord($node['child'], $deep, $words);
            }
        }
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
        $search_keys = $this->convertStrToH($search);
        //命中集合
        $hit_arr = array();
        $tree = &$this->nodeTree;
        $arr_len = count($search_keys);
        $current_index = 0;
        for ($i = 0; $i < $arr_len; $i++) {
            //若命中了一个索引 则继续向下寻找
            if (isset($tree[$search_keys[$i]])) {
                $node = $tree[$search_keys[$i]];
                if ($node['end']) {
                    //发现结尾 将原词以及自定义属性纳入返回结果集中 3.1 增加词频统计
                    $key = md5($node['full']);
                    if (isset($hit_arr[$key])) {
                        $hit_arr[$key]['count'] += 1;
                    } else {
                        $hit_arr[$key] = array(
                            'word' => $node['full'],
                            'data' => $node['data'],
                            'count' => 1
                        );
                    }
                    if (empty($node['child'])) {
                        //若不存在子集，检索游标还原
                        $i = $current_index;
                        //还原检索集合
                        $tree = &$this->nodeTree;
                        //字码游标下移
                        $current_index++;
                    } else {
                        //存在子集重定义检索tree
                        $tree = &$tree[$search_keys[$i]]['child'];
                    }
                    continue;
                } else {
                    //没发现结尾继续向下检索
                    $tree = &$tree[$search_keys[$i]]['child'];
                    continue;
                }
            } else {
                //还原检索起点
                $i = $current_index;
                //还原tree
                $tree = &$this->nodeTree;
                //字码位移
                $current_index++;
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
    public function convertStrToH($str) {
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
