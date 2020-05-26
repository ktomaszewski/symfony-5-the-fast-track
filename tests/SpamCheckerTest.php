<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Comment;
use App\SpamChecker;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SpamCheckerTest extends TestCase
{
    public function testSpamScoreWithInvalidRequest(): void
    {
        $comment = new Comment();
        $comment->setCreatedAtValue();
        $context = [];

        $clinet = new MockHttpClient([new MockResponse('invalid', ['response_headers' => ['x-akismet-debug-help: Invalid key']])]);
        $spamChecker = new SpamChecker($clinet, 'abcde');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to check for spam: invalid(Invalid key).');
        $spamChecker->getSpamScore($comment, $context);
    }

    /**
     * @dataProvider prepareComments
     */
    public function testSpamScore(int $expectedScore, ResponseInterface $response, Comment $comment, array $context): void
    {
        $client = new MockHttpClient([$response]);
        $spamChecker = new SpamChecker($client, 'abcde');

        $score = $spamChecker->getSpamScore($comment, $context);
        $this->assertSame($expectedScore, $score);
    }

    public function prepareComments(): iterable
    {
        $comment = new Comment();
        $comment->setCreatedAtValue();
        $context = [];

        $response = new MockResponse('', ['response_headers' => ['x-akismet-pro-tip: discard']]);
        yield 'blatant_spam' => [SpamChecker::SCORE_BLATANT_SPAM, $response, $comment, $context];

        $response = new MockResponse('true');
        yield 'spam' => [SpamChecker::SCORE_MAYBE_SPAM, $response, $comment, $context];

        $response = new MockResponse('false');
        yield 'ham' => [SpamChecker::SCORE_NOT_SPAM, $response, $comment, $context];
    }
}
