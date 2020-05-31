<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Conference;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use function sprintf;

/**
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    private const DAYS_BEFORE_REJECTED_REMOVAL = 7;

    public const PAGINATOR_PER_PAGE = 2;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function getCommentPaginator(Conference $conference, int $offset): Paginator
    {
        return new Paginator($this
            ->createQueryBuilder('c')
            ->andWhere('c.conference = :conference')
            ->andWhere('c.state = :state')
            ->setParameter('conference', $conference)
            ->setParameter('state', Comment::STATE_PUBLISHED)
            ->orderBy('c.createdAt', Criteria::DESC)
            ->setMaxResults(self::PAGINATOR_PER_PAGE)
            ->setFirstResult($offset)
            ->getQuery()
        );
    }

    public function findOneByEmail(string $email): ?Comment
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function countOldRejected(): int
    {
        return $this->getOldRejectedQueryBuilder()
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function deleteOldRejected(): int
    {
        return $this->getOldRejectedQueryBuilder()
            ->delete()
            ->getQuery()
            ->execute();
    }

    private function getOldRejectedQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.state = :state_rejected OR c.state = :state_spam')
            ->andWhere('c.createdAt < :date')
            ->setParameters([
                'state_rejected' => Comment::STATE_REJECTED,
                'state_spam'     => Comment::STATE_SPAM,
                'date'           => new DateTime(sprintf('-%ddays', self::DAYS_BEFORE_REJECTED_REMOVAL))
            ]);
    }
}
