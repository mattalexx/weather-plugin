# Weather

A plugin for CakePHP that provides access to National Oceanic and Atmospheric Administration\'s National Weather Service.

## Usage

1. [Find your airport code](http://www.weather.gov/xml/current_obs/).
1. In controller:

		$airportCode = 'KSKX';
		$this->loadModel('Weather.WeatherForecast');
		$weather = $this->WeatherForecast->daysTemperature($airportCode);
