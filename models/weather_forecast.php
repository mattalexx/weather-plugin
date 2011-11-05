<?php

class WeatherForecast extends AppModel
{
	public $useTable = false;
	public $client = null;

	public function getWeather($airport)
	{
		$cacheKey = 'weather';
		$data = Cache::read($cacheKey, 'weather');
		if (!empty($data))
			return $data;

		try {
			// http://www.weather.gov/xml/current_obs/ // Search
			$url = 'http://www.weather.gov/xml/current_obs/'.$airport.'.xml';
			$response = file_get_contents($url);
			$responseData = $this->_xmlToArray($response);

			if (empty($responseData['current_observation']))
				throw new Exception;
			$responseData2 = $responseData['current_observation'];

			$data = array();
			$data['provider'] = 'National Oceanic and Atmospheric Administration\'s National Weather Service (weather.gov)';
			$data['url'] = $url;
			$data['temperature'] = $responseData2['temp_f'];
			$data['summary'] = $responseData2['weather'];
			$data['icon'] = $responseData2['icon_url_base'].$responseData2['icon_url_name'];
			$data['local_timestamp'] = time();
			$data['api_timestamp'] = strtotime($responseData2['observation_time_rfc822']);
			$data['response'] = $responseData;

			Cache::write($cacheKey, $data, 'weather');
		} catch(Exception $e) {
			return null;
		}

		return $data;
	}
	
	public function getClient()
	{
		if (!$this->client)
			$this->client = new WeatherSoapClient;
		return $this->client;
	}

	public function _xmlToArray($xml)
	{
		App::import('Core', 'Xml');
		$Xml = new Xml($xml);
		$array = $Xml->toArray(false); // Send false to get separate elements
		$Xml->__destruct();
		$Xml = null;
		unset($Xml);
		return $array;
	}
}

class WeatherSoapClient extends SoapClient
{
	public function __construct($wsdl = null, $options = array())
	{
		$wsdl = 'http://www.weather.gov/forecasts/xml/DWMLgen/wsdl/ndfdXML.wsdl';
		$options = array('soap_version' => SOAP_1_2);
		return parent::__construct($wsdl, $options);
	}
 
	public function __doRequest($request, $location, $action, $version)
	{
		// Call via Curl and use the timeout
		$curl = curl_init($location);
		curl_setopt($curl, CURLOPT_VERBOSE, FALSE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
		curl_setopt($curl, CURLOPT_HEADER, FALSE);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		$response = curl_exec($curl);
		if (curl_errno($curl)) {
			throw new Exception(curl_error($curl));
		}
		curl_close($curl);
			
		return $response;
	}
}
