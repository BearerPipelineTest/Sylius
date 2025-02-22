<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\Bundle\AdminBundle\Tests\Controller;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use Http\Message\MessageFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophecy\ProphecyInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Sylius\Bundle\AdminBundle\Controller\NotificationController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class NotificationControllerTest extends TestCase
{
    private ObjectProphecy $client;

    private ObjectProphecy $messageFactory;

    private NotificationController $controller;

    private static string $hubUri = 'www.doesnotexist.test.com';

    /**
     * @test
     */
    public function it_returns_an_empty_json_response_upon_client_exception(): void
    {
        $this->messageFactory->createRequest(Argument::any(), Argument::cetera())
            ->willReturn($this->prophesize(RequestInterface::class)->reveal())
        ;

        $this->client->send(Argument::cetera())->willThrow(ConnectException::class);

        $emptyResponse = $this->controller->getVersionAction(new Request());

        $this->assertEquals(JsonResponse::HTTP_NO_CONTENT, $emptyResponse->getStatusCode());
        $this->assertEquals('""', $emptyResponse->getContent());
    }

    /**
     * @test
     */
    public function it_returns_json_response_from_client_on_success(): void
    {
        $content = json_encode(['version' => '9001']);

        $this->messageFactory->createRequest(Argument::any(), Argument::cetera())
            ->willReturn($this->prophesize(RequestInterface::class)->reveal())
        ;

        /** @var ProphecyInterface|StreamInterface $stream */
        $stream = $this->prophesize(StreamInterface::class);
        $stream->getContents()->willReturn($content);

        /** @var ProphecyInterface|ResponseInterface $externalResponse */
        $externalResponse = $this->prophesize(ResponseInterface::class);
        $externalResponse->getBody()->willReturn($stream->reveal());

        $this->client->send(Argument::cetera())->willReturn($externalResponse->reveal());

        $response = $this->controller->getVersionAction(new Request());

        $this->assertEquals(JsonResponse::HTTP_OK, $response->getStatusCode());
        $this->assertEquals($content, $response->getContent());
    }

    protected function setUp(): void
    {
        $this->client = $this->prophesize(ClientInterface::class);
        $this->messageFactory = $this->prophesize(MessageFactory::class);

        $this->controller = new NotificationController(
            $this->client->reveal(),
            $this->messageFactory->reveal(),
            self::$hubUri,
            'environment',
        );

        parent::setUp();
    }
}
