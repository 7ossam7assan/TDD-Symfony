<?php

namespace App\Tests;

use App\DataFixtures\SettingsFixture;
use App\Entity\Company;
use App\Tests\DatabasePrimer;
use App\Services\CompanyService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CompanyServiceTest extends KernelTestCase
{

    /**
     * @var \Doctrine\Persistence\ObjectManager
     */
    private $entityManager;
    /**
     * @var \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    private $validator;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        DatabasePrimer::prime($kernel);
        $this->validator = $kernel->getContainer()->get('validator');
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $settings = new SettingsFixture();
        $settings->load($this->entityManager);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }

    /**
     * @test
     */
    public function service_can_save_in_db_and_return_company_object()
    {
        $companyService = new CompanyService($this->entityManager, $this->validator);
        $mockRequestData = [
            "name" => "Amazon",
            "swift_code" => "AAIBEG33XXX",
            "bank_name" => "Arabian African International Bank"
        ];
        $company = $companyService->createCompany($mockRequestData);
        $companyRepository = $this->entityManager->getRepository(Company::class);
        $companyRecord = $companyRepository->findOneBy(["swift_code" => "AAIBEG33XXX"]);
        $this->assertSame($company, $companyRecord);
    }

    /**
     * @test
     */
    public function service_not_valid_data_return_validation_error_string()
    {
        $requestData = [
            "name" => "Amazon",
            "bank_name" => "AAIB",
            "swift_code" => "AAIBEG33XXX"
        ];
        $companyService = new CompanyService($this->entityManager, $this->validator);
        $companyService->createCompany($requestData);

        //create again
        $error = $companyService->createCompany($requestData);

        // return string validation error
        $this->assertIsString($error);
    }
}
