<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Comment;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class CommentMessageHandler implements MessageHandlerInterface
{
    /** @var CommentRepository */
    private $commentRepository;

    /** @var SpamChecker */
    private $spamChecker;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(CommentRepository $commentRepository, SpamChecker $spamChecker, EntityManagerInterface $entityManager)
    {
        $this->commentRepository = $commentRepository;
        $this->spamChecker = $spamChecker;
        $this->entityManager = $entityManager;
    }

    public function __invoke(CommentMessage $commentMessage)
    {
        $comment = $this->commentRepository->find($commentMessage->getId());
        if ($comment === null) {
            return;
        }

        if ($this->spamChecker->getSpamScore($comment, $commentMessage->getContext()) === SpamChecker::SCORE_BLATANT_SPAM) {
            $comment->setState(Comment::STATE_SPAM);
        } else {

            $comment->setState(Comment::STATE_PUBLISHED);
            $comment->setState(Comment::STATE_SPAM);
        }

        $this->entityManager->flush();
    }
}
