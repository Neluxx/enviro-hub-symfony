<?php

namespace App\Controller;

use App\Entity\EnvironmentalData;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class ApiController
{
    #[Route('/api/data', name: 'api_data', methods: ['POST'])]
    public function saveData(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['temperature'], $data['humidity'], $data['pressure'], $data['co2'], $data['created'])) {
            return new JsonResponse(['error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        $environmentalData = new EnvironmentalData();
        $environmentalData->setTemperature((float) $data['temperature']);
        $environmentalData->setHumidity((float) $data['humidity']);
        $environmentalData->setPressure((float) $data['pressure']);
        $environmentalData->setCo2((float) $data['co2']);
        $environmentalData->setMeasuredAt(new DateTime($data['created']));
        $environmentalData->setCreatedAt(new DateTime());

        $entityManager->persist($environmentalData);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Data saved successfully'], Response::HTTP_CREATED);
    }
}
