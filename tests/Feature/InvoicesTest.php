<?php

namespace App\Tests\Feature;

use App\DataFixtures\CompanyFixture;
use App\DataFixtures\InvoicesFilesFixture;
use App\DataFixtures\SettingsFixture;
use App\Entity\Company;
use App\Entity\Invoice;
use App\Repository\CompanyRepository;
use App\Tests\DatabasePrimer;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * testing the invoice creating feature
 **/
class InvoicesTest extends WebTestCase
{

    /**
     * @var KernelBrowser|null
     */
    private KernelBrowser|null $client;

    /**
     * @var CompanyRepository|null
     */
    private CompanyRepository|null $companyRepository;

    /**
     * @var EntityManager|null
     */
    private EntityManager|null $entityManager;

    /**
     * @var ValidatorInterface|null
     */
    private ValidatorInterface|null $validator;


    /**
     * @var File|null
     */
    private File|null $invoiceDocument;

    /**
     * @var File|null
     */
    private File|null $notValidInvoiceDocument;


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

        $companies = new CompanyFixture();
        $companies->load($this->entityManager);

        // to create files to test upload service
        $invoiceFixture = new InvoicesFilesFixture();
        $invoiceFixture->load($this->entityManager);
        $invoiceFilePath = $kernel->getProjectDir() . '/public/TestDocuments/InvoiceTemplate.pdf';
        $this->invoiceDocument = new UploadedFile($invoiceFilePath, "InvoiceTemplate.pdf");

        $notValidFilePath = $kernel->getProjectDir() . '/public/TestDocuments/InvoiceTemplate.wrong_format';
        $this->notValidInvoiceDocument = new UploadedFile($notValidFilePath, "InvoiceTemplate.wrong_format");
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->client = null;
        $this->entityManager->close();
        $this->entityManager = null;
        $this->validator = null;
        $this->companyRepository = null;
        $this->invoiceDocument = null;
        $this->notValidInvoiceDocument = null;
    }


    /**
     * @test
     */
    public function missing_creditor_id_validation_error(): void
    {
        $companies = $this->companyRepository->findAll();

        // given missing company name
        $requestData = ["debtor_id" => $companies[0]->getId(), "price" => $companies[1]->getDebtorLimit()];

        // when calling create company
        $this->client->request("POST", "/api/invoices", $requestData, ["document" => $this->invoiceDocument]);

        //then see error code and message
        $response = $this->client->getResponse();
        $this->assertEquals(422, $response->getStatusCode());
        $responseBody = json_decode($response->getContent());
        $this->assertSame($responseBody->response->message, "creditor id is required");
    }

    /**
     * @test
     */
    public function missing_debtor_id_validation_error(): void
    {
        $companies = $this->companyRepository->findAll();

        // given missing debtor id
        $requestData = ["creditor_id" => $companies[0]->getId(), "price" => $companies[1]->getDebtorLimit()];

        // when calling create invoice
        $this->client->request("POST", "/api/invoices", $requestData, ["document" => $this->invoiceDocument]);

        //then see error code and message
        $response = $this->client->getResponse();
        $this->assertEquals(422, $response->getStatusCode());
        $responseBody = json_decode($response->getContent());
        $this->assertSame($responseBody->response->message, "debtor id is required");
    }

    /**
     * @test
     */
    public function debtor_id_same_as_creditor_id_validation(): void
    {
        $companies = $this->companyRepository->findAll();

        // given debtor id same as creditor id
        $requestData = [
            "creditor_id" => $companies[0]->getId(),
            "debtor_id" => $companies[0]->getId(),
            "price" => 1000
        ];

        // when calling create invoice
        $this->client->request("POST", "/api/invoices", $requestData, ["document" => $this->invoiceDocument]);

        // then see error code and message
        $response = $this->client->getResponse();
        $this->assertEquals(422, $response->getStatusCode());
        $responseBody = json_decode($response->getContent());
        $this->assertSame($responseBody->response->message, "debtor and creditor can't be the same");
    }

    /**
     * @test
     */
    public function missing_invoice_price_validation_error(): void
    {
        $companies = $this->companyRepository->findAll();

        // given missing invoice price
        $requestData = [
            "creditor_id" => $companies[0]->getId(),
            "debtor_id" => $companies[1]->getId()
        ];

        // when calling create invoice
        $this->client->request("POST", "/api/invoices", $requestData, ["document" => $this->invoiceDocument]);

        // then see error code and message
        $response = $this->client->getResponse();
        $this->assertEquals(422, $response->getStatusCode());
        $responseBody = json_decode($response->getContent());
        $this->assertSame($responseBody->response->message, "price is required");
    }

    /**
     * @test
     */
    public function negative_or_zero_price_validation(): void
    {
        $companies = $this->companyRepository->findAll();
        // given negative price
        $requestData = [
            "creditor_id" => $companies[0]->getId(),
            "debtor_id" => $companies[1]->getId(),
            "price" => -1
        ];

        // when calling create invoice
        $this->client->request("POST", "/api/invoices", $requestData, ["document" => $this->invoiceDocument]);

        // then see error code and message
        $response = $this->client->getResponse();
        $this->assertEquals(422, $response->getStatusCode());
        $responseBody = json_decode($response->getContent());
        $this->assertSame($responseBody->response->message, "price must be greater than 0");
    }

    /**
     * @test
     */
    public function missing_invoice_document_validation(): void
    {
        $companies = $this->companyRepository->findAll();
        // given missing invoice document
        $requestData = [
            "creditor_id" => $companies[0]->getId(),
            "debtor_id" => $companies[1]->getId(),
            "price" => $companies[1]->getDebtorLimit()
        ];

        // when calling create invoice
        $this->client->request("POST", "/api/invoices", $requestData);

        // then see error code and message
        $response = $this->client->getResponse();
        $this->assertEquals(422, $response->getStatusCode());
        $responseBody = json_decode($response->getContent());
        $this->assertSame($responseBody->response->message, "invoice document is required");
    }

    /**
     * @test
     */
    public function wrong_mime_type_invoice_document_validation(): void
    {
        $companies = $this->companyRepository->findAll();
        // given wrong invoice document format
        $requestData = [
            "creditor_id" => $companies[0]->getId(),
            "debtor_id" => $companies[1]->getId(),
            "price" => $companies[1]->getDebtorLimit()
        ];

        // when calling create invoice
        $this->client->request("POST", "/api/invoices", $requestData, ["document" => $this->notValidInvoiceDocument]);

        // then see error code and message
        $response = $this->client->getResponse();
        $this->assertEquals(422, $response->getStatusCode());
        $responseBody = json_decode($response->getContent());
        $this->assertSame($responseBody->response->message, "Uploaded document has not valid file type (pdf,xls or csv)");
    }

    /**
     * @test
     */
    public function consumer_can_add_invoice(): void
    {
        $companies = $this->companyRepository->findAll();
        $invoiceRepository = $this->entityManager->getRepository(Invoice::class);
        //given valid requestData
        $requestData = [
            "creditor_id" => $companies[0]->getId(),
            "debtor_id" => $companies[1]->getId(),
            "price" => $companies[1]->getDebtorLimit()
        ];

        // when calling create invoice api
        $this->client->request("POST", "/api/invoices", $requestData, ["document" => $this->invoiceDocument]);

        // then we get json response
        $response = $this->client->getResponse();
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());

        // and 200 status code
        $this->assertEquals(200, $response->getStatusCode());

        // and convenient message status code
        $responseBody = json_decode($response->getContent());
        $this->assertSame($responseBody->response->message, "Invoice Created");

        $invoiceRecord = $invoiceRepository->findOneBy(
            [
                "creditor" => $companies[0]->getId(),
                "debtor" => $companies[1]->getId(),
                "price" => $companies[1]->getDebtorLimit()
            ]
        );
        // and the created invoice info
        $this->assertEquals(json_encode($responseBody->response->data), json_encode([$invoiceRecord]));

        //remove uploaded test document
        $documentName = $invoiceRecord->getDocument();
        $fileSystem = new Filesystem();
        $fileSystem->remove("public/uploads/invoices/$documentName");
    }
}
