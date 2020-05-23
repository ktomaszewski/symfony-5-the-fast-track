<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Conference;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use function count;
use function min;

class ConferenceController extends AbstractController
{
    /** @var Environment */
    private $twig;

    /** @var ConferenceRepository */
    private $conferenceRepository;

    public function __construct(Environment $twig, ConferenceRepository $conferenceRepository)
    {
        $this->twig = $twig;
        $this->conferenceRepository = $conferenceRepository;
    }

    /**
     * @Route("/", name="homepage")
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function index(): Response
    {
        return new Response($this->twig->render('conference/index.html.twig', [
            'conferences' => $this->conferenceRepository->findAll()
        ]));
    }

    /**
     * @Route("/conference/{id}", name="conference")
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function show(Request $request, Conference $conference, CommentRepository $commentRepository): Response
    {
        $offset = $request->query->getInt('offset', 0);
        $paginator = $commentRepository->getCommentPaginator($conference, $offset);

        return new Response($this->twig->render('conference/show.html.twig', [
            'conferences' => $this->conferenceRepository->findAll(),
            'conference'  => $conference,
            'comments'    => $paginator,
            'previous'    => $offset - CommentRepository::PAGINATOR_PER_PAGE,
            'next'        => min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE)
        ]));
    }
}
