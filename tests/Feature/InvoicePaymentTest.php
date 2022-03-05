<?php

namespace App\Tests\Feature;

use App\DataFixtures\CompanyFixture;
use App\DataFixtures\InvoicesAndDebtsFixture;
use App\DataFixtures\SettingsFixture;
use App\Entity\Invoice;
use App\Repository\InvoiceRepository;
use App\Tests\DatabasePrimer;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * testing the invoice payment feature
 **/
class InvoicePaymentTest extends WebTestCase
{

    /**
     * @var KernelBrowser|null
     */
    private KernelBrowser|null $client;

    /**
     * @var EntityManager|null
     */
    private EntityManager|null $entityManager;

    /**
     * @var InvoiceRepository|null
     */
    private InvoiceRepository|null $invoiceRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $kernel = self::bootKernel();
        DatabasePrimer::prime($kernel);

        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
        $this->invoiceRepository = $this->entityManager->getRepository(Invoice::class);

        $settings = new SettingsFixture();
        $settings->load($this->entityManager);

        $companies = new CompanyFixture();
        $companies->load($this->entityManager);

        $invoicesAndDebts = new InvoicesAndDebtsFixture();
        $invoicesAndDebts->load($this->entityManager);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->client = null;
        $this->entityManager->close();
        $this->entityManager = null;
        $this->invoiceRepository = null;
    }


    /**
     * @test
     */
    public function invoice_id_with_not_exist_validation_error(): void
    {
        // given wrong invoice id
        $wrongInvoiceId = -1;

        // when calling pay invoice
        $this->client->request("GET", "/api/invoices/$wrongInvoiceId/pay");

        //then see error code and message
        $response = $this->client->getResponse();
        $responseBody = json_decode($response->getContent());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertJson($response->getContent());
        $this->assertSame($responseBody->response->message, "Sorry not valid invoice to pay!");
        $this->assertSame(422, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function invoice_id_with_not_pending_status_validation_error(): void
    {
        // given wrong invoice id
        $wrongStatusInvoiceId = $this->invoiceRepository->findOneBy(["status" => Invoice::REJECTED])->getId();

        // when calling pay invoice
        $this->client->request("GET", "/api/invoices/$wrongStatusInvoiceId/pay");

        //then see error code and message
        $response = $this->client->getResponse();
        $responseBody = json_decode($response->getContent());
        $this->assertSame($responseBody->response->message, "Sorry not valid invoice to pay!");
        $this->assertSame(422, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function user_can_pay_valid_invoice(): void
    {
        // given valid invoice id
        $validInvoice = $this->invoiceRepository->findOneBy(["status" => Invoice::PENDING]);
        $validInvoiceId = $validInvoice->getId();

        // when calling pay invoice
        $this->client->request("GET", "/api/invoices/$validInvoiceId/pay");

        //then see success message and valid response format
        $response = $this->client->getResponse();
        $responseBody = json_decode($response->getContent());
        $this->entityManager->clear();
        $updatedInvoice = $this->invoiceRepository->find($validInvoiceId);
        $this->assertSame($responseBody->response->message, "Invoice Paid Successfully");
        $this->assertEquals(json_encode($responseBody->response->data), json_encode([$updatedInvoice]));
    }
}
