<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of nested_implode_R2
 *
 * @author Multiple authors found at http://theserverpages.com/php/manual/en/function.implode.php
 * I've made a few minor tweaks, there were some typos, but otherwise they do what I want.
 * blame the lack of documentation on them :P.
 */
class nested_implode_R2
{
	public function implode_assoc_r($inner_glue = "=", $outer_glue = "\n", $array = null, $keepOuterKey = false)
	{
		$output = array();

		foreach( $array as $key => $item )
			if ( is_array ($item) )
			{
				if ( $keepOuterKey )
				$output[] = $key;

				// This is value is an array, go and do it again!
				$output[] = $this->implode_assoc_r ($inner_glue, $outer_glue, $item, $keepOuterKey);
			}
			else
				$output[] = $key . $inner_glue . $item;

			return implode($outer_glue, $output);
	}
	public function implode_assoc_r2($inner_glue = "=", $outer_glue = "\n", $recusion_level = 0, $array = null)
	{
		$output = array();

		foreach( $array as $key => $item )
		if ( is_array ($item) )
		{
			// This is value is an array, go and do it again!
			$level = $recusion_level + 1;
			$output[] = $key . $inner_glue . $recusion_level . $inner_glue . $this->implode_assoc_r2 ($inner_glue, $outer_glue, $level, $item, false);
		}
		else
		$output[] = $key . $inner_glue . $recusion_level . $inner_glue . $item;

		return implode($outer_glue . $recusion_level . $outer_glue, $output);
	}

	public function explode_assoc_r2($inner_glue = "=", $outer_glue = "\n", $recusion_level = 0, $string = null)
	{
		$output=array();
		$array=explode($outer_glue.$recusion_level.$outer_glue, $string);
		foreach ($array as $value)
		{
			$row=explode($inner_glue.$recusion_level.$inner_glue,$value);
			$output[$row[0]]=$row[1];
			$level = $recusion_level + 1;
			if(strpos($output[$row[0]],$inner_glue.$level.$inner_glue))
			$output[$row[0]] = $this->explode_assoc_r2($inner_glue,$outer_glue,$level,$output[$row[0]]);
		}


		return $output;
	}
	public function explode_with_keys($seperator, $string)
	{
		$output=array();
		$array=explode($seperator, $string);
		foreach ($array as $value)
		{
			$row=explode("=",$value);
			$output[$row[0]]=$row[1];
		}
		return $output;
	}
}
?>
