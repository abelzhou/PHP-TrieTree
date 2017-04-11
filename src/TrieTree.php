<?php
/**
 *
 * Author: abel
 * Date: 17/4/10
 * Time: 17:26
 */

namespace AbelZhou\Tree;


class TrieTree{
    protected $nodeTree = [];

    public function __construct(){

    }


    public function append($str){
        $str = trim($str);
        $this->nodeTree = $this->_appendString($this->nodeTree,$str);
        return $this;
    }


    public function getTree(){
        return $this->nodeTree;
    }


    public function search($search){
        $search = trim($search);
        if(empty($search)){
            return false;
        }

        $keywords = false;
        $str = $search;
        while(true){
            if(empty($str)){
                break;
            }

            $word = mb_substr($str,0,1);
            $str = mb_substr($str,1);
            $searchRes = $this->_hit($word, $str, $this->nodeTree);
            if($searchRes !== false){
                $keywords = $searchRes;
                break;
            }
        }

        return $keywords;

    }


    private function _hit($firstWord,$str,$nodeTree){
        $firstHash = hash('md5', $firstWord);
        if(!isset($nodeTree['list'][$firstHash])){
            return false;
        }
        if(!$nodeTree['list'][$firstHash]['end']){
            $word_first = mb_substr($str,0,1);
            $str = mb_substr($str,1);

            $res = $this->_hit($word_first, $str, $nodeTree['list'][$firstHash]);
            if($res === false){
                return false;
            }

            $firstWord .= $res;
        }

        return $firstWord;

    }


    private function _appendString($nodeTree,$str,$preWords = ''){
        $firstWord = mb_substr($str, 0, 1);
        $words = mb_substr($str, 1);
        $firstHash = hash('md5',$firstWord);
        $preWords = $preWords.$firstWord;

        if(!isset($nodeTree['list'][$firstHash])){
            $nodeTree['list'][$firstHash] = array('str'=>$firstWord,'end'=>false);
        }
        if(empty($words)){
            $nodeTree['list'][$firstHash]['end'] = true;
            $nodeTree['list'][$firstHash]['value'] = $preWords;
            return $nodeTree;
        }

        $nodeTree['list'][$firstHash] = $this->_appendString($nodeTree['list'][$firstHash], $words, $preWords);
        return $nodeTree;
    }
}