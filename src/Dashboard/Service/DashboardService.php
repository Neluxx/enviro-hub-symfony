<?php

declare(strict_types=1);

namespace App\Dashboard\Service;

use App\Api\SensorData\Entity\SensorData;
use App\Api\SensorData\Repository\SensorDataRepository;
use DateTime;
use DateTimeZone;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Dashboard Service.
 *
 * Handles business logic for dashboard data processing and aggregation.
 */
class DashboardService
{
    private const int TEMP_MIN = 10;
    private const int TEMP_MAX = 30;
    private const int HUMIDITY_MIN = 0;
    private const int HUMIDITY_MAX = 100;
    private const int CO2_MIN = 400;
    private const int CO2_MAX = 2000;

    private SensorDataRepository $repository;
    private ChartBuilderInterface $chartBuilder;

    public function __construct(SensorDataRepository $repository, ChartBuilderInterface $chartBuilder)
    {
        $this->repository = $repository;
        $this->chartBuilder = $chartBuilder;
    }

    /**
     * Get chart data for a specific time range and node UUID.
     *
     * @param string $nodeUuid The node UUID
     * @param string $range Time range string
     *
     * @return array{labels: array<string>, temperature: array<int>, humidity: array<int>, co2: array<int|null>}
     */
    public function getChartDataByNodeUuid(string $nodeUuid, string $range): array
    {
        $timezone = new DateTimeZone('Europe/Berlin');
        $endDate = new DateTime('now', $timezone);
        $startDate = (clone $endDate)->modify($range);

        $data = $this->repository->findByNodeUuidAndDateRange($nodeUuid, $startDate, $endDate);

        if (empty($data)) {
            return [
                'labels' => [],
                'temperature' => [],
                'humidity' => [],
                'co2' => [],
            ];
        }

        return $this->formatChartData($data);
    }

    public function createTemperatureChart(array $labels, array $data): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Temperature (°C)',
                    'backgroundColor' => 'rgba(255, 99, 132, 0.1)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => $data,
                    'tension' => 0.4,
                    'cubicInterpolationMode' => 'monotone',
                    'spanGaps' => true,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 4,
                    'borderWidth' => 2,
                ],
            ],
        ]);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'x' => ['grid' => ['color' => 'rgba(255, 255, 255, 0.1)']],
                'y' => [
                    'grid' => ['color' => 'rgba(255, 255, 255, 0.1)'],
                    'beginAtZero' => false,
                    'suggestedMin' => self::TEMP_MIN,
                    'suggestedMax' => self::TEMP_MAX,
                ],
            ],
        ]);

        return $chart;
    }

    public function createHumidityChart(array $labels, array $data): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Humidity (%)',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.1)',
                    'borderColor' => 'rgb(54, 162, 235)',
                    'data' => $data,
                    'tension' => 0.4,
                    'cubicInterpolationMode' => 'monotone',
                    'spanGaps' => true,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 4,
                    'borderWidth' => 2,
                ],
            ],
        ]);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'x' => ['grid' => ['color' => 'rgba(255, 255, 255, 0.1)']],
                'y' => [
                    'grid' => ['color' => 'rgba(255, 255, 255, 0.1)'],
                    'beginAtZero' => false,
                    'suggestedMin' => self::HUMIDITY_MIN,
                    'suggestedMax' => self::HUMIDITY_MAX,
                ],
            ],
        ]);

        return $chart;
    }

    public function createCo2Chart(array $labels, array $data): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'CO₂ (ppm)',
                    'backgroundColor' => 'rgba(75, 192, 192, 0.1)',
                    'borderColor' => 'rgb(75, 192, 192)',
                    'data' => $data,
                    'tension' => 0.4,
                    'cubicInterpolationMode' => 'monotone',
                    'spanGaps' => true,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 4,
                    'borderWidth' => 2,
                ],
            ],
        ]);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'x' => ['grid' => ['color' => 'rgba(255, 255, 255, 0.1)']],
                'y' => [
                    'grid' => ['color' => 'rgba(255, 255, 255, 0.1)'],
                    'beginAtZero' => false,
                    'suggestedMin' => self::CO2_MIN,
                    'suggestedMax' => self::CO2_MAX,
                ],
            ],
        ]);

        return $chart;
    }

    /**
     * Format data into chart-ready structure.
     *
     * @param array<SensorData|array{label: string, temperature: int, humidity: int, co2: int|null}> $data
     *
     * @return array{labels: array<string>, temperature: array<int>, humidity: array<int>, co2: array<int|null>}
     */
    private function formatChartData(array $data): array
    {
        $chartData = [
            'labels' => [],
            'temperature' => [],
            'humidity' => [],
            'co2' => [],
        ];

        foreach ($data as $entry) {
            $chartData['labels'][] = $entry->getMeasuredAt()->format('d.m H:i');
            $chartData['temperature'][] = $entry->getTemperature();
            $chartData['humidity'][] = $entry->getHumidity();
            $chartData['co2'][] = $entry->getCarbonDioxide();
        }

        return $chartData;
    }
}
