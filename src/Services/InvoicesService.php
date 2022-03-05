<?php

namespace App\Services;

use App\Entity\Company;
use App\Entity\Debt;
use App\Entity\Invoice;
use App\Repository\CompanyRepository;
use App\Repository\DebtRepository;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class InvoicesService
{
    /**
     * @var EntityManagerInterface|null
     */
    private EntityManagerInterface|null $entityManager;

    /**
     * @var ValidatorInterface|null
     */
    private ValidatorInterface|null $validator;

    /**
     * @var InvoiceRepository|null
     */
    private InvoiceRepository|null $invoiceRepository;


    /**
     * @var DebtRepository|null
     */
    private DebtRepository|null $debtRepository;

    /**
     * /**
     * @var CompanyRepository|null
     */
    private CompanyRepository|null $companyRepository;

    /**
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     */
    public function __construct(EntityManagerInterface $em, ValidatorInterface $validator)
    {
        $this->validator = $validator;
        $this->entityManager = $em;
        $this->invoiceRepository = $em->getRepository(Invoice::class);
        $this->debtRepository = $em->getRepository(Debt::class);
        $this->companyRepository = $em->getRepository(Company::class);
    }

    /**
     * @param $data
     * @return Invoice|string
     * @throws \Doctrine\DBAL\Exception
     */
    public function createInvoice($data): Invoice|string
    {
        $debtor = $this->companyRepository->find($data["debtor_id"]);
        $creditor = $this->companyRepository->find($data["creditor_id"]);

        if (!$debtor) {
            return "Sorry wrong debtor_id!";
        }
        if (!$creditor) {
            return "Sorry wrong creditor_id!";
        }

        $companyDebtorLimit = $debtor->getDebtorLimit();
        if ($data["price"] > $companyDebtorLimit) {
            return "Sorry invoice price exceeds allowed company debtor limit!";
        }

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $debt = $this->debtRepository->findOneBy(["debtor" => $data["debtor_id"]]);
            if ($debt) {
                $updated = $this->debtRepository->update($data["debtor_id"], $data["price"], $companyDebtorLimit);
                if (!$updated == 0) {
                    throw new \Exception("Sorry invoice price exceeds the remaining of allowed company debtor limit!", 422);
                }
            } else {
                // make debtor_id unique in debt entity
                $debt = new Debt();
                $debt->setDebtor($debtor);
                $debt->setTotal($data["price"]);
                $this->entityManager->persist($debt);
            }
            $invoice = new Invoice();
            $invoice->setCreditor($creditor);
            $invoice->setDebtor($debtor);
            $invoice->setDocument($data["document"]);
            $invoice->setPrice($data["price"]);
            $invoice->setStatus(Invoice::PENDING);
            $this->entityManager->persist($invoice);

            $validationErrors = $this->validate($debt, $invoice);
            if ($validationErrors) {
                throw new \Exception($validationErrors);
            }
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();

            $filter = ["creditor" => $data["creditor_id"], "debtor" => $data["debtor_id"], "price" => $data["price"]];
            return $this->invoiceRepository->findOneBy($filter);
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->entityManager->clear();
            $message = "Ops, problem happened, please try again and report it back please if not solved";
            if ($e->getCode() == 422) {
                $message = $e->getMessage();
            }
            return $message;
        }
    }

    /**
     * @param $invoice
     * @return Invoice|string
     * @throws \Doctrine\DBAL\Exception
     */
    public function payInvoice($invoice): Invoice|string
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            //decrease debt by invoice amount
            $debt = $this->debtRepository->findOneBy(["debtor" => $invoice->getDebtor()]);
            $oldTotal = $debt->getTotal();
            $updated = $this->debtRepository->decrease($debt->getId(), $oldTotal, $invoice->getPrice());
            if (!$updated) {
                throw new \Exception("Error paying your invoice try Again for consistency!", 422);
            } else {
                $this->entityManager->persist($debt);
            }

            $invoice->setStatus(Invoice::PAID);
            $this->entityManager->persist($invoice);

            $validationErrors = $this->validate($debt, $invoice);
            if ($validationErrors) {
                throw new \Exception($validationErrors, 422);
            }

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            $this->entityManager->clear();
            $message = "Ops, problem happened, please try again and report it back please if not solved";
            if ($e->getCode() == 422) {
                $message = $e->getMessage();
            }
            return $message;
        }
        return $invoice;
    }

    private function validate($debt, $invoice): string
    {
        $errors = $this->validator->validate($debt);
        if (count($errors)) {
            return $errors->get(0)->getPropertyPath() . " " . $errors->get(0)->getMessage();
        }

        $errors = $this->validator->validate($invoice);
        if (count($errors)) {
            return $errors->get(0)->getPropertyPath() . " " . $errors->get(0)->getMessage();
        }
        return "";
    }
}
