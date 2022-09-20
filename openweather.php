<?php
/**
 * Weather widget
 * @author flutterderp
 * @link https://github.com/flutterderp/darksky-widget
 * @version 1.2.0
 *
 * @param lat
 * @param lon
 * @param units
 * @param exclude
 * @link https://openweathermap.org/current
 */

define('CACHE_PATH', __DIR__ . '/');

$cache_file = 'the-weather.json';
$cache_time = 15 * 60; // default fifteen minute cache
$api_key    = '';
$base_url   = 'https://api.openweathermap.org/data/2.5/weather';
$params     = array();

// Configure API parameters
$params['appid']   = '';
$params['lat']     = '36.8529';
$params['lon']     = '-75.978';
$params['units']   = 'imperial';
$params['exclude'] = '';
$location          = $params['lat'] . ',' . $params['lon'];

// Initialise cURL session
$ch        = curl_init();
$headers   = array();
$headers[] = 'Content-length: 0';
$headers[] = 'Content-type: application/json';
$headers[] = 'User-Agent: ';
// $headers[] = 'Authorization: Bearer ' . $api_key;
$fetch_cache = file_exists(CACHE_PATH . $cache_file) ? file_get_contents(CACHE_PATH . $cache_file) : false;
$wmo_codes   = parse_ini_file(__DIR__ . '/wmo-codes.ini');

if(($fetch_cache !== false) && ((filemtime($cache_file) + $cache_time) > time()))
{
	// Use our cached results
	$result = $fetch_cache;
}
else
{
	$query_string = http_build_query($params);

	// Fetch new results
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	curl_setopt($ch, CURLOPT_ENCODING, '');
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_TIMEOUT, 0);
	curl_setopt($ch, CURLOPT_URL, $base_url . '?' . urldecode($query_string));

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
?>
<?php if(json_last_error() !== JSON_ERROR_NONE) : ?>
	<?php /* TODO: echo the JSON error 🙃 */ ?>
<?php elseif($enc_response->body->cod !== 200) : ?>
	<p><?php echo $enc_response->body->cod . ': ' . $enc_response->body->message; ?></p>
<?php elseif(is_object($enc_response)) : ?>
	<?php
	$headers      = $enc_response->headers;
	$weather_data = $enc_response->body;

	if(isset($weather_data->error))
	{
		echo $weather_data->error->code . ': ' . $weather_data->error->message;

		return false;
	}

	$low        = $weather_data->main->temp_min;
	$high       = $weather_data->main->temp_max;
	$local_time = DateTime::createFromFormat('U', $weather_data->dt, new DateTimeZone('UTC'));
	$offset     = ($weather_data->timezone / 3600);
	$local_zone = new DateTimeZone($offset);
	$local_time->setTimeZone($local_zone);

	$current_weather = $weather_data->weather[0]->main;
	$current_icon    = $weather_data->weather[0]->icon;
	?>
	<div class="weather-widget">
		<div class="row">
			<div class="small-12 columns">
				<p>Current weather in <?php echo $weather_data->name; ?> as of <?php echo $local_time->format('M j@g:ia'); ?></p>

				<div class="clearfix">
					<ul class="no-bullet tempforecast">
						<li>
							<span class="temp">
								<?php echo $weather_data->main->temp; ?>°
								<small>(Feels like <?php echo $weather_data->main->feels_like; ?>)</small>
							</span>
						</li>
						<li><span class="forecast"><?php echo $current_weather; ?></span></li>
						<li>High: <?php echo $high; ?> / Low: <?php echo $low; ?></li>
					</ul>
					<span class="wicon">
						<img src="http://openweathermap.org/img/wn/<?php echo $current_icon; ?>@2x.png" alt="<?php echo htmlspecialchars($weather_data->weather->description, ENT_COMPAT|ENT_HTML5, 'utf-8'); ?>">
					</span>
				</div>
				<p><small>Weather data provided by <a href="https://openweathermap.org/" target="_blank" rel="noopener noreferrer" title="OpenWeather">OpenWeather</a></small></p>
			</div>
		</div>
	</div>
<?php else : ?>
	<?php //echo 'Error: ' . $response; ?>
<?php endif; ?>
