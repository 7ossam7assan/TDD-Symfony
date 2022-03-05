<?php

namespace App\Tests\Feature;

use App\DataFixtures\SettingsFixture;
use App\Entity\Company;
use App\Services\CompanyService;
use App\Tests\DatabasePrimer;
use App\Controller\CompaniesController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * testing the company adding feature
 **/
class CompanyTest extends WebTestCase
{

    /**
     * @var \Symfony\Bundle\FrameworkBundle\KernelBrowser
     */
    private $client;

    /**
     * @var \Doctrine\Persistence\ObjectRepository
     */
    private $companyRepository;

    /**
     * @var \Doctrine\Persistence\ObjectManager
     */
    private $entityManager;

    /**
     * @var object|\Symfony\Component\Validator\Validator\ValidatorInterface|null
     */
    private $validator;


    protected function setUp(): void
    {
        $this->client = static::createClient();
        $kernel = self::bootKernel();
        DatabasePrimer::prime($kernel);
        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
        $this->validator = $this->getContainer()->get('validator');
        $this->companyRepository = $this->entityManager->getRepository(Company::class);
        $settings = new SettingsFixture();
        $settings->load($this->entityManager);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->client = null;
        $this->entityManager->close();
        $this->entityManager = null;
        $this->validator = null;
    }


    /**
     * @covers \App\Controller\CompaniesController
     * @test
     */
    public function missing_company_name_validation_error(): void
    {
        // given missing company name
        $requestData = ["swift_code" => "AAIBEG33XXX", "bank_name" => "AAIB"];

        // when calling create company
        $this->client->request("POST", "/api/companies", $requestData);

        //then see error code and message
        $response = $this->client->getResponse();
        $this->assertEquals(422, $response->getStatusCode());
        $responseBody = json_decode($response->getContent());
        $this->assertSame($responseBody->response->message, "name is required");
    }

    /**
     * @covers \App\Controller\CompaniesController
     * @test
     */
    public function missing_company_swift_code_validation_error(): void
    {
        // given missing swift code
        $requestData = ["name" => "Amazon", "bank_name" => "AAIB"];

        // when calling create company
        $this->client->request("POST", "/api/companies", $requestData);

        //then see error code and message
        $response = $this->client->getResponse();
        $this->assertEquals(422, $response->getStatusCode());
        $responseBody = json_decode($response->getContent());
        $this->assertSame($responseBody->response->message, "swift_code is required");
    }

    /**
     * @covers \App\Controller\CompaniesController
     * @test
     */
    public function unique_company_swift_code_validation_error(): void
    {
        //given create company with request data
        $requestData = [
            "name" => "Amazon",
            "bank_name" => "AAIB",
            "swift_code" => "AAIBEG33XXX"
        ];

        //when creating company with requestData
        $companyService = new CompanyService($this->entityManager, $this->validator);
        $companyService->createCompany($requestData);

        // and call create company api with th same requestData again
        $this->client->request("POST", "/api/companies", $requestData);
        $response = $this->client->getResponse();

        // then we get error message and code
        $this->assertEquals(422, $response->getStatusCode());
        $responseBody = json_decode($response->getContent());
        $this->assertSame($responseBody->response->message, "swift_code This value is already used.");
    }

    /**
     * @covers \App\Controller\CompaniesController
     * @test
     */
    public function missing_company_bank_name_validation_error(): void
    {
        //given missing bank name
        $requestData = ["name" => "Amazon", "swift_code" => "AAIBEG33XXX"];

        // when calling create company
        $this->client->request("POST", "/api/companies", $requestData);

        // then we get error message and code
        $response = $this->client->getResponse();
        $this->assertEquals(422, $response->getStatusCode());
        $responseBody = json_decode($response->getContent());
        $this->assertSame($responseBody->response->message, "bank_name is required");
    }

    /**
     * @covers \App\Controller\CompaniesController::store
     * @test
     */
    public function consumer_can_add_company(): void
    {

        //given valid requestData
        $requestData = [
            "name" => "Amazon",
            "swift_code" => "AAIBEG33XXX",
            "bank_name" => "AAIB"
        ];

        // when calling create company api
        $this->client->request("POST", "/api/companies", $requestData);

        // then we get json response
        $response = $this->client->getResponse();
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        // and 200 status code
        $this->assertEquals(200, $response->getStatusCode());

        // and convenient message status code
        $responseBody = json_decode($response->getContent());
        $this->assertSame($responseBody->response->message, "Company Created");

        // and the created company info
        $this->assertEquals(json_encode($responseBody->response->data), json_encode([$this->companyRepository->findOneBy(["name" => "Amazon"])]));
    }
}
