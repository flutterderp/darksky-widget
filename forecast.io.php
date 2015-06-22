<?php
/**
 * Weather widget
 * 
 * @param api_key
 * @param latitude
 * @param longitude
 * @link https://developer.forecast.io/
 */

define(CACHE_PATH, __DIR__ . '/');
$cache_file	= 'forecast.io.json';
$cache_time	= 5 * 60; // default five minute cache
$base_url		= 'https://api.forecast.io/forecast/';
$api_key		= '';
$latitude		= '35.3156';
$longitude	= '-82.4587';
$location		= $latitude . ',' . $longitude;
$ch					= curl_init();
$headers		= array();
$headers[]	= 'Content-length: 0';
$headers[]	= 'Content-type: application/json';
//$headers[]	= 'Authorization: Bearer ' . $api_key;

$fetch_cache = @file_get_contents(CACHE_PATH . $cache_file);
if(($fetch_cache !== false) && ((filemtime($cache_file) + $cache_time) > time()))
{
	// Use our cached results
	//echo date('H:i:s', (filemtime($cache_file) + $cache_time));
	//echo '<br>'.date('H:i:s', time()).'<br>';
	$response = $fetch_cache;
}
else
{
	// Fetch new results
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_URL, $base_url . $api_key . '/' . $location);
	$response = curl_exec($ch);
	
	// Write to a cache file
	@file_put_contents(CACHE_PATH . $cache_file, $response);
	touch($cache_file, time());
}

$enc_response = json_decode($response, true);

if(is_array($enc_response) && json_last_error() == JSON_ERROR_NONE)
{
	$current			= $enc_response['currently'];
	$c_time				= new DateTime($current->time, new DateTimeZone($enc_response['timezone']));
	$forecast_url	= 'http://forecast.io/#/f/' . $location;
	//echo '<pre>'; print_r($enc_response); echo '</pre>';
	?>

	<div class="weather-widget">
		<div class="row">
			<div class="small-12 columns">
				<h3>Hendersonville Weather</h3>
				<?php echo $c_time->format('M j'); ?>
				
				<div class="clearfix">
					<ul class="no-bullet tempforecast">
						<li>
							<span class="temp">
								<?php echo $current['temperature']; ?>°
								<small>(Feels like <?php echo $current['apparentTemperature']; ?>°)</small>
							</span>
						</li>
						<li><span class="forecast"><?php echo $current['summary']; ?></span></li>
					</ul>
					<span class="wicon">
						images/weather-icons/<?php echo $current['icon']; ?>.png
						<!--<img src="/images/weather-icons/<?php echo $current['icon']; ?>.png" alt="Current weather in Hendersonville, NC" />-->
					</span>
				</div>
				<p class="clearfix"><a href="<?php echo $forecast_url; ?>" target="_blank"><small>Full Forecast via Forecast.io</small></a></p>
			</div>
		</div>
	</div>
	<?php
}
else
{
	//echo 'Error: ' . $response;
}
