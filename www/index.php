<?php
include 'functions.php';
$interval = 10 * 60;
$threshold = 10;

$data = load_data();
$ssid = load_ssid();
$oui = load_oui();
$map_data = get_data('map_data');

$total = array();
$entries = array();

$changed = false;

/*
foreach($map_data as $key => $data_mac)
{
	if(!is_object($map_data->$key))
	{
		$map_data->$key = new stdclass();
		$map_data->$key->name = $key;
		$map_data->$key->vendor = resolve_mac($key, $oui);
		$changed = true;
	}
}*/
foreach($data as $key => $data_mac)
{
	$processed = (process_data($data_mac, $interval, $key));
	$entries[$key] = $processed['points'];
	$total[$key] = $processed['total'];
	if(!isset($map_data->$key))
	{
		$map_data->$key = new stdclass();
		$map_data->$key->name = $key;
		$map_data->$key->vendor = resolve_mac($key, $oui);
		$changed = true;
	}
}

if($changed)
{
	set_data('map_data',$map_data);
}
?>
<!DOCTYPE html>
<html>
  <head>
    <script type="text/javascript" src="bower_components/jquery/dist/jquery.min.js"></script>
    <link href="bower_components/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>

    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
    google.load('visualization', '1', {packages: ['timeline']});
    google.setOnLoadCallback(drawChart);

    function drawChart() {
      var data = google.visualization.arrayToDataTable([
        ['Activity', 'Start Time', 'End Time'],
	<?php
	foreach($entries as $key => $points)
	{
	    if($total[$key] >= $threshold)
	    {
	    	foreach($points as $point)
	    	{
	            echo "['".($map_data->$key->name)."',new Date(".date('Y,n,j,G,i',$point['start'])."), new Date(".date('Y,n,j,G,i',$point['end']).")],\n";
	    	}
	    }
	} 
	?>
      ]);

      var options = {
        height: 450,
      };

      var chart = new google.visualization.Timeline(document.getElementById('chart_div'));

      chart.draw(data, options);
    }
    </script>
  </head>
  <body>
	<div class="container" style="padding-top:20px;">
	  <div class="row">
 	    <div id="chart_div" style="width: 100%; height: 500px;"></div>
	  </div>
	</div>

	<div class="container">
	  <div class="row">
	    <input type="button" value="Settings" class="btn btn-default" id="settings">
   	  </div>
	</div>
	<script>
	jQuery(function(){
		jQuery('#settings').click(function(){
			jQuery('.container-settings').toggle();
		});
	});
	jQuery(function(){
		jQuery('.form-control').change(function(){
			jQuery.post('api.php?action=map',{mac:jQuery(this).attr('name'),val:jQuery(this).val()});
		});
	});</script>	
	<div class="container container-settings" style="display:none;">
	  <div class="row">
	    <h2>Apparaten</h2>
	      <table class="table">
		<?php
		foreach($map_data as $device => $object)
		{
		    echo '<tr>';
			echo '<td>'.$device.'</td>';
			echo '<td><input class="form-control" name="'.$device.'" type="text" value="'.$object->name.'" /></td>';
		    	echo '<td>'.(isset($ssid[$device]) ? implode(", ",$ssid[$device]) : '').'</td>';
		    	echo '<td>'.$object->vendor.'</td>';
		    echo '</tr>'."\n";
		}
		?>
	     </table>
           </div>
    	</div>
  </body>
</html>
