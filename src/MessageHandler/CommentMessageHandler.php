<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;

final class CommentMessageHandler implements MessageHandlerInterface
{
    /** @var CommentRepository */
    private $commentRepository;

    /** @var SpamChecker */
    private $spamChecker;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var MessageBusInterface */
    private $messageBus;

    /** @var WorkflowInterface */
    private $workflow;

    /** @var null|LoggerInterface */
    private $logger;

    public function __construct(CommentRepository $commentRepository, SpamChecker $spamChecker, EntityManagerInterface $entityManager, MessageBusInterface $messageBus, WorkflowInterface $commentStateMachine, LoggerInterface $logger = null)
    {
        $this->commentRepository = $commentRepository;
        $this->spamChecker = $spamChecker;
        $this->entityManager = $entityManager;
        $this->messageBus = $messageBus;
        $this->workflow = $commentStateMachine;
        $this->logger = $logger;
    }

    public function __invoke(CommentMessage $commentMessage)
    {
        $comment = $this->commentRepository->find($commentMessage->getId());
        if ($comment === null) {
            return;
        }

        if ($this->workflow->can($comment, 'accept')) {
            $spamScore = $this->spamChecker->getSpamScore($comment, $commentMessage->getContext());
            $this->workflow->apply($comment, $this->resolveTransition($spamScore));
            $this->entityManager->flush();
            $this->messageBus->dispatch($commentMessage);
        } elseif ($this->workflow->can($comment, 'publish') || $this->workflow->can($comment, 'publish_ham')) {
            $this->workflow->apply($comment, $this->workflow->can($comment, 'publish') ? 'publish' : 'publish_ham');
            $this->entityManager->flush();
        } elseif ($this->logger !== null) {
            $this->logger->debug('Dropping comment message', ['comment' => $comment->getId(), 'state' => $comment->getState()]);
        }
    }

    private function resolveTransition(int $spamScore): string
    {
        if ($spamScore === SpamChecker::SCORE_BLATANT_SPAM) {
            return 'reject_spam';
        } elseif ($spamScore === SpamChecker::SCORE_MAYBE_SPAM) {
            return 'might_be_spam';
        } else {
            return 'accept';
        }
    }
}
