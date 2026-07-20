<?php

declare(strict_types=1);

namespace Drupal\library_status\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Fetches and maps remote service status data.
 */
final class StatusClient {

  /**
   * Remote endpoint that provides sample status items.
   */
  private const ENDPOINT = 'https://jsonplaceholder.typicode.com/todos';

  /**
   * Number of status items to request.
   */
  private const ITEM_LIMIT = 5;

  /**
   * HTTP timeout in seconds.
   */
  private const TIMEOUT = 5.0;

  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Returns mapped status items, or an empty list on failure.
   *
   * @return array<int, array{name: string, available: bool}>
   *   Status items safe for display.
   */
  public function getStatuses(): array {
    try {
      $response = $this->httpClient->request('GET', self::ENDPOINT, [
        'query' => ['_limit' => self::ITEM_LIMIT],
        'timeout' => self::TIMEOUT,
        'http_errors' => TRUE,
      ]);

      $body = (string) $response->getBody();
      $data = json_decode($body, TRUE, 512, JSON_THROW_ON_ERROR);

      if (!is_array($data)) {
        $this->logger->error('Status API returned a non-array JSON payload.');
        return [];
      }

      $items = [];
      foreach ($data as $index => $item) {
        if (count($items) >= self::ITEM_LIMIT) {
          break;
        }

        if (!$this->isValidItem($item)) {
          $this->logger->warning('Skipping invalid status item at index @index.', [
            '@index' => (string) $index,
          ]);
          continue;
        }

        $items[] = [
          'name' => (string) $item['title'],
          // Treat completed todos as available services for this demo mapping.
          'available' => (bool) $item['completed'],
        ];
      }

      return $items;
    }
    catch (GuzzleException $exception) {
      $this->logger->error('Status API request failed: @message', [
        '@message' => $exception->getMessage(),
      ]);
      return [];
    }
    catch (\JsonException $exception) {
      $this->logger->error('Status API returned malformed JSON: @message', [
        '@message' => $exception->getMessage(),
      ]);
      return [];
    }
    catch (\Throwable $exception) {
      $this->logger->error('Unexpected status client failure: @message', [
        '@message' => $exception->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Validates one decoded API item before mapping.
   */
  private function isValidItem(mixed $item): bool {
    return is_array($item)
      && isset($item['title'], $item['completed'])
      && is_string($item['title'])
      && $item['title'] !== ''
      && is_bool($item['completed']);
  }

}
