<?php

namespace App\Tests;

use App\DataFixtures\CompanyFixture;
use App\DataFixtures\SettingsFixture;
use App\Entity\Company;
use App\Entity\Debt;
use App\Entity\Invoice;
use App\Services\InvoicesService;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class InvoicesServiceTest extends KernelTestCase
{

    /**
     * @var EntityManager|null
     */
    private EntityManager|null $entityManager;
    /**
     * @var ValidatorInterface|null
     */
    private ValidatorInterface|null $validator;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        DatabasePrimer::prime($kernel);
        $this->validator = $kernel->getContainer()->get('validator');
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $settings = new SettingsFixture();
        $settings->load($this->entityManager);
        $companies = new CompanyFixture();
        $companies->load($this->entityManager);
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
    public function service_can_save_in_db_and_return_invoice_object()
    {
        $companies = $this->entityManager->getRepository(Company::class)->findAll();
        $mockRequestData = [
            "creditor_id" => $companies[0]->getId(),
            "debtor_id" => $companies[1]->getId(),
            "price" => $companies[1]->getDebtorLimit(),
            "document" => "invoices.pdf"
        ];
        $invoicesService = new InvoicesService($this->entityManager, $this->validator);
        $invoice = $invoicesService->createInvoice($mockRequestData);
        $invoiceRepository = $this->entityManager->getRepository(Invoice::class);
        $invoiceRecord = $invoiceRepository->findOneBy(["creditor" => $mockRequestData["creditor_id"], "debtor" => $mockRequestData["debtor_id"], "price" => $mockRequestData["price"]]);

        $this->assertSame($invoice, $invoiceRecord);
    }

    /**
     * @test
     */
    public function validate_wrong_debtor_id_or_creditor_id()
    {
        $companies = $this->entityManager->getRepository(Company::class)->findAll();

        $invoicesService = new InvoicesService($this->entityManager, $this->validator);
        $mockRequestData = [
            "creditor_id" => $companies[0]->getId(),
            "debtor_id" => -1,
            "price" => $companies[1]->getDebtorLimit(),
            "document" => "invoices.pdf"
        ];
        $error = $invoicesService->createInvoice($mockRequestData);
        $this->assertSame("Sorry wrong debtor_id!", $error);

        $mockRequestData = [
            "creditor_id" => -1,
            "debtor_id" => $companies[0]->getId(),
            "price" => $companies[1]->getDebtorLimit(),
            "document" => "invoices.pdf"
        ];
        $error = $invoicesService->createInvoice($mockRequestData);
        $this->assertSame("Sorry wrong creditor_id!", $error);
    }

    /**
     * @test
     */
    public function cumulative_invoices_prices_exceeds_debtor_limit_return_validation_error_message()
    {
        $this->entityManager->clear();
        $companies = $this->entityManager->getRepository(Company::class)->findAll();
        $mockRequestData = [
            "creditor_id" => $companies[0]->getId(),
            "debtor_id" => $companies[1]->getId(),
            "price" => $companies[1]->getDebtorLimit(),
            "document" => "invoice.pdf"
        ];
        $invoicesService = new InvoicesService($this->entityManager, $this->validator);
        $invoicesService->createInvoice($mockRequestData);
        //create again with total amount 1000000.00 + 1 = 1000001.00 > debtor limit 1000000.00
        $mockRequestData = [
            "creditor_id" => $companies[0]->getId(),
            "debtor_id" => $companies[1]->getId(),
            "price" => 1,
            "document" => "invoice.pdf"
        ];
        $error = $invoicesService->createInvoice($mockRequestData);
        $this->assertEquals("Sorry invoice price exceeds the remaining of allowed company debtor limit!", $error);
    }

    /**
     * @test
     */
    public function see_only_on_debt_for_company_after_many_invoices_adding()
    {
        $companies = $this->entityManager->getRepository(Company::class)->findAll();
        $mockRequestData = [
            "creditor_id" => $companies[0]->getId(),
            "debtor_id" => $companies[1]->getId(),
            "price" => $companies[1]->getDebtorLimit() - 1,
            "document" => "invoice.pdf"
        ];
        $invoicesService = new InvoicesService($this->entityManager, $this->validator);
        $invoicesService->createInvoice($mockRequestData);

        //create again with total amount 999999.00 + 1 = 1000000.00 <= debtor limit 1000000.00
        $mockRequestData2 = [
            "creditor_id" => $companies[0]->getId(),
            "debtor_id" => $companies[1]->getId(),
            "price" => 1,
            "document" => "invoice.pdf"
        ];
        $invoicesService->createInvoice($mockRequestData2);
        $debts = $this->entityManager->getRepository(Debt::class)->findBy(["debtor" => $companies[1]->getId()]);
        $this->assertEquals(1, count($debts));
    }

    /**
     * @test
     */
    public function invoice_price_exceed_debtor_limit_return_validation_error()
    {
        $companies = $this->entityManager->getRepository(Company::class)->findAll();
        $mockRequestData = [
            "creditor_id" => $companies[0]->getId(),
            "debtor_id" => $companies[1]->getId(),
            "price" => $companies[1]->getDebtorLimit() + 1,
            "document" => "invoice.pdf"
        ];
        $invoicesService = new InvoicesService($this->entityManager, $this->validator);
        $error = $invoicesService->createInvoice($mockRequestData);
        $this->assertEquals($error, "Sorry invoice price exceeds allowed company debtor limit!");
    }
}
