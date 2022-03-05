<?php

namespace App\Tests;

use App\DataFixtures\CompanyFixture;
use App\DataFixtures\SettingsFixture;
use App\Entity\Company;
use App\Entity\Debt;
use App\Entity\Invoice;
use App\Repository\CompanyRepository;
use App\Repository\DebtRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InvoiceTest extends KernelTestCase
{
    /**
     * @var EntityManager|null
     */
    private EntityManager|null $entityManager;

    /**
     * @var CompanyRepository|null
     */
    private CompanyRepository|null $companyRepository;

    /**
     * @var DebtRepository|null
     */
    private DebtRepository|null $debtRepository;

    /**
     * @var Company|null
     */
    private Company|null $creditor;

    /**
     * @var Company|null Company|null
     */
    private Company|null $debtor;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        DatabasePrimer::prime($kernel);
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $settings = new SettingsFixture();
        $settings->load($this->entityManager);

        $companies = new CompanyFixture();
        $companies->load($this->entityManager);

        $this->companyRepository = $this->entityManager->getRepository(Company::class);
        $this->debtRepository = $this->entityManager->getRepository(Debt::class);
        $this->creditor = $this->companyRepository->findOneBy(["swift_code" => "AAIB555XZX"]);
        $this->debtor = $this->companyRepository->findOneBy(["swift_code" => "QNB565XYX"]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
        $this->companyRepository = null;
        $this->debtRepository = null;
        $this->creditor = null;
        $this->debtor = null;
    }

    /**
     * @test
     */
    public function can_persist_invoice(): void
    {
        $invoiceValue = 100;
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $debt = $this->debtRepository->findOneBy(["debtor" => $this->debtor->getId()]);
            if ($debt) {
                $updated = $this->debtRepository->update($this->debtor->getId(), $debt->getTotal() + $invoiceValue, $this->debtor->getDebtorLimit());
                if (!empty($updated)) {
                    $this->entityManager->persist($debt);
                } else {
                    throw new \Exception("Sorry can't add invoice the limit exceeded on this company!", 422);
                }
            } else {
                // make debtor_id unique in debt entity
                $debt = new Debt();
                $debt->setDebtor($this->debtor);
                $debt->setTotal($invoiceValue);
                $this->entityManager->persist($debt);
            }
            $invoice = new Invoice();
            $invoice->setCreditor($this->creditor);
            $invoice->setDebtor($this->debtor);
            $invoice->setDocument("invoice.pdf");
            $invoice->setPrice($this->debtor->getDebtorLimit());
            $invoice->setStatus(Invoice::PENDING);
            $this->entityManager->persist($invoice);

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->entityManager->clear();
        }
        $this->assertEquals($invoice->getDebtor(), $this->debtor);
        $this->assertEquals($invoice->getCreditor(), $this->creditor);
    }
}
