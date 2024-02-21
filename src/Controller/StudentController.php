<?php

namespace App\Controller;

use App\Entity\Brand;
use App\Entity\Car;
use App\Entity\City;
use App\Entity\Student;
use App\Entity\User;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class StudentController extends AbstractController
{

    #[Route('/insertstudent', name: 'app_student_insert', methods: ['POST'])]
    public function insertStudent(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Get the request data
        $data = json_decode($request->getContent(), true);

        // Check if all necessary fields are present and not empty
        if (empty($data['firstname']) || empty($data['name']) || empty($data['phone']) || empty($data['email'])) {
            return $this->json([
                'error' => 'Missing one or more required fields',
            ], 400);
        }

        // Get the token from the request headers
        $token = $request->headers->get('X-AUTH-TOKEN');

        // Get the user from the database using the token
        $user = $em->getRepository(User::class)->findOneBy(['token' => $token]);

        // If the user is not found, return an error
        if (!$user) {
            return $this->json([
                'error' => 'Invalid token',
            ], 401);
        }

        // Create a new Student entity and set the user as the register
        $student = $em->getRepository(Student::class)->findOneBy(['register' => $user]);
        if (!$student) {
            $student = new Student();
            $student->setRegister($user);
        }

        // Get the city from the database using the city name
        $city = $em->getRepository(City::class)->findOneBy(['name' => $data['city']]);

        if (!$city) {
            $city = new City();
            $city->setName($data['city']);
            if (isset($data['zip_code'])) {
                $city->setZipCode($data['zip_code']);
            } else {
                return $this->json([
                    'error' => 'zip_code is required',
                ], 400);
            }
            $em->persist($city);
        }

        // Instantiate a new Car at null value
        $car = null;
        //Check if the car, matriculation and cumber of places fields are present
        if (isset($data['car']) && isset($data['matriculation']) && isset($data['number_places'])) {
            $car = $em->getRepository(Car::class)->findOneBy(['carModel' => $data['car']]);

            if (!$car) {
                $car = new Car();
                $car->setCarModel($data['car']);
                $car->setMatriculation($data['matriculation']);
                $car->setNumberPlaces($data['number_places']);
                // Check if the brand field is present and create a new Brand entity if it does not exist
                if (isset($data['brand'])) {
                    $brand = $em->getRepository(Brand::class)->findOneBy(['carBrand' => $data['brand']]);
                    if (!$brand) {
                        $brand = new Brand();
                        $brand->setCarBrand($data['brand']);
                        $em->persist($brand);
                    }
                    $car->setIdentify($brand);
                }
                $em->persist($car);
            }
        }

        // Update the student entity with the request data
        $student->setFirstname($data['firstname']);
        $student->setName($data['name']);
        $student->setPhone($data['phone']);
        $student->setEmail($data['email']);
        $student->setLive($city);
        // Null if the student does not have a car
        if ($car !== null) {
            $student->setPossess($car);
        }
        $em->persist($student);
        $em->flush();

        return $this->json([
            'message' => 'Profile updated successfully',
        ]);
    }



}
