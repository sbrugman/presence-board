<?php
include 'functions.php';
$interval = 5 * 60;
$threshold = 2;

$data = load_data();

$entries = array();
foreach($data as $key => $data_mac)
{
	$entries[$key] = (process_data($data_mac, $interval, $key));
}

$map_data = get_data('map_data');
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
	    if(count($points) >= $threshold)
	    {
	    	foreach($points as $point)
	    	{
	            echo "['".map($key, $map_data)."',new Date(".date('Y,n,j,G,i',$point['start'])."), new Date(".date('Y,n,j,G,i',$point['end']).")],\n";
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
		foreach($map_data as $device => $name)
		{
		    echo '<tr><td><a href="http://www.coffer.com/mac_find/?string='.urlencode($device).'">'.$device.'</a></td><td><input class="form-control" name="'.$device.'" type="text" value="'.$name.'" /></td></tr>';
		}
		foreach($unknown as $device)
		{
		    echo '<tr><td><a href="http://www.coffer.com/mac_find/?string='.urlencode($device).'">'.$device.'</a></td><td><input class="form-control" name="'.$device.'" type="text" /></td></tr>';
		}
		?>
	     </table>
           </div>
    	</div>
  </body>
</html>
