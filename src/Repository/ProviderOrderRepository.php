<?php

namespace App\Repository;

use App\Entity\ProviderOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProviderOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProviderOrder::class);
    }

    public function save(ProviderOrder $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ProviderOrder $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findPendingOrders(): array
    {
        return $this->createQueryBuilder('po')
            ->where('po.status IN (:statuses)')
            ->setParameter('statuses', [ProviderOrder::STATUS_PENDING, ProviderOrder::STATUS_CONFIRMED])
            ->orderBy('po.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentOrders(int $limit = 10): array
    {
        return $this->createQueryBuilder('po')
            ->orderBy('po.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
