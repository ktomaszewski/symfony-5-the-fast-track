<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Form\CommentFormType;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use function bin2hex;
use function count;
use function min;
use function random_bytes;
use function sprintf;

class ConferenceController extends AbstractController
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
     * @Route("/", name="homepage")
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function index(ConferenceRepository $conferenceRepository): Response
    {
        return (new Response($this->twig->render('conference/index.html.twig', [
            'conferences' => $conferenceRepository->findAll()
        ])))->setSharedMaxAge(3600);
    }

    /**
     * @Route("/conference/{slug}", name="conference")
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function show(Request $request, Conference $conference, CommentRepository $commentRepository, NotifierInterface $notifier, string $photoDirectoryPath): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $comment->setConference($conference);
                /** @var null|UploadedFile $photo */
                $photo = $form['photo']->getData();
                if ($photo !== null) {
                    $filename = sprintf('%s.%s', bin2hex(random_bytes(6)), $photo->guessExtension());
                    try {
                        $photo->move($photoDirectoryPath, $filename);
                    } catch (FileException $exception) {
                        // unable to upload the photo, give up
                    }
                    $comment->setPhotoFilename($filename);
                }

                $this->entityManager->persist($comment);
                $this->entityManager->flush();

                $context = [
                    'user_ip'    => $request->getClientIp(),
                    'user_agent' => $request->headers->get('user-agent'),
                    'referrer'   => $request->headers->get('referer'),
                    'permalink'  => $request->getUri()
                ];
                $reviewUrl = $this->generateUrl('review_comment', ['id' => $comment->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
                $this->messageBus->dispatch(new CommentMessage($comment->getId(), $reviewUrl, $context));
                $notifier->send(new Notification('Thank you for the feedback; your comment will be posted after moderation', ['browser']));

                return $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
            }

            $notifier->send(new Notification('Can you check your submission? There are some problems with it', ['browser']));
        }

        $offset = $request->query->getInt('offset', 0);
        $paginator = $commentRepository->getCommentPaginator($conference, $offset);

        return new Response($this->twig->render('conference/show.html.twig', [
            'conference'   => $conference,
            'comments'     => $paginator,
            'previous'     => $offset - CommentRepository::PAGINATOR_PER_PAGE,
            'next'         => min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE),
            'comment_form' => $form->createView()
        ]));
    }

    /**
     * @Route("/conference_header", name="conference_header")
     */
    public function conferenceHeader(ConferenceRepository $conferenceRepository): Response
    {
        return $this->render('conference/header.html.twig', [
            'conferences' => $conferenceRepository->findAll()
        ])->setSharedMaxAge(3600);
    }
}
