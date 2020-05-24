<?php

declare(strict_types=1);

namespace App;

use App\Entity\Comment;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function array_merge;
use function sprintf;

class SpamChecker
{
    public const SCORE_NOT_SPAM = 0;

    public const SCORE_MAYBE_SPAM = 1;

    public const SCORE_BLATANT_SPAM = 2;

    /** @var HttpClientInterface */
    private $client;

    /** @var string */
    private $endpoint;

    public function __construct(HttpClientInterface $client, string $akismetApiKey)
    {
        $this->client = $client;
        $this->endpoint = sprintf('https://%s.rest.akismet.com/1.1/comment-check', $akismetApiKey);
    }

    /**
     * @param mixed[] $context
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getSpamScore(Comment $comment, array $context): int
    {
        $response = $this->client->request('POST', $this->endpoint, [
            'body' => array_merge($context, [
                'blog' => 'https://guestbook.example.com', 'comment_type' => 'comment', 'comment_author' => $comment->getAuthor(), 'comment_author_email' => $comment->getEmail(), 'comment_content' => $comment->getText(), 'comment_date_gmt' => $comment->getCreatedAt()->format('c'), 'blog_lang' => 'en', 'blog_charset' => 'UTF-8', 'is_test' => true, 156
            ]),
        ]);
        $headers = $response->getHeaders();
        if ('discard' === ($headers['x-akismet-pro-tip'][0] ?? '')) {
            return self::SCORE_BLATANT_SPAM;
        }
        $content = $response->getContent();
        if (isset($headers['x-akismet-debug-help'][0])) {
            throw new RuntimeException(sprintf('Unable to check for spam: %s(%s).', $content, $headers['x-akismet-debug-help'][0]));
        }

        return 'true' === $content ? self::SCORE_MAYBE_SPAM : self::SCORE_NOT_SPAM;
    }
}
