<?php

declare(strict_types=1);

namespace Drupal\bootstrap_simple_carousel\Form;

use Drupal\bootstrap_simple_carousel\CarouselItemStorage;
use Drupal\bootstrap_simple_carousel\Service\CarouselService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ItemsForm.
 *
 * Table item list form.
 *
 * @package Drupal\bootstrap_simple_carousel\Form
 */
class ItemsForm extends FormBase {
  /**
   * The database connection object.
   */
  protected CarouselItemStorage $carouselItemStorage;
  /**
   * The catousel service.
   */
  protected CarouselService $carouselService;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'bootstrap_simple_carousel_items_form';
  }

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\bootstrap_simple_carousel\Service\CarouselService $carouselService
   *   The CarouselService.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CarouselService $carouselService) {
    $this->carouselItemStorage = $entity_type_manager->getStorage('bootstrap_simple_carousel');
    $this->carouselService = $carouselService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static($container->get('entity_type.manager'), $container->get('bootstrap_simple_carousel.carousel_service'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $items = $this->carouselItemStorage->getAllItems();
    $header = [
      'cio' => $this->t('Item id'),
      'image_id' => $this->t('Image'),
      'caption_title' => $this->t('Caption title'),
      'caption_text' => $this->t('Caption text'),
      'status' => $this->t('Status'),
      'weight' => $this->t('Weight'),
      'edit' => $this->t('Edit'),
      'delete' => $this->t('Delete'),
    ];
    $output = [];
    if (!empty($items)) {
      foreach ($items as $item) {
        $imageParams = ['alt' => $item->image_alt, 'title' => $item->image_title];
        $output[$item->cid] = [
          'cio' => $item->cid,
          'image_id' => $this->carouselService->renderImageById((int) $item->image_id, 'thumbnail', $imageParams),
          'caption_title' => $item->caption_title,
          'caption_text' => $item->caption_text,
          'status' => $this->carouselService->getStatuses()[(bool) $item->status],
          'weight' => $item->weight,
          'edit' => $this->carouselService->renderLink(
            Url::fromRoute('bootstrap_simple_carousel.edit', ['id' => $item->cid]),
            $this->t('edit')
          ),
          'delete' => $this->carouselService->renderLink(
            Url::fromRoute('bootstrap_simple_carousel.delete', ['id' => $item->cid]),
            $this->t('delete')
          ),
        ];
      }
    }

    $form['toolbar'] = [
      '#type' => 'fieldset',
      '#title' => '',
    ];
    $form['toolbar']['add_link'] = [
      '#title' => $this->t('Add element'),
      '#type' => 'link',
      '#url' => Url::fromRoute('bootstrap_simple_carousel.add'),
      '#attributes' => ['class' => ['button button-action button--primary button--small']],
    ];
    $form['toolbar']['settings_link'] = [
      '#title' => $this->t('Settings'),
      '#type' => 'link',
      '#url' => Url::fromRoute('bootstrap_simple_carousel.admin_settings'),
      '#attributes' => ['class' => ['button button--primary button--small']],
    ];
    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $output,
      '#empty' => $this->t('No images found'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
