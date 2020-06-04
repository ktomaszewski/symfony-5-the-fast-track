<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use function dirname;

final class ConferenceControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/en/');

        static::assertResponseIsSuccessful();
        static::assertSelectorTextContains('h2', 'Give your feedback');
    }

    public function testCommentSubmission(): void
    {
        $email = 'me@autmat.ed';

        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/en/conference/amsterdam-2019');
        $client->submitForm('Submit', [
            'comment_form[author]' => 'Fabien',
            'comment_form[text]'   => 'Some feedback from an automated functional test',
            'comment_form[email]'  => $email,
            'comment_form[photo]'  => dirname(__DIR__, 2) . 'public/images/under-construction.gif'
        ]);
        static::assertResponseRedirects();

        $commentRepository = self::$container->get(CommentRepository::class);
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $comment = $commentRepository->findOneByEmail($email);
        $comment->setState(Comment::STATE_PUBLISHED);
        $entityManager->flush();

        $client->followRedirect();
        static::assertSelectorExists('div:contains("There are 2 comments")');
    }

    public function testConferencePage(): void
    {
        $client = static::createClient();
        $crawler = $client->request(Request::METHOD_GET, '/en/');

        $this->assertCount(2, $crawler->filter('h4'));

        $client->clickLink('View');

        static::assertPageTitleContains('Amsterdam');
        static::assertResponseIsSuccessful();
        static::assertSelectorTextContains('h2', 'Amsterdam 2019');
        static::assertSelectorExists('div:contains("There is one comment")');
    }
}
