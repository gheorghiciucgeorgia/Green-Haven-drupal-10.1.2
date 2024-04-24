<?php

declare(strict_types=1);

namespace Drupal\bootstrap_simple_carousel\Plugin\Block;

use Drupal\bootstrap_simple_carousel\CarouselItemStorage;
use Drupal\bootstrap_simple_carousel\Form\SettingsForm;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\file\FileStorageInterface;
use Drupal\image\ImageStyleStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Bootstrap simple carousel' Block.
 *
 * @Block(
 *   id = "bootstrap_simple_carousel_block",
 *   admin_label = @Translation("Bootstrap simple carousel block")
 * )
 */
class CarouselBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * This will hold ImmutableConfig object.
   */
  protected ImmutableConfig $moduleSettings;
  /**
   * Image style service.
   */
  protected ImageStyleStorageInterface $imageStyleStorage;
  /**
   * The database connection object.
   */
  protected CarouselItemStorage $carouselItemStorage;
  /**
   * The file storage interface.
   */
  protected FileStorageInterface $fileStorage;
  /**
   * The url generator.
   */
  protected FileUrlGeneratorInterface $urlGenerator;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleSettings = $config_factory->get('bootstrap_simple_carousel.settings');
    $this->carouselItemStorage = $entity_type_manager->getStorage('bootstrap_simple_carousel');
    $this->fileStorage = $entity_type_manager->getStorage('file');
    $this->imageStyleStorage = $entity_type_manager->getStorage('image_style');
    $this->urlGenerator = \Drupal::service('file_url_generator');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = [
      '#items' => $this->getCarouselItems(),
      '#settings' => $this->moduleSettings,
      '#theme' => 'bootstrap_simple_carousel_block',
      '#cache' => [
        'tags' => $this->moduleSettings->getCacheTags(),
      ],
    ];

    if ($this->moduleSettings->get('assets')) {
      $build['#attached'] = ['library' => ['bootstrap_simple_carousel/bootstrap']];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * Returns an active carousel items.
   */
  protected function getCarouselItems(): ?array {
    $items = $this->carouselItemStorage->getActiveItems();

    if (empty($items)) {
      return NULL;
    }

    foreach ($items as &$item) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->fileStorage->load($item->image_id);
      if (NULL === $file) {
        continue;
      }

      $item->image_url = $this->getImageUrl($file);
      $item->image_link = $this->getImageLink($item->image_link);
    }

    return $items;
  }

  /**
   * Generate url for image.
   */
  private function getImageUrl(FileInterface $file): string {
    $image_style = $this->moduleSettings->get('image_style');

    if (empty($image_style) || $image_style === SettingsForm::ORIGINAL_IMAGE_STYLE_ID) {
      return $this->urlGenerator->transformRelative($this->urlGenerator->generateAbsoluteString($file->getFileUri()));
    }

    return $this->urlGenerator->transformRelative(
      $this->imageStyleStorage->load($image_style)->buildUrl($file->getFileUri())
    );
  }

  /**
   * Generates url for image link.
   */
  private function getImageLink(?string $imageLink): ?Url {
    if (NULL === $imageLink) {
      return NULL;
    }

    $uri = parse_url($imageLink);

    if (empty($uri['host'])) {
      $imageLink = 'internal:/' . ltrim($imageLink, '/');
    }

    return Url::fromUri($imageLink);
  }

}
