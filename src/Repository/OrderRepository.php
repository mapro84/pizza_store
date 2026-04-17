<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findByCustomer(int $customerId): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.customer = :customerId')
            ->setParameter('customerId', $customerId)
            ->orderBy('o.orderedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByEmail(string $email): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.customerEmail = :email')
            ->setParameter('email', $email)
            ->orderBy('o.orderedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
