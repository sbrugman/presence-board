<?php
include 'functions.php';

if($_GET['action'] == 'dump')
{
	$data_start = get_data('data');

	$data = (json_decode(file_get_contents('php://input')));
	foreach($data as $value)
	{
		$data_start[] = array($value->mac,$value->ssid,$value->datetime);
	}
	
	set_data('data', $data_start);
}
if($_GET['action'] == 'map')
{
	$data_start = get_data('map_data');
	$data_start->$_POST['mac'] = $_POST['val'];

	set_data('map_data',$data_start);
}
