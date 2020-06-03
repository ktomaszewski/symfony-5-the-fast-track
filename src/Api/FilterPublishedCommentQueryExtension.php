<?php

declare(strict_types=1);

namespace App\Api;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Comment;
use Doctrine\ORM\QueryBuilder;
use function current;
use function sprintf;

final class FilterPublishedCommentQueryExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        if ($resourceClass === Comment::class) {
            $queryBuilder->andWhere(sprintf("%s.state = '%s'", current($queryBuilder->getRootAliases()), Comment::STATE_PUBLISHED));
        }
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, string $operationName = null, array $context = [])
    {
        if ($resourceClass === Comment::class) {
            $queryBuilder->andWhere(sprintf("%s.state = '%s'", current($queryBuilder->getRootAliases()), Comment::STATE_PUBLISHED));
        }
    }
}
