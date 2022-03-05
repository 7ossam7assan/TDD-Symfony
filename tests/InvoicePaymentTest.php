<?php

namespace App\Tests;

use App\DataFixtures\CompanyFixture;
use App\DataFixtures\InvoicesAndDebtsFixture;
use App\DataFixtures\SettingsFixture;
use App\Entity\Debt;
use App\Entity\Invoice;
use App\Repository\DebtRepository;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InvoicePaymentTest extends KernelTestCase
{
    /**
     * @var EntityManager|null
     */
    private EntityManager|null $entityManager;

    /**
     * @var InvoiceRepository|null
     */
    private InvoiceRepository|null $invoiceRepository;

    /**
     * @var DebtRepository|null
     */
    private DebtRepository|null $debtRepository;


    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        DatabasePrimer::prime($kernel);
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->debtRepository = $this->entityManager->getRepository(Debt::class);
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
        $this->debtRepository = null;
        $this->invoiceRepository = null;
    }

    /**
     * @test
     */
    public function can_pay_invoice_and_subtract_debt_total(): Invoice|string
    {
        $invoice = $this->invoiceRepository->findOneBy(["status" => Invoice::PENDING]);
        $this->entityManager->getConnection()->beginTransaction();
        try {
            //update status
            $invoice->setStatus(Invoice::PAID);
            $this->entityManager->persist($invoice);

            //decrease debt amount
            $debt = $this->debtRepository->findOneBy(["debtor" => $invoice->getDebtor()]);
            $oldTotal = $debt->getTotal();

            //added check on  $oldTotal (simple optimistic locking)
            $updated = $this->debtRepository->decrease($debt->getId(), $oldTotal, $invoice->getPrice());
            if (!$updated) {
                throw new \Exception("Error paying your invoice try Again for consistency!", 422);
            } else {
                $this->entityManager->persist($debt);
            }
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->entityManager->clear();
            $message = "Ops, problem happened, please try again and report it back please if not solved";
            if ($e->getCode() == 422) {
                $this->assertSame("Error paying your invoice try Again for consistency!", $e->getMessage());
                $message = $e->getMessage();
            }
            return $message;
        }

        $this->entityManager->clear();
        $updatedDebt = $this->debtRepository->find($debt->getId());
        $this->assertEquals(Invoice::PAID, $invoice->getStatus());// validate status changed to paid
        $this->assertEquals($updatedDebt->getTotal(), $oldTotal - $invoice->getPrice());//validating that invoice price subtracted successfully
        return $invoice;
    }
}
