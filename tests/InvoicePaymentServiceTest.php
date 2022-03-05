<?php

namespace App\Tests;

use App\DataFixtures\CompanyFixture;
use App\DataFixtures\InvoicesAndDebtsFixture;
use App\DataFixtures\SettingsFixture;
use App\Entity\Invoice;
use App\Repository\InvoiceRepository;
use App\Services\InvoicesService;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class InvoicePaymentServiceTest extends KernelTestCase
{

    /**
     * @var EntityManager|null
     */
    private EntityManager|null $entityManager;
    /**
     * @var ValidatorInterface
     */
    private ValidatorInterface $validator;

    /**
     * @var InvoiceRepository|null
     */
    private InvoiceRepository|null $invoiceRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        DatabasePrimer::prime($kernel);
        $this->validator = $kernel->getContainer()->get('validator');
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
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
        $this->entityManager->close();
        $this->entityManager = null;
        $this->invoiceRepository = null;
    }

    /**
     * @test
     */
    public function service_can_update_invoice_and_debt_and_return_object()
    {
        $unpaidInvoice = $this->invoiceRepository->findOneBy(["status" => Invoice::PENDING]);
        $invoicesService = new InvoicesService($this->entityManager, $this->validator);
        $invoice = $invoicesService->payInvoice($unpaidInvoice);
        $this->assertSame(Invoice::PAID, $invoice->getStatus());
    }
}
