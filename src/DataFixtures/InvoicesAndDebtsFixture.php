<?php

namespace App\DataFixtures;

use App\Entity\Company;
use App\Entity\Debt;
use App\Entity\Invoice;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class InvoicesAndDebtsFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        if ($_ENV['APP_ENV'] == "test") {
            $companies = $manager->getRepository(Company::class)->findAll();

            $invoice = new Invoice();
            $invoice->setCreditor($companies[0]);
            $invoice->setDebtor($companies[1]);
            $invoice->setPrice($companies[1]->getDebtorLimit() - 1);
            $invoice->setDocument("invoice1.pdf");
            $invoice->setStatus(Invoice::PENDING);
            $manager->persist($invoice);

            //only add debt if invoice status is pending (to be paid)
            $debt = new Debt();
            $debt->setDebtor($companies[1]);
            $debt->setTotal($invoice->getPrice());
            $manager->persist($debt);

            $invoice = new Invoice();
            $invoice->setCreditor($companies[0]);
            $invoice->setDebtor($companies[1]);
            $invoice->setPrice(1);
            $invoice->setDocument("invoice1.pdf");
            $invoice->setStatus(Invoice::REJECTED);
            $manager->persist($invoice);
            $manager->flush();
        }
    }
}
