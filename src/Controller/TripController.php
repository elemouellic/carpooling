<?php

namespace App\Controller;

use App\Entity\Car;
use App\Entity\City;
use App\Entity\Student;
use App\Entity\Trip;
use App\Security\TokenUserProvider;
use DateTime;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class TripController extends AbstractController
{
    private TokenUserProvider $tokenUserProvider;

    public function __construct(TokenUserProvider $tokenUserProvider)
    {
        $this->tokenUserProvider = $tokenUserProvider;
    }

    #[Route('/inserttrip', name: 'app_trip_insert', methods: ['POST'])]
    public function insertTrip(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Get the token from the request headers
        $token = $request->headers->get('X-AUTH-TOKEN');
        try {
            $user = $this->tokenUserProvider->loadUserByIdentifier($token);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }

        $data = json_decode($request->getContent(), true);
        // Check if all necessary fields are present and not empty
        if (empty($data['km_distance']) || empty($data['student_id']) || empty($data['travel_date']) || empty($data['starting_trip']) || empty($data['arrival_trip']) || empty($data['places_offered'])) {
            return $this->json([
                'error' => 'Missing one or more required fields',
            ], 400);
        }

        // Get the Student and City entities from the database
        $student = $em->getRepository(Student::class)->find($data['student_id']);
        $startingCity = $em->getRepository(City::class)->find($data['starting_trip']);
        $arrivalCity = $em->getRepository(City::class)->find($data['arrival_trip']);


        if (!$student || !$startingCity || !$arrivalCity) {
            return $this->json([
                'error' => 'Student or City not found',
            ], 404);
        }

        $car = $student->getPossess();
        if (!$car) {
            return $this->json([
                'error' => 'The student does not have a car',
            ], 400);
        }

        $seats = $car->getNumberPlaces();
        if ($data['places_offered'] != $seats - 1) {
            return $this->json([
                'error' => 'The number of offered places does not correspond to the number of seats in the car minus one',
            ], 400);
        }

        // Dans votre méthode insertTrip
        $startingCity = $em->getRepository(City::class)->find($data['starting_trip']);
        $arrivalCity = $em->getRepository(City::class)->find($data['arrival_trip']);

        if (!$startingCity || !$arrivalCity) {
            return $this->json([
                'error' => 'City not found',
            ], 404);
        }

        if ($startingCity->getId() == $arrivalCity->getId()) {
            return $this->json([
                'error' => 'The departure city cannot be the same as the arrival city',
            ], 400);
        }

        // Check if a trip with the same student, travel date, starting city and arrival city already exists
        try {
            $existingTrip = $em->getRepository(Trip::class)->findOneBy([
                'student' => $student,
                'travelDate' => new \DateTime($data['travel_date']),
                'startingTrip' => $startingCity,
                'arrivalTrip' => $arrivalCity,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
        if ($existingTrip) {
            return $this->json([
                'error' => 'A trip with the same student, travel date, starting city and arrival city already exists',
            ], 400);
        }

        try {
            // Create a new trip
            $trip = new Trip();
            $trip->setKmDistance($data['km_distance']);
            $trip->addStudent($student);
            $trip->setStudent($student);
            try {
                $trip->setTravelDate(new DateTime($data['travel_date']));
            } catch (\Exception $e) {
                return new JsonResponse(['error' => $e->getMessage()], 400);
            }
            $trip->setStartingTrip($startingCity);
            $trip->setArrivalTrip($arrivalCity);
            $trip->setPlacesOffered($data['places_offered']);

            // Save the new trip
            $em->persist($trip);
            $em->flush();

            $result = [
                'message' => 'Trip created successfully',
                'trip' => $trip->getId(),
                'student' => $student->getId(),
                'km_distance' => $trip->getKmDistance(),
                'travel_date' => $trip->getTravelDate(),
                'starting_trip' => $startingCity->getName(),
                'arrival_trip' => $arrivalCity->getName(),
                'places_offered' => $trip->getPlacesOffered(),
            ];

        } catch (Exception $e) {
            $result = "Error while creating the trip: " . $e->getMessage();
        }
        // Return the trip data
        return new JsonResponse($result);
    }

    #[Route('/searchtrip/{idCityStart}/{idCityArrival}/{dateTravel}', name: 'app_trip_search', methods: ['GET'])]
    public function searchTrip(Request $request, EntityManagerInterface $em, $idCityStart, $idCityArrival, $dateTravel): JsonResponse
    {
        // Get the token from the request headers
        $token = $request->headers->get('X-AUTH-TOKEN');
        try {
            $user = $this->tokenUserProvider->loadUserByIdentifier($token);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }

        // Convert the date string to a DateTime object
        try {
            $dateTravel = new \DateTime($dateTravel);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }

        // Get the trip from the database
        $trips = $em->getRepository(Trip::class)->findBy([
            'startingTrip' => $idCityStart,
            'arrivalTrip' => $idCityArrival,
            'travelDate' => $dateTravel,
        ]);

        $data = [];
        foreach ($trips as $trip) {
            $data[] = [
                'id' => $trip->getId(),
                'student' => $trip->getStudent()->getId(),
                'starting_trip' => $trip->getStartingTrip()->getId(),
                'arrival_trip' => $trip->getArrivalTrip()->getId(),
                'km_distance' => $trip->getKmDistance(),
                'travel_date' => $trip->getTravelDate()->format('Y-m-d'), // Format the date
                'places_offered' => $trip->getPlacesOffered(),
            ];
        }
        // Return the brands data
        return $this->json($data);
    }


    #[Route('/listalltrips', name: 'app_trip_list', methods: ['GET'])]
    public function listAllTrips(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Check if the current user has the 'ROLE_ADMIN' role
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json([
                'error' => 'Access denied',
            ], 403);
        }

        // Get the trip from the database
        $trips = $em->getRepository(Trip::class)->findAll();

        $data = [];
        foreach ($trips as $trip) {
            $data[] = [
                'id' => $trip->getId(),
                'student' => $trip->getStudent()->getId(),
                'starting_trip' => $trip->getStartingTrip()->getId(),
                'arrival_trip' => $trip->getArrivalTrip()->getId(),
                'km_distance' => $trip->getKmDistance(),
                'travel_date' => $trip->getTravelDate()->format('Y-m-d'), // Format the date
                'places_offered' => $trip->getPlacesOffered(),
            ];
        }
        // Return the brands data
        return $this->json($data);
    }

    #[Route('/deletetrip/{id}', name: 'app_trip_delete', methods: ['DELETE'])]
    public function deleteTrip(Request $request, int $id, EntityManagerInterface $em): JsonResponse
    {
        // Check if the current user has the 'ROLE_ADMIN' role
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json([
                'error' => 'Access denied',
            ], 403);
        }

        // Get the token from the request headers
        $token = $request->headers->get('X-AUTH-TOKEN');
        try {
            $user = $this->tokenUserProvider->loadUserByIdentifier($token);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }

        // Get the trip from the database using the id
        $trip = $em->getRepository(Trip::class)->find($id);

        // If the trip is not found, return an error
        if (!$trip) {
            return $this->json([
                'error' => 'Trip not found',
            ], 404);
        }

        // Remove the trip from the database
        $em->remove($trip);
        $em->flush();

        // Return a success message
        return $this->json([
            'message' => 'Trip deleted',
        ]);
    }

    // This routes refer to the association table between the trip and the student
    #[Route('/insertparticipation', name: 'app_trip_insert_participation', methods: ['POST'])]
    public function insertParticipation(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Get the token from the request headers
        $token = $request->headers->get('X-AUTH-TOKEN');
        try {
            $user = $this->tokenUserProvider->loadUserByIdentifier($token);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }

        $data = json_decode($request->getContent(), true);
        // Check if all necessary fields are present and not empty
        if (empty($data['trip_id']) || empty($data['student_id'])) {
            return $this->json([
                'error' => 'Missing one or more required fields',
            ], 400);
        }

        // Get the Trip and Student entities from the database
        $trip = $em->getRepository(Trip::class)->find($data['trip_id']);
        $student = $em->getRepository(Student::class)->find($data['student_id']);

        if (!$trip || !$student) {
            return $this->json([
                'error' => 'Trip or Student not found',
            ], 404);
        }

        // Check if the student is already participating in the trip
        if ($trip->getStudents()->contains($student)) {
            return $this->json([
                'error' => 'The student is already participating in the trip',
            ], 400);
        }

        // Add the student to the trip
        $trip->addStudent($student);

        // Save the new participation
        $em->persist($trip);
        $em->flush();

        // Return a success message
        return $this->json([
            'message' => 'Participation added successfully',
        ]);
    }

    #[Route('/listallparticipations', name: 'app_trip_list_participation', methods: ['GET'])]
    public function listAllParticipations(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Check if the current user has the 'ROLE_ADMIN' role
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json([
                'error' => 'Access denied',
            ], 403);
        }

        // Get the participations from the database
        $participations = $em->getRepository(Trip::class)->createQueryBuilder('t')
            ->leftJoin('t.students', 's')
            ->addSelect('s')
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($participations as $participation) {
            $studentsData = [];
            foreach ($participation->getStudents() as $student) {
                $studentsData[] = [
                    'id' => $student->getId(),
                    'name' => $student->getName(),
                    'firstname' => $student->getFirstname(),
                    'email' => $student->getEmail()
                ];
            }
            $data[] = [
                'id' => $participation->getId(),
                'trip' => $participation->getId(),
                'driver' => [
                    'id' => $participation->getStudent()->getId(),
                    'name' => $participation->getStudent()->getName(),
                    'firstname' => $participation->getStudent()->getFirstname(),
                    'email' => $participation->getStudent()->getEmail(),
                ],
                'students' => $studentsData
            ];
        }
        // Return the participations data
        return $this->json($data);
    }

    #[Route('/deleteparticipation/{tripid}', name: 'app_trip_delete_participation', methods: ['DELETE'])]
    public function deleteParticipation(Request $request, int $tripid, EntityManagerInterface $em): JsonResponse
    {
        // Get the token from the request headers
        $token = $request->headers->get('X-AUTH-TOKEN');
        try {
            $user = $this->tokenUserProvider->loadUserByIdentifier($token);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }

        // Get the trip from the database using the id
        $trip = $em->getRepository(Trip::class)->find($tripid);

        // If the trip is not found, return an error
        if (!$trip) {
            return $this->json([
                'error' => 'Trip not found',
            ], 404);
        }

        // Check if the user is a participant of the trip
        if (!$trip->getStudents()->contains($trip->getStudent())) {
            return $this->json([
                'error' => 'You are not a participant of this trip',
            ], 403);
        }

        // Remove the user from the trip participants
        $trip->removeStudent($trip->getStudent());

        // Save the changes
        $em->persist($trip);
        $em->flush();

        // Return a success message
        return $this->json([
            'message' => 'Participation deleted',
        ]);
    }

    #[Route('/getdriverontrip/{tripid}', name: 'app_trip_get_driver', methods: ['GET'])]
    public function getDriverOnTrip(Request $request, EntityManagerInterface $em, $tripid): JsonResponse
    {
        // Get the token from the request headers
        $token = $request->headers->get('X-AUTH-TOKEN');
        try {
            $user = $this->tokenUserProvider->loadUserByIdentifier($token);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }

        // Get the trip from the database using the id
        $trip = $em->getRepository(Trip::class)->find($tripid);

        // If the trip is not found, return an error
        if (!$trip) {
            return $this->json([
                'error' => 'Trip not found',
            ], 404);
        }

        // Get the driver from the trip
        $driver = $trip->getStudent();

        // Prepare the driver data
        $data = [
            'id' => $driver->getId(),
            'name' => $driver->getName(),
            'firstname' => $driver->getFirstname(),
            'email' => $driver->getEmail(),
        ];

        // Return the driver data
        return $this->json($data);
    }

#[Route('/getstudentontrips/{studentid}', name: 'app_trip_get_student', methods: ['GET'])]
    public function getStudentOnTrips(Request $request, EntityManagerInterface $em, $studentid): JsonResponse
    {
        // Get the token from the request headers
        $token = $request->headers->get('X-AUTH-TOKEN');
        try {
            $user = $this->tokenUserProvider->loadUserByIdentifier($token);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }

        // Get the student from the database using the id
        $student = $em->getRepository(Student::class)->find($studentid);

        // If the student is not found, return an error
        if (!$student) {
            return $this->json([
                'error' => 'Student not found',
            ], 404);
        }

        // Get the trips of the student
        $trips = $student->getDrive();

        $data = [];
        foreach ($trips as $trip) {
            $data[] = [
                'id' => $trip->getId(),
                'starting_trip' => $trip->getStartingTrip()->getId(),
                'arrival_trip' => $trip->getArrivalTrip()->getId(),
                'km_distance' => $trip->getKmDistance(),
                'travel_date' => $trip->getTravelDate()->format('Y-m-d'), // Format the date
                'places_offered' => $trip->getPlacesOffered(),
            ];
        }
        // Return the trips data
        return $this->json($data);
    }
}
