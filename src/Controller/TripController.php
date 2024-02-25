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
                'starting_trip' => $trip->getStartingTrip(),
                'arrival_trip' => $trip->getArrivalTrip(),
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
}
