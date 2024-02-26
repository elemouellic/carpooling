<?php

namespace App\Controller;

use App\Entity\Brand;
use App\Entity\Car;
use App\Entity\City;
use App\Entity\Student;
use App\Entity\User;
use App\Security\AdminRoleChecker;
use App\Security\TokenAuth;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class StudentController extends AbstractController
{
    private TokenAuth $tokenAuth;

    // Add the TokenAuth to the constructor to get the user from the token
    public function __construct(TokenAuth $tokenAuth, AdminRoleChecker $adminRoleChecker)
    {
        $this->tokenAuth = $tokenAuth;
    }
    #[Route('/insertstudent', name: 'app_student_insert', methods: ['POST'])]
    public function insertStudent(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $token = $request->headers->get('X-AUTH-TOKEN');
            $user = $this->tokenAuth->getUserFromToken($token);
        } catch (CustomUserMessageAuthenticationException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }

        // Get the request data
        $data = json_decode($request->getContent(), true);

        // Check if all necessary fields are present and not empty
        if (empty($data['firstname']) || empty($data['name']) || empty($data['phone']) || empty($data['email'])) {
            return $this->json([
                'error' => 'Missing one or more required fields',
            ], 400);
        }


        // Create a new Student entity and set the user as the register
        $student = $em->getRepository(Student::class)->findOneBy(['register' => $user]);
        if (!$student) {
            $student = new Student();
            // Provide the student with the UserInterface instead of User entity
            // It works because User entity implements UserInterface
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

    #[Route('/updatestudent', name: 'app_student_update', methods: ['PUT'])]
    public function updateStudent(Request $request, EntityManagerInterface $em): JsonResponse
    {


        // Get the request data
        $data = json_decode($request->getContent(), true);

        // Check if all necessary fields are present and not empty
        if (empty($data['firstname']) || empty($data['name']) || empty($data['phone']) || empty($data['email']) || empty($data['idstudent'])) {
            return $this->json([
                'error' => 'Missing one or more required fields',
            ], 400);
        }

        // Get the student from the database using the idstudent
        $student = $em->getRepository(Student::class)->find($data['idstudent']);

        // If the student is not found, return an error
        if (!$student) {
            return $this->json([
                'error' => 'Student not found',
            ], 404);
        }

        // Update the student entity with the request data
        $student->setFirstname($data['firstname']);
        $student->setName($data['name']);
        $student->setPhone($data['phone']);
        $student->setEmail($data['email']);

        // Check if the car, matriculation and number of places fields are present
        if (isset($data['car']) && isset($data['matriculation']) && isset($data['number_places'])) {
            $car = $student->getPossess();
            if (!$car) {
                $car = new Car();
                $student->setPossess($car);
            }
            $car->setCarModel($data['car']);
            $car->setMatriculation($data['matriculation']);
            $car->setNumberPlaces($data['number_places']);
            // Check if the brand field is present and update the Brand entity if it exists
            if (isset($data['brand'])) {
                $brand = $car->getIdentify();
                if (!$brand) {
                    $brand = new Brand();
                    $brand->setCarBrand($data['brand']);
                    $car->setIdentify($brand);
                    $em->persist($brand);
                } else {
                    $brand->setCarBrand($data['brand']);
                }
            }
            $em->persist($car);
        } else {
            // If the car information is not present in the request, remove the Car from the Student
            $car = $student->getPossess();
            if ($car) {
                $student->setPossess(null);
                $em->remove($car);
            }
        }

        $em->flush();

        return $this->json([
            'message' => 'Student updated successfully',
        ]);


    }

    #[Route('/deletestudent/{id}', name: 'app_student_delete', methods: ['DELETE'])]
    public function deleteStudent(int $id, EntityManagerInterface $em): JsonResponse
    {


        // Get the student from the database using the id
        $student = $em->getRepository(Student::class)->find($id);

        // If the student is not found, return an error
        if (!$student) {
            return $this->json([
                'error' => 'Student not found',
            ], 404);
        }

        // Check if the student is an admin
        if (in_array('ROLE_ADMIN', $student->getRegister()->getRoles())) {
            return $this->json([
                'error' => 'Cannot delete an admin',
            ], 403);
        }

        // Remove the student from the database
        $em->remove($student);
        $em->flush();

        return $this->json([
            'message' => 'Student deleted successfully',
        ]);
    }

    #[Route('/selectstudent/{id}', name: 'app_student_get', methods: ['GET'])]
    public function getStudent(int $id, EntityManagerInterface $em, Request $request): JsonResponse
    {


        // Get the student from the database using the id
        $student = $em->getRepository(Student::class)->find($id);

        // If the student is not found, return an error
        if (!$student) {
            return $this->json([
                'error' => 'Student not found',
            ], 404);
        }

        // Return the student data
        return $this->json([
            'id' => $student->getId(),
            'firstname' => $student->getFirstname(),
            'name' => $student->getName(),
            'phone' => $student->getPhone(),
            'email' => $student->getEmail(),
            'city' => $student->getLive()->getName(),
            'zip_code' => $student->getLive()->getZipCode(),
            'car' => $student->getPossess() ? $student->getPossess()->getCarModel() : null,
            'matriculation' => $student->getPossess() ? $student->getPossess()->getMatriculation() : null,
            'number_places' => $student->getPossess() ? $student->getPossess()->getNumberPlaces() : null,
            'brand' => $student->getPossess() && $student->getPossess()->getIdentify() ? $student->getPossess()->getIdentify()->getCarBrand() : null,
        ]);
    }

    #[Route('/listallstudents', name: 'app_student_list', methods: ['GET'])]
    public function listAllStudents(EntityManagerInterface $em): JsonResponse
    {

        // Get all students from the database
        $students = $em->getRepository(Student::class)->findAll();

        // Create an array of students data
        $result = [];
        foreach ($students as $student) {
            $result[] = [
                'id' => $student->getId(),
                'firstname' => $student->getFirstname(),
                'name' => $student->getName(),
                'phone' => $student->getPhone(),
                'email' => $student->getEmail(),
                'city' => $student->getLive()->getName(),
                'zip_code' => $student->getLive()->getZipCode(),
                'car' => $student->getPossess() ? $student->getPossess()->getCarModel() : null,
                'matriculation' => $student->getPossess() ? $student->getPossess()->getMatriculation() : null,
                'number_places' => $student->getPossess() ? $student->getPossess()->getNumberPlaces() : null,
                'brand' => $student->getPossess() && $student->getPossess()->getIdentify() ? $student->getPossess()->getIdentify()->getCarBrand() : null,
            ];
        }

        // Return the students data
        return $this->json($result);
    }


}
