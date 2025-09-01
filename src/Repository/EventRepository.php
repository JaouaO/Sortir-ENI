<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function queryFilters(array $filters): array
    {
        $queryBuilder = $this->createQueryBuilder('event');

        // Search by event
        if (isset($filters['search'])) {
            $queryBuilder->andWhere('event.name like :search')
                ->setParameter('search', "%{$filters['search']}%");
        }

        // filter by site
        if (isset($filters['site'])) {
            $queryBuilder->andWhere('event.site = :site')
                ->setParameter('site', $filters['site']);
        }

        // filter by organizer
        if (isset($filters['organizer'])) {
            $queryBuilder->andWhere('event.organizer = :organizer')
                ->setParameter('organizer', $filters['organizer']);
        }

        // Filter on event where user is registered
        if (isset($filters['registered'])) {
            $queryBuilder->innerJoin(
                'event.registeredParticipants',
                'participant')
                ->andWhere('participant.id IN (:registered)')
                ->setParameter('registered', $filters['registered']);
        }

        // Filter on event where user is not registered
        if (isset($filters['notRegistered'])) {
            $userId = is_object($filters['notRegistered']) ? $filters['notRegistered']->getId() : $filters['notRegistered'];

            $subQuery = $this->getEntityManager()->createQueryBuilder()
                ->select('1')
                ->from('App\Entity\Event', 'e2')
                ->innerJoin('e2.registeredParticipants', 'p')
                ->where('e2.id = event.id')
                ->andWhere('p.id = :userId');

            $queryBuilder->andWhere(
                $queryBuilder->expr()->not(
                    $queryBuilder->expr()->exists(
                        $subQuery->getDQL()
                    )
                )
            )->setParameter('userId', $userId);
        }

        // Filter on range date
        if (isset($filters['dateStart'])) {
            $dateStart = $filters['dateStart'];
            if (!$dateStart instanceof \DateTimeInterface) {
                $dateStart = \DateTime::createFromFormat('Y-m-d', $dateStart);
            }
            if ($dateStart) {
                $dateStart->setTime(0, 0, 0);
                $queryBuilder
                    ->andWhere('event.startDateTime >= :dateStart')
                    ->setParameter('dateStart', $dateStart);
            }
        }

        if (isset($filters['dateEnd'])) {
            $dateEnd = $filters['dateEnd'];
            if (!($dateEnd instanceof \DateTimeInterface)) {
                $dateEnd = \DateTime::createFromFormat('Y-m-d', $dateEnd);
            }
            if ($dateEnd) {
                $dateEnd->setTime(0, 0, 0);
                $queryBuilder
                    ->andWhere('event.startDateTime <= :dateEnd')
                    ->setParameter('dateEnd', $dateEnd);
            }
        }

        // Filter on passed events
        if (isset($filters['outingPast'])) {
            $queryBuilder->andWhere('event.startDateTime < :currentDate')
                ->setParameter('currentDate', new \DateTime());
        }

        // Events + 1 month are not displayed
        $queryBuilder->andWhere('event.endDateTime > :maxDate')
            ->setParameter('maxDate', (new \DateTime())->modify('-1 month'));

        return $queryBuilder->getQuery()->getResult();



        //    /**
        //     * @return Event[] Returns an array of Event objects
        //     */
        //    public function findByExampleField($value): array
        //    {
        //        return $this->createQueryBuilder('e')
        //            ->andWhere('e.exampleField = :val')
        //            ->setParameter('val', $value)
        //            ->orderBy('e.id', 'ASC')
        //            ->setMaxResults(10)
        //            ->getQuery()
        //            ->getResult()
        //        ;
        //    }

        //    public function findOneBySomeField($value): ?Event
        //    {
        //        return $this->createQueryBuilder('e')
        //            ->andWhere('e.exampleField = :val')
        //            ->setParameter('val', $value)
        //            ->getQuery()
        //            ->getOneOrNullResult()
        //        ;
        //    }
    }

}

