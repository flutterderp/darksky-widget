<?php
/**
 * Weather widget
 * @author flutterderp
 * @link https://github.com/flutterderp/darksky-widget
 * @version 1.2.0
 *
 * @param latitude
 * @param longitude
 * @param daily
 * @param current_weather
 * @param temperature_unit
 * @param windspeed_unit
 * @param precipitation_unit
 * @param timezone
 * @link https://open-meteo.com/
 * @link https://open-meteo.com/en/docs#api_form
 *
 * Params used are derived from Open-Meteo's URL Builder (see @links)
 */

define('CACHE_PATH', __DIR__ . '/');

$cache_file = 'the-weather.json';
$cache_time = 5 * 60; // default five minute cache
$api_key    = '';
$base_url   = 'https://api.open-meteo.com/v1/forecast';
$params     = array();

// Configure API parameters
$params['latitude']           = '35.3156';
$params['longitude']          = '-82.4587';
$location                     = $params['latitude'] . ',' . $params['longitude'];
$params['hourly']             = 'temperature_2m,relativehumidity_2m,apparent_temperature,precipitation,weathercode';
$params['daily']              = 'weathercode,temperature_2m_max,temperature_2m_min,sunrise,sunset,precipitation_sum';
$params['current_weather']    = 'true';
$params['temperature_unit']   = 'fahrenheit';
$params['windspeed_unit']     = 'mph';
$params['precipitation_unit'] = 'inch';
$params['timezone']           = 'America%2FNew_York';

// Initialise cURL session
$ch        = curl_init();
$headers   = array();
$headers[] = 'Content-length: 0';
$headers[] = 'Content-type: application/json';
$headers[] = 'User-Agent: ';
// $headers[] = 'Authorization: Bearer ' . $api_key;
$fetch_cache = file_get_contents(CACHE_PATH . $cache_file);
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
<?php elseif($enc_response->body->error) : ?>
	<p><?php echo $enc_response->body->reason; ?></p>
<?php elseif(is_object($enc_response)) : ?>
	<?php
	$headers      = $enc_response->headers;
	$weather_data = $enc_response->body;

	if(isset($weather_data->error))
	{
		echo $weather_data->error->code . ': ' . $weather_data->error->message;

		return false;
	}

	$current         = $weather_data->current;
	$location        = $weather_data->location;
	$current         = $weather_data->current_weather;
	$today_stats     = $weather_data->daily;
	$degree_unit     = $weather_data->daily_units->temperature_2m_max;
	$low             = $today_stats->temperature_2m_min[0];
	$high            = $today_stats->temperature_2m_max[0];
	$localtime       = new DateTime($weather_data->current_weather->time, new DateTimeZone($weather_data->timezone_abbreviation));
	$weather_code    = 'WMO_' . $current->weathercode;
	$current_weather = array_key_exists($weather_code, $wmo_codes) ? $wmo_codes[$weather_code] : $weather_code;
	$forecast_url    = '';

	?>
	<div class="weather-widget">
		<div class="row">
			<div class="small-12 columns">
				<p>Current weather as of <?php echo $localtime->format('M j@g:ia'); ?></p>

				<div class="clearfix">
					<ul class="no-bullet tempforecast">
						<li>
							<span class="temp">
								<?php echo $current->temperature . $degree_unit; ?>
								<!-- <small>(Feels like <?php echo $current->feelslike_f . $degree_unit; ?>)</small> -->
							</span>
						</li>
						<li><span class="forecast"><?php echo $current_weather; ?></span></li>
						<li>High: <?php echo $high; ?> / Low: <?php echo $low; ?></li>
					</ul>
					<?php /* <span class="wicon">
						<img src="<?php echo $current->condition->icon; ?>" alt="<?php echo htmlspecialchars($current->condition->text, ENT_COMPAT|ENT_HTML5, 'utf-8'); ?>">
					</span> */ ?>
				</div>
				<p class="clearfix"><small>Powered by <a href="https://open-meteo.com/" target="_blank" rel="noopener noreferrer" title="Open-source Weather API">Open-Meteo Weather API</a></small></p>
			</div>
		</div>
	</div>
<?php else : ?>
	<?php //echo 'Error: ' . $response; ?>
<?php endif; ?>
