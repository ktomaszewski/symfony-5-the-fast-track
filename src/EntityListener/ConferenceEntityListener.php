<?php

declare(strict_types=1);

namespace App\EntityListener;

use App\Entity\Conference;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ConferenceEntityListener
{
    /** @var SluggerInterface */
    private $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function prePersist(Conference $conference, LifecycleEventArgs $lifecycleEventArgs): void
    {
        $conference->computeSlug($this->slugger);
    }

    public function preUpdate(Conference $conference, LifecycleEventArgs $lifecycleEventArgs): void
    {
        $conference->computeSlug($this->slugger);
    }
}
