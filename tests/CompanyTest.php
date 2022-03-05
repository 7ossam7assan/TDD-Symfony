<?php

namespace App\Tests;

use App\DataFixtures\SettingsFixture;
use App\Entity\Company;
use App\Entity\Settings;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CompanyTest extends KernelTestCase
{
    /**
     * @var \Doctrine\Persistence\ObjectManager
     */
    private $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        DatabasePrimer::prime($kernel);
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $settings = new SettingsFixture();
        $settings->load($this->entityManager);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->validator = null;
        $this->entityManager->close();
        $this->entityManager = null;
    }

    /**
     * @test
     */
    public function can_persist_company(): void
    {
        $company = new Company();
        $company->setName("Amazon");
        $company->setSWIFTCode("AAIBEG33XXX");
        $company->setBankName("Arabian african international bank");

        $settingsRepository = $this->entityManager->getRepository(Settings::class);

        $settingRecordLimit = $settingsRepository->findOneBy(["setting" => "default_debtor_limit"]);
        $company->setDebtorLimit($settingRecordLimit->getValue());

        $settingRecordCurrency = $settingsRepository->findOneBy(["setting" => "default_debtor_currency"]);
        $company->setDebtorCurrency($settingRecordCurrency->getValue());

        $this->entityManager->persist($company);
        $this->entityManager->flush();


        $companyRepository = $this->entityManager->getRepository(Company::class);
        $companyRecord = $companyRepository->findOneBy(["swift_code" => "AAIBEG33XXX"]);

        $this->assertEquals("Amazon", $companyRecord->getName());
        $this->assertEquals("AAIBEG33XXX", $companyRecord->getSWIFTCode());
        $this->assertEquals("Arabian african international bank", $companyRecord->getBankName());
        $this->assertEquals($settingRecordLimit->getValue(), $companyRecord->getDebtorLimit());
        $this->assertEquals($settingRecordCurrency->getValue(), $companyRecord->getDebtorCurrency());
    }
}
