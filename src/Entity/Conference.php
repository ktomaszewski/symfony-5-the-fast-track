<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\ConferenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\String\Slugger\SluggerInterface;
use function in_array;
use function sprintf;

/**
 * @ORM\Entity(repositoryClass=ConferenceRepository::class)
 * @UniqueEntity("slug")
 *
 * @ApiResource(
 *     collectionOperations={"get"={"normalization_context"={"groups"="conference:list"}}},
 *     itemOperations={"get"={"normalization_context"={"groups"="conference:item"}}},
 *     order={"year"="DESC", "city"="ASC"},
 *     paginationEnabled=false
 * )
 */
class Conference
{
    public const SLUG_EMPTY_VALUE = '-';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     *
     * @Groups({"conference:list", "conference:item"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @var string
     *
     * @Groups({"conference:list", "conference:item"})
     */
    private $city;

    /**
     * @ORM\Column(type="string", length=4)
     * @var string
     *
     * @Groups({"conference:list", "conference:item"})
     */
    private $year;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     *
     * @Groups({"conference:list", "conference:item"})
     */
    private $isInternational;

    /**
     * @ORM\OneToMany(targetEntity=Comment::class, mappedBy="conference", orphanRemoval=true)
     * @var Collection|Comment[]
     */
    private $comments;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @var string
     *
     * @Groups({"conference:list", "conference:item"})
     */
    private $slug;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getYear(): ?string
    {
        return $this->year;
    }

    public function setYear(string $year): self
    {
        $this->year = $year;

        return $this;
    }

    public function isInternational(): ?bool
    {
        return $this->isInternational;
    }

    public function setIsInternational(bool $isInternational): self
    {
        $this->isInternational = $isInternational;

        return $this;
    }

    /**
     * @return Collection|Comment[]
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setConference($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
            if ($comment->getConference() === $this) {
                $comment->setConference(null);
            }
        }

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s %s', $this->city, $this->year);
    }

    public function computeSlug(SluggerInterface $slugger): void
    {
        if (in_array($this->slug, [null, self::SLUG_EMPTY_VALUE], true)) {
            $this->slug = $slugger->slug($this->__toString())->lower()->toString();
        }
    }
}
