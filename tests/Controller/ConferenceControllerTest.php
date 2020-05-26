<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use function dirname;

final class ConferenceControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/');

        static::assertResponseIsSuccessful();
        static::assertSelectorTextContains('h2', 'Give your feedback');
    }

    public function testConferencePage(): void
    {
        $client = static::createClient();
        $crawler = $client->request(Request::METHOD_GET, '/');

        $this->assertCount(2, $crawler->filter('h4'));

        $client->clickLink('View');

        static::assertPageTitleContains('Amsterdam');
        static::assertResponseIsSuccessful();
        static::assertSelectorTextContains('h2', 'Amsterdam 2019');
        static::assertSelectorExists('div:contains("There are 1 comments")');
    }

    public function testCommentSubmission(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/conference/amsterdam-2019');
        $client->submitForm('Submit', [
            'comment_form[author]' => 'Fabien',
            'comment_form[text]'   => 'Some feedback from an automated functional test',
            'comment_form[email]'  => 'me@autmat.ed',
            'comment_form[photo]'  => dirname(__DIR__, 2) . 'public/images/under-construction.gif'
        ]);

        static::assertResponseRedirects();
        $client->followRedirect();
        static::assertSelectorExists('div:contains("There are 2 comments")');
    }
}
