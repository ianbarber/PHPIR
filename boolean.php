<?php

class Index {
        private $index = array();

        public function storeToken($documentId, $token) {
                if( !isset($this->index[$token]) ) {
                        $this->index[$token] = array();
                }
                $this->index[$token][] = $documentId;
        }
        
        public function getPostings($token) {
            return isset($this->index[$token]) ? $this->index[$token] : array();
        } 
}

class BooleanQuery {
        private $index = 0;
        private $tokens = array();
        private $count;
        private $tree;

        public function __construct($query) {
                preg_match_all('/[a-zA-Z]+|[\(\)]/', strtolower($query), $matches);
                $this->count = count($matches[0]);
                $this->tokens = $matches[0];
                $this->tree = $this->buildQueryTree();
        }

        private function buildQueryTree() {
                while($this->index < $this->count) {
                        $token = $this->tokens[$this->index];
                        $this->index++;

                        if('(' == $token) {
                                $tree = $this->buildQueryTree();
                        } else if(')' == $token) {
                                return $tree;
                        } else if(in_array($token, array('and', 'or', 'not'))) {
                                $tree = array('action' => $token, 'left' => $tree,
                                        'right' => $this->buildQueryTree());
                        } else {
                                $tree = $token;
                        }
                }
                return $tree;
        }
        
        public function search($index) {
            return $this->processQuery($this->tree, $index);
        }
        
        private function union($postings1, $postings2) {
                return array_unique(array_merge($postings1, $postings2));
        }
        
        private function intersect($postings1, $postings2) {
                return array_unique(array_intersect($postings1, $postings2));
        }
        
        private function complement($postings1, $postings2) {
                return array_unique(array_diff($postings1, $postings2));
        }
        
        
        private function processQuery($queryTree, $index) {
            if(is_array($queryTree)) {
                $left = $this->processQuery($queryTree['left'], $index);
                $right = $this->processQuery($queryTree['right'], $index);
                switch($queryTree['action']) {
                    case 'and':
                            return $this->intersect($left, $right);
                    case 'or':
                            return $this->union($left, $right);
                    case 'not':
                            return $this->complement($left, $right);
                }
            } else {
                return $index->getPostings($queryTree);
            }
        }
}



$index = new Index();
$documents = array(
    "http://phpir.com/simple-searching-boolean-retrieval", 
    "http://phpir.com/presentation-tips-from-benelux",
    "http://phpir.com/linear-regression-in-php-part-2",
);

foreach($documents as $documentID => $document) {
    $contents = strtolower(strip_tags(file_get_contents($document)));
    preg_match_all('/[a-zA-Z]+/', $contents, $matches);
    $matches = array_unique($matches[0]);
    foreach($matches as $match) {
        $index->storeToken($documentID, $match);
    }
    unset($contents);
}
        
$query = 'PHP AND (Information OR Retrieval) NOT Spoons';
$q = new BooleanQuery($query);
var_dump($q->search($index));