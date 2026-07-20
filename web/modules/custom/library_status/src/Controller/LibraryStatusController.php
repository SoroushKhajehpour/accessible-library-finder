<?php

declare(strict_types=1);

namespace Drupal\library_status\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\library_status\Service\StatusClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders the library service status page.
 */
final class LibraryStatusController extends ControllerBase {

  public function __construct(
    private readonly StatusClient $statusClient,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('library_status.status_client'),
    );
  }

  /**
   * Builds the status page.
   *
   * @return array
   *   A render array.
   */
  public function page(): array {
    $items = $this->statusClient->getStatuses();
    $hasError = $items === [];

    return [
      '#theme' => 'library_status',
      '#items' => $items,
      '#error' => $hasError,
      '#cache' => [
        'max-age' => 300,
      ],
    ];
  }

}
