<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Comment;
use App\Message\CommentMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\Registry;
use Twig\Environment;
use function sprintf;

/**
 * @Route("/admin")
 */
final class AdminController extends AbstractController
{
    /** @var Environment */
    private $twig;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var MessageBusInterface */
    private $messageBus;

    public function __construct(Environment $twig, EntityManagerInterface $entityManager, MessageBusInterface $messageBus)
    {
        $this->twig = $twig;
        $this->entityManager = $entityManager;
        $this->messageBus = $messageBus;
    }

    /**
     * @Route("/comment/review/{id}", name="review_comment")
     */
    public function reviewComment(Request $request, Comment $comment, Registry $registry): Response
    {
        $accepted = !$request->query->getBoolean('reject');

        $machine = $registry->get($comment);
        if ($machine->can($comment, 'publish')) {
            $transition = $accepted ? 'publish' : 'reject';
        } elseif ($machine->can($comment, 'publish_ham')) {
            $transition = $accepted ? 'publish_ham' : 'reject_ham';
        } else {
            return new Response('Comment already reviewed or not in the right state');
        }

        $machine->apply($comment, $transition);
        $this->entityManager->flush();

        if ($accepted) {
            $this->messageBus->dispatch(new CommentMessage($comment->getId()));
        }

        return $this->render('admin/review.html.twig', [
            'transition' => $transition,
            'comment'    => $comment
        ]);
    }

    /**
     * @Route("/http-cache/{uri<.*>}", methods={"PURGE"})
     */
    public function purgeHttpCache(KernelInterface $kernel, Request $request, string $uri): Response
    {
        if ($kernel->getEnvironment() === 'prod') {
            return new Response('KO', Response::HTTP_BAD_REQUEST);
        }

        $store = (new class($kernel) extends HttpCache {
        })->getStore();
        $store->purge(sprintf('%s/%s', $request->getSchemeAndHttpHost(), $uri));

        return new Response('Done');
    }
}
