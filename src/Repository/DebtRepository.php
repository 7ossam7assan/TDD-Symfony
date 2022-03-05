<?php

namespace App\Repository;

use App\Entity\Debt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Debt|null find($id, $lockMode = null, $lockVersion = null)
 * @method Debt|null findOneBy(array $criteria, array $orderBy = null)
 * @method Debt[]    findAll()
 * @method Debt[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DebtRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Debt::class);
    }

    public function update($debtorId, $invoicePrice, $debtorLimit): int
    {
        $queryBuilder = $this->createQueryBuilder("d");
        $queryBuilder = $queryBuilder->update("App:Debt", 'd')
            ->set("d.total", "d.total + :invoice_price")
            ->where('d.debtor = :debtor_id')
            ->andWhere('d.total + :invoice_price <= :debtor_limit')
            ->setParameter('debtor_id', $debtorId)
            ->setParameter('debtor_limit', $debtorLimit)
            ->setParameter('invoice_price', $invoicePrice)
            ->getQuery();
        return $queryBuilder->execute();
    }

    public function decrease($debtId, $oldTotal, $invoicePrice): int
    {
        $queryBuilder = $this->createQueryBuilder("d");
        $queryBuilder = $queryBuilder->update("App:Debt", 'd')
            ->set("d.total", "d.total - :invoice_price")
            ->where('d.id = :debt_id')
            ->andWhere('d.total = :old_total')
            ->setParameter('debt_id', $debtId)
            ->setParameter('old_total', $oldTotal)
            ->setParameter('invoice_price', $invoicePrice)
            ->getQuery();
        return $queryBuilder->execute();
    }
}
