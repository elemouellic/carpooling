<?php

namespace App\Controller;

use App\Entity\Brand;
use App\Security\TokenAuth;
use App\Security\TokenUserProvider;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class BrandController extends AbstractController
{
    private TokenAuth $tokenAuth;

    public function __construct(TokenAuth $tokenAuth)
    {
        $this->tokenAuth = $tokenAuth;
    }

    #[Route('/insertbrand', name: 'app_brand_insert', methods: ['POST'])]
    public function insertBrand(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $token = $request->headers->get('X-AUTH-TOKEN');
            $user = $this->tokenAuth->getUserFromToken($token);
        } catch (CustomUserMessageAuthenticationException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }

        $data = json_decode($request->getContent(), true);

        // Check if all necessary fields are present and not empty
        if (empty($data['car_brand'])) {
            return $this->json([
                'error' => 'Missing one or more required fields',
            ], 400);
        }

        // Check if a brand with the same name already exists
        $existingBrand = $em->getRepository(Brand::class)->findOneBy(['carBrand' => $data['car_brand']]);

        if ($existingBrand) {
            return $this->json([
                'error' => 'A brand with the same name already exists',
            ], 400);
        }

        try {
            // Create a new brand
            $brand = new Brand();
            $brand->setCarBrand($data['car_brand']);

            // Save the new brand
            $em->persist($brand);
            $em->flush();

            $result = [
                'message' => 'Brand created successfully',
                'brand' => $brand->getCarBrand(),
            ];
        } catch (Exception $e) {
            $result = "Error while creating the brand: " . $e->getMessage();
        }
        // Return the brand data
        return new JsonResponse($result);
    }
    #[Route('/deletebrand/{id}', name: 'app_brand_delete', methods: ['DELETE'])]
    public function deleteBrand(Request $request, $id, EntityManagerInterface $em): JsonResponse
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

        // Get the brand from the database
        $brand = $em->getRepository(Brand::class)->find($id);

        // If the brand does not exist, return an error
        if (!$brand) {
            return $this->json([
                'error' => 'Brand not found',
            ], 404);
        }

        try {
            // Remove the brand from the database
            $em->remove($brand);
            $em->flush();
        } catch (ForeignKeyConstraintViolationException $e) {
            return $this->json([
                'error' => 'Cannot delete this brand because it is referenced by other entities',
            ], 409);
        }

        // Return a success message
        return $this->json([
            'message' => 'Brand deleted successfully',
        ]);
    }

    #[Route('/listallbrands', name: 'app_brand_list', methods: ['GET'])]
    public function listAllBrands(EntityManagerInterface $em): JsonResponse
    {
        // Check if the current user has the 'ROLE_ADMIN' role
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json([
                'error' => 'Access denied',
            ], 403);
        }


        // Get all brands from the database
        $brands = $em->getRepository(Brand::class)->findAll();

        // Create an array to store the brands data
        $data = [];

        // Loop through the brands and add the data to the array
        foreach ($brands as $brand) {
            $data[] = [
                'id' => $brand->getId(),
                'brand' => $brand->getCarBrand(),
            ];
        }

        // Return the brands data
        return $this->json($data);
    }

}
