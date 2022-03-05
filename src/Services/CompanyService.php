<?php

namespace App\Services;

use App\Entity\Company;
use App\Entity\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CompanyService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     */
    public function __construct(EntityManagerInterface $em, ValidatorInterface $validator)
    {
        $this->validator = $validator;
        $this->entityManager = $em;
    }


    /**
     * @param $data
     * @return string|Company
     */
    public function createCompany($data): string|Company
    {
        $company = new Company();
        $company->setName($data["name"]);
        $company->setSWIFTCode($data["swift_code"]);
        $company->setBankName($data["bank_name"]);

        $settingsRepository = $this->entityManager->getRepository(Settings::class);
        $settingRecord = $settingsRepository->findOneBy(["setting" => "default_debtor_limit"]);
        $company->setDebtorLimit($settingRecord->getValue());
        $settingRecord = $settingsRepository->findOneBy(["setting" => "default_debtor_currency"]);
        $company->setDebtorCurrency($settingRecord->getValue());

        $errors = $this->validator->validate($company);
        if (count($errors)) {
            return $errors->get(0)->getPropertyPath() . " " . $errors->get(0)->getMessage();
        }

        $this->entityManager->persist($company);
        $this->entityManager->flush();
        $companyRepository = $this->entityManager->getRepository(Company::class);
        return $companyRepository->findOneBy(["swift_code" => $data["swift_code"]]);
    }
}
