<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CommentRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=CommentRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class Comment
{
    public const STATE_SUBMITTED = 'submitted';
    public const STATE_PUBLISHED = 'published';
    public const STATE_SPAM = 'spam';
    public const STATE_REJECTED = 'rejected';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     * @var string
     */
    private $author;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    private $text;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Email()
     * @var string
     */
    private $email;

    /**
     * @ORM\Column(type="datetime")
     * @var DateTimeInterface
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity=Conference::class, inversedBy="comments")
     * @ORM\JoinColumn(nullable=false)
     * @var Conference
     */
    private $conference;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var null|string
     */
    private $photoFilename;

    /**
     * @ORM\Column(type="string", length=255, options={"default": Comment::STATE_SUBMITTED})
     * @var string
     */
    private $state = self::STATE_SUBMITTED;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getConference(): ?Conference
    {
        return $this->conference;
    }

    public function setConference(?Conference $conference): self
    {
        $this->conference = $conference;

        return $this;
    }

    public function getPhotoFilename(): ?string
    {
        return $this->photoFilename;
    }

    public function setPhotoFilename(?string $photoFilename): self
    {
        $this->photoFilename = $photoFilename;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function __toString(): string
    {
        return $this->email;
    }

    /**
     * @ORM\PrePersist()
     */
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new DateTime();
    }
}
