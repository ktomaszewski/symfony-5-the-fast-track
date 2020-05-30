<?php

declare(strict_types=1);

namespace App\Message;

final class CommentMessage
{
    /** @var int */
    private $id;

    /** @var mixed[] */
    private $context;

    public function __construct(int $id, array $context = [])
    {
        $this->id = $id;
        $this->context = $context;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return mixed[]
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
