<?php


function generate_stats($url) {
	$text = file_get_contents($url);
	preg_match_all('/[a-zA-Z]+/', $text, $matches);
	$words = array();
	foreach($matches[0] as $match) {
		$match = strtolower($match);
		if(!isset($words[$match])) {
			$words[$match] = 0;
		}
		$words[$match]++;
	}
	asort($words);
	return $words;
}

function rwords($r, $words) {
	$count = 0;
	foreach($words as $score) {
		if($score == $r) {
			$count++;
		}
	}
	return $count;
}

function zval($r, $words) {
	$w1 = array_values($words);
	$rindex = array_search($r, $w1);
	if(false === $rindex) {		
		$r--;
		$rindex = array_search($r, $w1)+1;
	} 
	$nr = rwords($r, $words);
	$rl = isset($w1[$rindex-1]) ? $w1[$rindex-1] : 0;
	$rh = isset($w1[$rindex+1]) ? $w1[$rindex+1] : (2*$r)-$rl;
	if($rl == $rh) {
		$rh++; // avoid /0
	}
	return 2 * ($nr / ( $rh - $rl ));
}

function newtypes($w1, $w2) {
	return count(array_diff(array_keys($w1), array_keys($w2)));
}

function merge_results($w1, $w2) {
	foreach($w2 as $word => $count) {
		if(!isset($w1[$word])) {
			$w1[$word] = $count;
		} else {
			$w1[$word] += $count;
		}
	}
	return $w1;
}

function rand_prob($w1, $w2) {
	echo "Probability of random word:\n";
	$word = array_rand($w1);
	$r = $w1[$word];
	// r* = (r+1)*nr+1/nr
	// prob = r*/N
	$rare_word_probability = (($r+1) * ( zval($r+1, $w1) / zval($r, $w1) )) / 	
						array_sum($w1);
	var_dump($word);
	var_dump($rare_word_probability);
	var_dump("Expected: " . ($rare_word_probability * array_sum($w2)));
	var_dump( isset($w2[$word]) ? "Count: " . $w2[$word] : 'not present in 2nd set' );
	echo "\n";
}

$words1 = generate_stats("http://blackroses.textfiles.com/fun/search.txt");
$words2 = generate_stats("http://blackroses.textfiles.com/fun/wdw4-92.txt");

rand_prob($words1, $words2);

// 1−n1/N
// num_types / 1−n1/N
//$expected_new_types = round(  count($words1) / ( 1 - ( rwords(1, $words1) / array_sum($words1) ) ) );
$expected_new_types = count($words2) * (rwords(1, $words1) / count($words1));
echo "Expected New Types:\n";
var_dump($expected_new_types);
echo "Actual New Types:\n";
var_dump(newtypes($words1, $words2));
echo "\n";

$words1 = merge_results($words1, $words2);
unset($words2);

$words3 = generate_stats("http://blackroses.textfiles.com/fun/w-fact-1.txt");

$expected_new_types = round( count($words1) / ( 1 - ( rwords(1, $words1) / array_sum($words1) ) ) );
echo "Expected New Types:\n";
var_dump($expected_new_types);
echo "Actual New Types:\n";
var_dump(newtypes($words1, $words3));
echo "\n";

rand_prob($words1, $words3);