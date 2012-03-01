<?php

class MVGradient {
	
	protected $data;
	protected $learning_rate = 0.1;

	/**
	 * Set the data for the function. 
	 * @param array - 0 => (x1, x2, x3, x4), 1 => y
	 */
	public function set_data($data) {
		$this->data = $this->scale_data($data);
	}
	
	/**
	 * Set the rate at which the algorithm updates.
	 * Normal values are 0.1 - 0.001
	 *
	 * @param float $rate 
	 * @return void
	 */
	public function set_learning_rate($rate) {
		$this->learning_rate = $rate;
	}

	/**
	 * Normalise variance and scale data to:
	 * xi - avg(xi) / range(max-min)
	 * so we get in a -0.5 - 0.5 range with an
	 * avg of 0
	 * - this is a bit of clunky method!
	 */
	protected function scale_data($data) {
		$minmax = array();
		$rows = count($data);
	
		foreach($data as $key => $row) {
			foreach($row[0] as $id => $val) {
				/* Initialise Arrays */
				if(!isset($minmax[$id])) {
					$minmax[$id] = array();
					$minmax[$id]['min'] = false;
					$minmax[$id]['max'] = false;
					$minmax[$id]['total'] = 0;
				}
			
				/* Get stats */
				if( $minmax[$id]['min'] == false ||
					  $minmax[$id]['min'] > $val) {
					$minmax[$id]['min'] = $val;
				}
				if( $minmax[$id]['max'] == false ||
					  $minmax[$id]['max'] < $val) {
					$minmax[$id]['max'] = $val;
				}
			
				$minmax[$id]['total'] += $val;
			}
		}
	
		/* Compute average and variance */
		foreach($minmax as $id => $row) {
			$minmax[$id]['var'] = $row['max'] - $row['min'];
			$minmax[$id]['avg'] = $row['total'] / $rows;
		
		}

		foreach($data as $key => $row) {
			foreach($row[0] as $id => $val) {
				$data[$key][0][$id] = ( $val - $minmax[$id]['avg'] )
									/ $minmax[$id]['var'];
			}
		}
	
		return $data;
	}

	/**
	 * Update the parameters, including using a dummy row value
	 * of 1 for the first parameter.
	 *
	 * @param array $params 
	 * @return array
	 */
	protected function learn($params) {
		$data_rate = 1/count($this->data);
	
		foreach($params as $id => $p) {
			foreach($this->data as $row) {
				$score = $this->mv_hypothesis($row[0], $params) - $row[1];
			
				// Update parameters
				$params[$id] -= $this->learning_rate * 
					($data_rate *
						( $score * ($id == 0 ? 1 : $row[0][$id-1]) )
					);
			}
		}

		return $params;
	}
	
	/**
	 * Generate a score based on the data and passed parameters
	 *
	 * @param array $params 
	 * @return int
	 */
	protected function mv_hypothesis($rowdata, $params) {
		$score = $params[0];
		foreach($rowdata as $id => $value)  {
			$score += $value * $params[$id+1];
		}
		return $score;
	}

	/**
	 * Return the sum of squared error score
	 *
	 * @param array $params 
	 * @return int
	 */
	public function score($params) {
		$score = 0;
		foreach($this->data as $row) {
			$score += pow($this->mv_hypothesis($row[0], $params) - $row[1], 2);
		}
		return $score;
	}

	/**
	 * Update parameters
	 *
	 * @param string $data 
	 * @param string $parameters 
	 * @return array parameters
	 */
	function mv_gradient($parameters) {
		$score = $this->score($parameters);
	
		// Create a new hypothesis to test our score
		$parameters = $this->learn($parameters);

		if($score < $this->score($parameters)) {
			return false;
		}
	
		return $parameters;
	}
	
	/**
	 * Find the parameters that best fit the data
	 *
	 * @param int $iterations - max iterations to run
	 * @param array $defaults - optional starting params
	 * @return array - best fit parameters
	 */
	public function find_params($iterations = 5000, $defaults = null) {
		if(!$defaults) {
			$defaults = array_fill(0, count($this->data[0][0]) + 1, 0);
		}
		
		$parameters = $defaults;
		$iters = 0;
		do {
			$last_parameters = $parameters;
			$parameters = $this->mv_gradient($parameters);
		} while($parameters != false && $iters++ < $iterations);
		
		return $parameters ? $parameters : $last_parameters;
	}

}

/* Nice regular data for testing */
$data = array(
	array(array(2, 4000, 0.5), 2+2+(2*4)+(3*5)),
	array(array(2, 4000, 0.4), 2+2+(2*4)+(3*4)),
	array(array(2, 4000, 0.6), 2+2+(2*4)+(3*6)),
	array(array(1, 5000, 0.5), 2+1+(2*5)+(3*5)),
	array(array(2, 5000, 0.1), 2+2+(2*5)+(3*1)),
);

class PolyMV extends MVGradient {
	
	/**
	 * Skip scaling just for the example
	 */
	protected function scale_data($data) {
		return $data;
	}
	
	/**
	 * Generate a score based on the data and passed parameters
	 *
	 * @param array $params 
	 * @return int
	 */
	protected function mv_hypothesis($rowdata, $params) {
		$score = $params[0];
		foreach($rowdata as $id => $value)  {
			$score +=  pow($value, $id+2) * $params[$id+1];
		}
		return $score;
	}
	
	/**
	 * Update the parameters, including using a dummy row value
	 * of 1 for the first parameter.
	 *
	 * @param array $params 
	 * @return array
	 */
	protected function learn($params) {
		$data_rate = 1/count($this->data);
	
		foreach($params as $id => $p) {
			foreach($this->data as $row) {
				$score = $this->mv_hypothesis($row[0], $params) - $row[1];
			
				// Update parameters
				// We have to multiply by an appropriate power as part of the
				// partial derivative
				$params[$id] -= $this->learning_rate * 
					($data_rate *
						( $score * ($id == 0 ? 1 : pow($row[0][$id-1], $id+1)) )
					);
			}
		}

		return $params;
	}
}
/*


$iterations = array(10, 100, 500, 1000, 2000, 5000, 10000);
$mvg = new MVGradient();
$mvg->set_data($data);
foreach(array(0.1, 0.01, 0.001, 0.001) as $rate) {
	$mvg->set_learning_rate($rate);
	foreach($iterations as $i) {
		$params = $mvg->find_params($i);
		echo $mvg->score($params), "\n";
	}
	echo "\n";
}
die();


// We have a polynomial example here

$data = array(
	array(array(2, 2), 1+(3*pow(2, 2))+(2*pow(2, 3))),
	array(array(3, 3), 1+(3*pow(3, 2))+(2*pow(3, 3))),
	array(array(4, 4), 1+(3*pow(4, 2))+(2*pow(4, 3))),
	array(array(5, 5), 1+(3*pow(5, 2))+(2*pow(5, 3))),
);

$iterations = array(10000);
$mvg = new PolyMV();
$mvg->set_data($data);
$mvg->set_learning_rate(0.001);
foreach($iterations as $i) {
	$params = $mvg->find_params($i);
	echo $mvg->score($params), "\n";
	var_dump($params);
}
echo "\n";
*/