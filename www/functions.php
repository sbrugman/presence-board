<?php
function get_data($type)
{
	$filename = 'data/'.$type.'.json';
	if(file_exists($filename))
	{
		return json_decode(file_get_contents($filename));
	}
	else
	{
		return array();
	}
}

function set_data($type, $data)
{
	$data = json_encode($data);
	$filename = 'data/'.$type.'.json';
	file_put_contents($filename, $data);
}

function process_into_interval($data, $seconds_interval)
{
	$active = array();
	$seconds_day = 24*60*60;
	$iterations_total = ceil($seconds_day / $seconds_interval);
	$start = mktime(0,0,0);
	$between_start = $start;
	for($i = 0; $i < $iterations_total; $i++)
	{
		$between_start = $between_start + $seconds_interval;
		$name =  $between_start;
		$active[$name] = 0;

		$between_end = $between_start + $seconds_interval;
		foreach($data as $point)
		{
			if($point >= $between_start && $point < $between_end)
			{
				$active[$name]++;	
			}
		}
	}

	return $active;
}

function process_into_points($active)
{
	$points = array();
	$prevValue = -1;	
	$start = -1;
	foreach($active as $name => $value)
	{
		if($prevValue == -1 || (($value >= 1 && $prevValue == 0) || ($value == 0 && $prevValue == 1)))
		{
			if($start != -1 && $prevValue == 1)
			{
				$points[] = array('start' => $start, 'end' => $name, 'value' => $prevValue);
			}
			$start = $name;
		}
		$prevValue = ($value >= 1) ? 1 : 0;
	}

	if($prevValue == 1)
	{
		$points[] = array('start' => $start, 'end' => $name, 'value' => $prevValue);
	}
	return $points;
}

function process_data($data, $seconds_interval = 300)
{
	$active = process_into_interval($data, $seconds_interval);

	$points = process_into_points($active);
	return $points;
}

function load_data()
{
	$raw_data = get_data('data');
	$data = array();
	foreach($raw_data as $value)
	{
		if($value[0] != null)
		{
			$data[$value[0]][] = strtotime($value[2]);
		}
	}

	return $data;
}

$unknown = array();
function map($key, $map_data)
{
	if(isset($map_data->$key))
	{
		return $map_data->$key;
	}
	else
	{
		global $unknown;
		if(!in_array($key, $unknown))
		{
			$unknown[] = $key;
		}	
		
		return 'Onbekend apparaat ('.(array_search($key, $unknown)+1).')';
	}
}
