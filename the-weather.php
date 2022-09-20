<?php
/**
 * Weather widget
 * @author flutterderp
 * @link https://github.com/flutterderp/darksky-widget
 * @version 1.0.0
 *
 * @param api_key
 * @param latitude
 * @param longitude
 * @link https://www.weatherapi.com/
 *
 * forecast.json may also be used to get current plus forecast data up to 10 days out using
 * “days” as an additional query parameter
 */

define('CACHE_PATH', __DIR__ . '/');
$cache_file  = 'the-weather.json';
$cache_time  = 5 * 60; // default five minute cache
$base_url    = 'https://api.weatherapi.com/v1/forecast.json';
$api_key     = '';
/* $latitude    = '35.3156';
$longitude   = '-82.4587';
$location    = $latitude . ',' . $longitude; */
$location    = 'Virginia Beach, VA';
$ch          = curl_init();
$headers     = array();
$headers[]   = 'Content-length: 0';
$headers[]   = 'Content-type: application/json';
$headers[]   = 'User-Agent: ';
// $headers[]   = 'Authorization: Bearer ' . $api_key;
$fetch_cache = file_get_contents(CACHE_PATH . $cache_file);

if(($fetch_cache !== false) && ((filemtime($cache_file) + $cache_time) > time()))
{
	// Use our cached results
	$result = $fetch_cache;
}
else
{
	// Fetch new results
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_URL, $base_url . '?key=' . $api_key . '&q=' . urlencode($location));

	$response         = curl_exec($ch);
	$response_code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	// remove the empty line between headers and body
	$response         = preg_replace("/[\r\n\r\n]+/", "\r\n", $response);
	$response         = explode(PHP_EOL, trim($response));
	$response_headers = array();
	$response_body    = array_pop($response);

	foreach($response as $key => $data)
	{
		$dataset    = explode(':', $data, 2);
		$dataset[0] = trim($dataset[0]);

		if(isset($dataset[1]))
		{
			$response_headers[$dataset[0]] = trim($dataset[1]);
		}
		else
		{
			$response_headers[$dataset[0]] = null;
		}
	}

	// Write to a cache file
	$result = array('headers' => $response_headers, 'body' => json_decode($response_body));
	$result = json_encode($result);

	file_put_contents(CACHE_PATH . $cache_file, $result);
	touch($cache_file, time());
}

$enc_response = json_decode($result);

if(json_last_error() !== JSON_ERROR_NONE) : ?>

<?php elseif(is_object($enc_response)) : ?>
	<?php
	$headers      = $enc_response->headers;
	$weather_data = $enc_response->body;

	if(isset($weather_data->error))
	{
		echo $weather_data->error->code . ': ' . $weather_data->error->message;

		return false;
	}

	$current      = $weather_data->current;
	$location     = $weather_data->location;
	$today_stats  = $weather_data->forecast->forecastday[0];
	$low          = $today_stats->day->mintemp_f;
	$high         = $today_stats->day->maxtemp_f;
	$localtime    = new DateTime($location->localtime, new DateTimeZone($location->tz_id));
	$forecast_url = '';

	?>
	<div class="weather-widget">
		<div class="row">
			<div class="small-12 columns">
				<h3><?php echo htmlspecialchars($location->name, ENT_COMPAT|ENT_HTML5, 'utf-8'); ?></h3>
				<?php echo $localtime->format('M j'); ?>

				<div class="clearfix">
					<ul class="no-bullet tempforecast">
						<li>
							<span class="temp">
								<?php echo $current->temp_f; ?>°
								<small>(Feels like <?php echo $current->feelslike_f; ?>°)</small>
							</span>
						</li>
						<li><span class="forecast"><?php echo $current->condition->text; ?></span></li>
						<li>High: <?php echo $high; ?> / Low: <?php echo $low; ?></li>
					</ul>
					<span class="wicon">
						<img src="<?php echo $current->condition->icon; ?>" alt="<?php echo htmlspecialchars($current->condition->text, ENT_COMPAT|ENT_HTML5, 'utf-8'); ?>">
					</span>
				</div>
				<p class="clearfix"><small>Powered by <a href="https://www.weatherapi.com/" target="_blank" rel="noopener noreferrer" title="Free Weather API">WeatherAPI</a></small></p>
			</div>
		</div>
	</div>
<?php else : ?>
	<?php //echo 'Error: ' . $response; ?>
<?php endif; ?>
