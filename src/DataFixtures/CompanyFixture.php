<?php

namespace App\DataFixtures;

use App\Entity\Company;
use App\Entity\Settings;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CompanyFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        if ($_ENV['APP_ENV'] == "test") {
            $defaultDebtorLimit = $manager->getRepository(Settings::class)->findOneBy(["setting" => "default_debtor_limit"])->getValue();
            $defaultDebtorCurrency = $manager->getRepository(Settings::class)->findOneBy(["setting" => "default_debtor_currency"])->getValue();

            $company = new Company();
            $company->setName("Amazon");
            $company->setBankName("Arabian African Bank");
            $company->setSWIFTCode("AAIB555XZX");
            $company->setDebtorLimit($defaultDebtorLimit);
            $company->setDebtorCurrency($defaultDebtorCurrency);
            $manager->persist($company);

            $company = new Company();
            $company->setName("OLX");
            $company->setBankName("QNB");
            $company->setSWIFTCode("QNB565XYX");
            $company->setDebtorLimit($defaultDebtorLimit);
            $company->setDebtorCurrency($defaultDebtorCurrency);
            $manager->persist($company);

            $manager->flush();
        }
    }
}
