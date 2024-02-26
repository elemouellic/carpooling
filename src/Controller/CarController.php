<?php

namespace App\Controller;

use App\Entity\Brand;
use App\Entity\Car;
use App\Entity\Student;
use App\Entity\User;
use App\Security\AdminRoleChecker;
use App\Security\TokenAuth;
use App\Security\TokenUserProvider;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class CarController extends AbstractController
{

    private TokenAuth $tokenAuth;
    private AdminRoleChecker $adminRoleChecker;

    public function __construct(TokenAuth $tokenAuth, AdminRoleChecker $adminRoleChecker)
    {
        $this->tokenAuth = $tokenAuth;
        $this->adminRoleChecker = $adminRoleChecker;
    }


    #[Route('/insertcar', name: 'app_car_insert', methods: ['POST'])]
    public function insertCar(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $token = $request->headers->get('X-AUTH-TOKEN');
            $user = $this->tokenAuth->getUserFromToken($token);
        } catch (CustomUserMessageAuthenticationException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }

        $data = json_decode($request->getContent(), true);

        // Check if all necessary fields are present and not empty
        if (empty($data['car_model']) || empty($data['matriculation']) || empty($data['number_places']) || empty($data['brand'])) {
            return $this->json([
                'error' => 'Missing one or more required fields',
            ], 400);
        }

        // Check if a car with the same matriculation already exists
        $existingCar = $em->getRepository(Car::class)->findOneBy(['matriculation' => $data['matriculation']]);

        if ($existingCar) {
            return $this->json([
                'error' => 'A car with the same matriculation already exists',
            ], 400);
        }


        try {
            // Create a new car
            $car = new Car();
            $car->setCarModel($data['car_model']);
            $car->setMatriculation($data['matriculation']);
            $car->setNumberPlaces($data['number_places']);
            $em->persist($car); // Persist the new Car entity

            // Get the brand from the database using the brand name
            $brand = $em->getRepository(Brand::class)->findOneBy(['carBrand' => $data['brand']]);

            // If the brand is not found, create a new one
            if (!$brand) {
                $brand = new Brand();
                $brand->setCarBrand($data['brand']);
                $em->persist($brand);
            }

            // Set the brand to the car
            $car->setIdentify($brand);

            // Persist the car
            $em->persist($car);
            $em->flush();
            $result = [
                'message' => "Car created",
                'car' => $car->getCarModel(),
                'matriculation' => $car->getMatriculation(),
                'number_places' => $car->getNumberPlaces(),
                'brand' => $brand->getCarBrand()

            ];

        } catch (RandomException|Exception $e) {
            $result = "Error while creating the car: " . $e->getMessage();
        }

        // Return the car data
        return new JsonResponse($result);
    }

    #[Route('/deletecar/{id}', name: 'app_car_delete', methods: ['DELETE'])]
    public function deleteCar(Request $request, int $id, EntityManagerInterface $em): JsonResponse
    {
        // Check if the current user has the 'ROLE_ADMIN' role
        $response = $this->adminRoleChecker->checkAdminRole();
        if ($response) {
            return $response;
        }

        // Get the car from the database using the id
        $car = $em->getRepository(Car::class)->find($id);

        // If the car is not found, return an error
        if (!$car) {
            return $this->json([
                'error' => 'Car not found',
            ], 404);
        }

        // Remove the car from the database
        $em->remove($car);
        $em->flush();

        // Return a success message
        return $this->json([
            'message' => 'Car deleted',
        ]);
    }

    #[Route('/listallcars', name: 'app_car_list', methods: ['GET'])]
    public function listAllCars(EntityManagerInterface $em): JsonResponse
    {
        // Check if the current user has the 'ROLE_ADMIN' role
        $response = $this->adminRoleChecker->checkAdminRole();
        if ($response) {
            return $response;
        }


        // Get all cars from the database
        $cars = $em->getRepository(Car::class)->findAll();

        // Create an array to store the cars data
        $data = [];

        // Loop through the cars and add the data to the array
        foreach ($cars as $car) {
            $data[] = [
                'id' => $car->getId(),
                'car_model' => $car->getCarModel(),
                'matriculation' => $car->getMatriculation(),
                'number_places' => $car->getNumberPlaces(),
                'brand' => $car->getIdentify() ? $car->getIdentify()->getCarBrand() : null,
            ];
        }

        // Return the cars data
        return $this->json($data);
    }
}
