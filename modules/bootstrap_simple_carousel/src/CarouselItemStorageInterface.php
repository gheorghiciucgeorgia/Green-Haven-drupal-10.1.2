<?php

declare(strict_types=1);

namespace Drupal\bootstrap_simple_carousel;

/**
 * Carousel item storage interface.
 */
interface CarouselItemStorageInterface {

  /**
   * Return all items.
   */
  public function getAllItems(): array;

  /**
   * Return active items.
   */
  public function getActiveItems(): array;

}
