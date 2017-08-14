<?php
/**
 *
 * Author: abel
 * Date: 17/4/10
 * Time: 17:26
 */

namespace AbelZhou\Tree;

/**
 * 支持"中文|英文|符号"的字典树
 * Class TrieTree
 *
 * @package AbelZhou\Tree
 */
class TrieTree {
	protected $tree  = [];
	protected $count = 0;

	/**
	 * TrieTree constructor.
	 *
	 * @param array $wordsMap ['keyword1' => ['key' => 'additionalInfo']]
	 */
	public function __construct(array $wordsMap = []) {
		foreach ($wordsMap as $word => $additionalInfo) {
			$this->addWord($word, $additionalInfo);
		}
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return json_encode($this->tree);
	}

	/**
	 * 获取字典树总词量
	 *
	 * @return int
	 */
	public function getCount() {
		return $this->count;
	}

	/**
	 * 获取字典树
	 *
	 * @return array
	 */
	public function getTree() {
		return $this->tree;
	}

	/**
	 * 设置字典树
	 *
	 * @param array $tree
	 */
	public function setTree(array $tree) {
		$this->tree = $tree;
	}

	/**
	 * 从字典树中摘除一个文本
	 *
	 * @param string $word
	 * @param bool   $delTree
	 * @return bool
	 */
	public function deleteWord($word, $delTree = false) {
		$word = trim($word);
		$delLetters = $this->convertStrToLetters($word);
		$len = count($delLetters);
		//提取树
		$childTree = &$this->tree;
		$delIndex = [];
		//提取树中的相关索引
		for ($i = 0; $i < $len; $i++) {
			$code = $this->getLetterCode($delLetters[$i]);
			//命中将其纳入索引范围
			if (isset($childTree[$code])) {
				//del tree
				$delIndex[$i] = [
					'code'  => $code,
					'index' => &$childTree[$code],
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
		if ($delTree) {
			//清空子集
			$delIndex[$idx]['index']['child'] = [];
		}
		//只有一个字 直接删除
		if ($idx == 0) {
			if (count($delIndex[$idx]['index']['child']) == 0) {
				unset($this->tree[$delIndex[$idx]['code']]);
				return true;
			}
		}
		//末梢为关键词结尾，且存在子集 清除结尾标签
		if (count($delIndex[$idx]['index']['child']) > 0) {
			$delIndex[$idx]['index']['end'] = false;
			$delIndex[$idx]['index']['data'] = [];
			unset($delIndex[$idx]['index']['full']);
			return true;
		}

		//以下为末梢不存在子集的情况
		//倒序检索 子集大于2的 清除child
		for (; $idx >= 0; $idx--) {
			//检测子集 若发现联字情况 检测是否为其他关键词结尾
			if (count($delIndex[$idx]['index']['child']) > 0) {
				//遇到结束标记或者count>1的未结束节点直接清空子集跳出
				if ($delIndex[$idx]['index']['end'] == true || $delIndex[$idx]['index']['child'] > 1) {
					//清空子集
					$child_code = $delIndex[$idx + 1]['code'];
					unset($delIndex[$idx]['index']['child'][$child_code]);
					return true;
				}

			}
		}
		return false;
	}

	/**
	 * 添加一个新词到字典树
	 *
	 * @param       $word  添加的词
	 * @param array $data  添加词的附加属性
	 * @return $this
	 */
	public function addWord($word, $data = []) {
		$word = trim($word);
		$childTree = &$this->tree;

		$letters = $this->convertStrToLetters($word);
		for ($i = 0; $i < count($letters); $i++) {
			$code = $this->getLetterCode($letters[$i]);
			$isEnd = $i === (count($letters) - 1);

			if (!isset($childTree[$code])) {
				$childTree[$code] = [
					'end'   => $isEnd,
					'child' => [],
					'value' => $letters[$i],
				];
			}
			if ($isEnd) {
				$childTree[$code]['end'] = true;
				$childTree[$code]['data'] = isset($childTree[$code]['data']) ? $data
																			   + $childTree[$code]['data'] : $data;
				$childTree[$code]['full'] = $word;
			}

			$childTree = &$childTree[$code]['child'];
		}

		$this->count++;
		unset($childTree);

		return $this;
	}

	/**
	 * 搜索文本所命中的关键词
	 *
	 * @param string  $searchText
	 * @param integer $limitNum 命中词数大于等于$limitNum时返回，-1表示找出所有
	 * @return array
	 */
	public function search($searchText, $limitNum = -1) {
		$searchText = trim($searchText);

		if (empty($searchText)) {
			return [];
		}

		$searchTextLetters = $this->convertStrToLetters($searchText);
		$tree = &$this->tree;
		$originIndex = 0;
		$hitWords = [];
		for ($i = 0; $i < count($searchTextLetters); $i++) {
			$letterCode = $this->getLetterCode($searchTextLetters[$i]);
			//若命中了一个索引 则继续向下寻找
			if (isset($tree[$letterCode])) {
				$node = $tree[$letterCode];
				if ($node['end']) {    //发现结尾 将原词以及自定义属性纳入返回结果集中
					$hitWords[md5($node['full'])] = [
						'word' => $node['full'],
						'data' => $node['data'],
					];

					if ($limitNum !== -1 && count($hitWords) >= $limitNum) {
						return $hitWords;
					}

					if (empty($node['child'])) {
						$i = $originIndex;        //若不存在子集，检索游标还原
						$tree = &$this->tree;     //还原检索集合
						$originIndex++;         //字码游标下移
					} else {
						$tree = &$tree[$letterCode]['child'];    //存在子集重定义检索tree
					}

					continue;
				} else {
					//没发现结尾继续向下检索
					$tree = &$tree[$letterCode]['child'];

					continue;
				}
			} else {
				//还原检索起点
				$i = $originIndex;
				//还原tree
				$tree = &$this->tree;
				//字码位移
				$originIndex++;

				continue;
			}
		}

		unset($tree, $searchTextLetters);

		return $hitWords;
	}

	private function convertStrToLetters($str) {
		return preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
	}

	private function getLetterCode($letter) {
		return md5($letter);
	}
}
