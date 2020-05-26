<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

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
}
