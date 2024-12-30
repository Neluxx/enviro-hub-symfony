<?php

namespace App\Service;

use App\Entity\OpenWeatherData;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use DateTime;
use App\Repository\OpenWeatherDataRepository;

class OpenWeatherDataService
{
    private const OPEN_WEATHER_API_URL = 'https://api.openweathermap.org/data/2.5/weather';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly OpenWeatherDataRepository $repository,
        private readonly string $openWeatherApiKey
    ) {}

    /**
     * Fetches weather data from the OpenWeather API for a given city.
     *
     * @param string $cityName The name of the city for which to fetch weather data.
     *
     * @return OpenWeatherData The weather data object containing temperature, humidity, wind speed, description, and other details.
     *
     * @throws RuntimeException If the API request fails or returns an unsuccessful response.
     * @throws TransportExceptionInterface If there is an issue with the HTTP transport layer during the API request.
     * @throws DecodingExceptionInterface If there is an error decoding the JSON response from the API.
     * @throws Exception
     */
    public function fetchWeatherData(string $cityName): OpenWeatherData
    {
        $response = $this->httpClient->request('GET', self::OPEN_WEATHER_API_URL, [
            'query' => [
                'q' => $cityName,
                'appid' => $this->openWeatherApiKey,
                'units' => 'metric'
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Failed to fetch weather data from OpenWeather API.');
        }

        $data = $response->toArray();

        return $this->createOpenWeatherDataFromArray($data);
    }

    /**
     * Saves the provided weather data into the repository.
     *
     * @param OpenWeatherData $data The weather data object to be persisted.
     *
     * @return void
     *
     * @throws Exception If there is an issue while saving the data into the repository.
     */
    public function saveWeatherData(OpenWeatherData $data): void
    {
        $this->repository->save($data);
    }

    /**
     * Creates an OpenWeatherData object from an array of weather data.
     *
     * @param array $data The associative array containing weather data retrieved from the API.
     *
     * @return OpenWeatherData The populated OpenWeatherData object with city name, temperature, humidity, wind speed, description, and timestamp.
     *
     * @throws InvalidArgumentException If required keys are missing or data is invalid.
     * @throws Exception If there is an issue creating the DateTime object for the `createdAt` property.
     */
    public function createOpenWeatherDataFromArray(array $data): OpenWeatherData
    {
        $weatherData = new OpenWeatherData();

        // Basic information
        $weatherData->setCityName($data['name'] ?? null);
        $weatherData->setCountry($data['sys']['country'] ?? null);

        // Main weather data
        $weatherData->setTemperature($data['main']['temp'] ?? null);
        $weatherData->setFeelsLike($data['main']['feels_like'] ?? null);
        $weatherData->setTempMin($data['main']['temp_min'] ?? null);
        $weatherData->setTempMax($data['main']['temp_max'] ?? null);
        $weatherData->setPressure($data['main']['pressure'] ?? null);
        $weatherData->setHumidity($data['main']['humidity'] ?? null);

        // Wind data
        $weatherData->setWindSpeed($data['wind']['speed'] ?? null);
        $weatherData->setWindDirection($data['wind']['deg'] ?? null);
        // Visibility
        $weatherData->setVisibility($data['visibility'] ?? null);

        // Cloudiness
        $weatherData->setCloudiness($data['clouds']['all'] ?? null);

        // Weather description
        $weatherData->setWeatherDescription($data['weather'][0]['description'] ?? null);
        $weatherData->setWeatherMain($data['weather'][0]['main'] ?? null);
        $weatherData->setWeatherIcon($data['weather'][0]['icon'] ?? null);

        // Coordinates
        $weatherData->setLatitude($data['coord']['lat'] ?? null);
        $weatherData->setLongitude($data['coord']['lon'] ?? null);

        // Timestamps
        $weatherData->setCreatedAt(new DateTime());
        $weatherData->setTimezone($data['timezone'] ?? null);
        $weatherData->setTimestamp(isset($data['dt']) ? (new DateTime())->setTimestamp($data['dt']) : null);
        $weatherData->setSunrise(isset($data['sys']['sunrise']) ? (new DateTime())->setTimestamp($data['sys']['sunrise']) : null);
        $weatherData->setSunset(isset($data['sys']['sunset']) ? (new DateTime())->setTimestamp($data['sys']['sunset']) : null);

        return $weatherData;
    }
}