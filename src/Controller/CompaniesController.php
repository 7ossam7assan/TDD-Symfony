<?php

namespace App\Controller;

use App\Services\CompanyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route ("/api")
 */
class CompaniesController extends AbstractController
{
    /**
     * @var CompanyService
     */
    private CompanyService $companyService;

    /**
     * @param CompanyService $companyService
     */
    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }

    /**
     * @Route("/companies", name="add-company", methods={"POST"})
     * @return Response
     */
    public function store(Request $request): Response
    {
        $requestBody = $request->request->all();

        $requiredParameters = [
            'name' => 'name is required',
            'swift_code' => 'swift_code is required',
            'bank_name' => 'bank_name is required'
        ];
        $errors = $this->validateRequest($requiredParameters, $requestBody);
        if (count($errors) > 0) {
            return $this->json($errors, 422);
        }

        $company = $this->companyService->createCompany($requestBody);
        if (gettype($company) == "string") {
            return $this->json($this->errorResponse($company), 422);
        }

        return $this->json([
            "response" => [
                "message" => "Company Created",
                "data" => [$company]
            ]
        ], 200);
    }

    /**
     * @param $requiredParameters
     * @param $requestBody
     * @return array|array[]
     */
    private function validateRequest($requiredParameters, $requestBody): array
    {
        foreach ($requiredParameters as $parameter => $errorMessage) {
            if (!isset($requestBody[$parameter])) {
                return $this->errorResponse($errorMessage);
            }
        }
        return [];
    }

    /**
     * @param $errorMessage
     * @return array[]
     */
    private function errorResponse($errorMessage): array
    {
        return ["response" => ["message" => $errorMessage]];
    }
}
