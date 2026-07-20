<?php

declare(strict_types=1);

namespace Drupal\Tests\library_status\Unit;

use Drupal\library_status\Service\StatusClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for StatusClient with mocked HTTP and logging.
 *
 * @coversDefaultClass \Drupal\library_status\Service\StatusClient
 * @group library_status
 */
final class StatusClientTest extends TestCase {

  /**
   * @covers ::getStatuses
   */
  public function testSuccessfulJsonResponseIsMapped(): void {
    $payload = [
      [
        'userId' => 1,
        'id' => 1,
        'title' => 'Catalogue search',
        'completed' => TRUE,
      ],
      [
        'userId' => 1,
        'id' => 2,
        'title' => 'Interlibrary loan',
        'completed' => FALSE,
      ],
    ];

    $httpClient = $this->createMock(ClientInterface::class);
    $httpClient->expects($this->once())
      ->method('request')
      ->willReturn(new Response(200, [], json_encode($payload, JSON_THROW_ON_ERROR)));

    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->never())->method('error');

    $client = new StatusClient($httpClient, $logger);
    $result = $client->getStatuses();

    $this->assertSame([
      [
        'name' => 'Catalogue search',
        'available' => TRUE,
      ],
      [
        'name' => 'Interlibrary loan',
        'available' => FALSE,
      ],
    ], $result);
  }

  /**
   * @covers ::getStatuses
   */
  public function testRequestExceptionReturnsEmptyListAndLogs(): void {
    $httpClient = $this->createMock(ClientInterface::class);
    $httpClient->expects($this->once())
      ->method('request')
      ->willThrowException(new RequestException(
        'Connection timed out',
        new Request('GET', 'https://example.test/todos'),
      ));

    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Status API request failed'),
        $this->callback(static function (array $context): bool {
          return isset($context['@message'])
            && str_contains((string) $context['@message'], 'Connection timed out');
        }),
      );

    $client = new StatusClient($httpClient, $logger);
    $this->assertSame([], $client->getStatuses());
  }

  /**
   * @covers ::getStatuses
   */
  public function testMalformedJsonReturnsEmptyListAndLogs(): void {
    $httpClient = $this->createMock(ClientInterface::class);
    $httpClient->expects($this->once())
      ->method('request')
      ->willReturn(new Response(200, [], '{not-valid-json'));

    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('malformed JSON'),
        $this->arrayHasKey('@message'),
      );

    $client = new StatusClient($httpClient, $logger);
    $this->assertSame([], $client->getStatuses());
  }

  /**
   * @covers ::getStatuses
   */
  public function testNonArrayJsonReturnsEmptyListAndLogs(): void {
    $httpClient = $this->createMock(ClientInterface::class);
    $httpClient->expects($this->once())
      ->method('request')
      ->willReturn(new Response(200, [], '"just-a-string"'));

    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())
      ->method('error')
      ->with('Status API returned a non-array JSON payload.');

    $client = new StatusClient($httpClient, $logger);
    $this->assertSame([], $client->getStatuses());
  }

  /**
   * @covers ::getStatuses
   */
  public function testInvalidItemsAreSkippedSafely(): void {
    $payload = [
      ['title' => 'Valid service', 'completed' => TRUE],
      ['title' => '', 'completed' => TRUE],
      ['completed' => FALSE],
      'not-an-array',
      ['title' => 'Another valid', 'completed' => FALSE],
    ];

    $httpClient = $this->createMock(ClientInterface::class);
    $httpClient->method('request')
      ->willReturn(new Response(200, [], json_encode($payload, JSON_THROW_ON_ERROR)));

    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->atLeastOnce())->method('warning');

    $client = new StatusClient($httpClient, $logger);
    $this->assertSame([
      [
        'name' => 'Valid service',
        'available' => TRUE,
      ],
      [
        'name' => 'Another valid',
        'available' => FALSE,
      ],
    ], $client->getStatuses());
  }

}
