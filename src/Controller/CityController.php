<?php

namespace App\Controller;

use App\Entity\City;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

class CityController extends AbstractController
{


    #[Route('/insertcity', name: 'app_city_insert', methods: ['POST'])]
    public function insertCity(Request $request, EntityManagerInterface $em): JsonResponse
    {

        $data = json_decode($request->getContent(), true);

        // Check if all necessary fields are present and not empty
        if (empty($data['name']) || empty($data['zip_code'])) {
            return $this->json([
                'error' => 'Missing one or more required fields',
            ], 400);
        }

        // Check if a city with the same name and zip code already exists
        $existingCity = $em->getRepository(City::class)->findOneBy([
            'name' => $data['name'],
            'zipCode' => $data['zip_code'],
        ]);

        if ($existingCity) {
            return $this->json([
                'error' => 'A city with the same name and zip code already exists',
            ], 400);
        }


        try {
            // Create a new city
            $city = new City();
            $city->setName($data['name']);
            $city->setZipCode($data['zip_code']);
            $em->persist($city); // Persist the new City entity
            $em->flush(); // Save the changes to the database

            $result = [
                'message' => "City created",
                'city' => $city->getName(),
                'zip_code' => $city->getZipCode()
            ];

        } catch (Exception $e) {
            $result = "Error while creating the city: " . $e->getMessage();

        }
        // Return the city data
        return new JsonResponse($result);

    }

#[Route('/deletecity/{id}', name: 'app_city_delete', methods: ['DELETE'])]
    public function deleteCity(Request $request, int $id, EntityManagerInterface $em): JsonResponse
    {

        // Get the city from the database
        $city = $em->getRepository(City::class)->find($id);


        // If the city is not found, return an error
        if (!$city) {
            return $this->json([
                'error' => 'City not found',
            ], 404);
        }

        // Remove the city from the database
        $em->remove($city);
        $em->flush();

        // Return a success message
        return $this->json([
            'message' => 'City deleted',
        ]);
    }

    #[Route('/listallcities', name: 'app_city_list', methods: ['GET'])]
    public function listAllCities(EntityManagerInterface $em): JsonResponse
    {

        // Get all cities from the database
        $cities = $em->getRepository(City::class)->findAll();

        // Create an array to store the cities data
        $data = [];

        // Loop through the cities and add the data to the array
        foreach ($cities as $city) {
            $data[] = [
                'id' => $city->getId(),
                'name' => $city->getName(),
            ];
        }

        // Return the cities data
        return $this->json($data);
    }
    #[Route('/listallzipcodes', name: 'app_zipcode_list', methods: ['GET'])]
    public function listAllZipCodes(EntityManagerInterface $em): JsonResponse
    {
        // Get all cities from the database
        $zipCodes = $em->getRepository(City::class)->findAll();

        // Create an array to store the cities data
        $data = [];

        // Loop through the cars and add the data to the array
        foreach ($zipCodes as $zipcode) {
            $data[] = [
                'id' => $zipcode->getId(),
                'zipcode' => $zipcode->getZipCode(),
            ];
        }

        // Return the cities data
        return $this->json($data);
    }




}
