<?php

declare(strict_types=1);

namespace Drupal\bootstrap_simple_carousel\Service;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\file\FileStorageInterface;

/**
 * CarouselService Class.
 *
 * Provides functions for the module.
 *
 * @category Class
 * @package Drupal\bootstrap_simple_carousel\Service
 */
class CarouselService extends ServiceProviderBase {
  use StringTranslationTrait;
  /**
   * This will hold Renderer object.
   */
  protected RendererInterface $renderer;
  /**
   * This will hold File object.
   */
  protected FileStorageInterface $file;

  /**
   * CarouselService constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity manager interface.
   */
  public function __construct(RendererInterface $renderer, EntityTypeManagerInterface $entityTypeManager) {
    $this->renderer = $renderer;
    $this->file = $entityTypeManager->getStorage('file');
  }

  /**
   * Return a rendered image.
   *
   * @throws \Exception
   */
  public function renderImageById(int $image_id, string $image_style = 'thumbnail', array $params = []) {
    $image = '';
    $imageFile = $this->file->load($image_id);
    if (NULL !== $imageFile) {
      $imageTheme = [
        '#theme' => 'image_style',
        '#style_name' => $image_style,
        '#uri' => $imageFile->getFileUri(),
        '#alt' => $params['alt'] ?? '',
        '#title' => $params['title'] ?? '',
      ];
      $image = $this->renderer->render($imageTheme);
    }

    return $image;
  }

  /**
   * Return a Render Link.
   *
   * @throws \Exception
   */
  public function renderLink(Url $url, TranslatableMarkup $title, array $attributes = []) {
    $linkTheme = [
      '#type' => 'link',
      '#title' => $title,
      '#url' => $url,
      '#options' => ['attributes' => $attributes, 'html' => FALSE],
    ];

    return $this->renderer->render($linkTheme);
  }

  /**
   * Return the statuses.
   */
  public function getStatuses(): array {
    return [0 => $this->t('Inactive'), 1 => $this->t('Active')];
  }

}
