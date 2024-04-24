<?php

declare(strict_types=1);

namespace Drupal\bootstrap_simple_carousel\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\ImageStyleStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 *
 * Provides a settings form.
 *
 * @package Drupal\bootstrap_simple_carousel\Form
 */
class SettingsForm extends ConfigFormBase {
  public const ORIGINAL_IMAGE_STYLE_ID = 'original';
  public const DEFAULT_IMAGE_TYPE_ID = 'img-default';
  public const FLUID_IMAGE_TYPE_ID = 'img-fluid';
  public const CIRCLE_IMAGE_TYPE_ID = 'img-circle';
  /**
   * Image style service.
   */
  protected ImageStyleStorageInterface $imageStyleService;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->imageStyleService = $entity_type_manager->getStorage('image_style');
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static($container->get('config.factory'), $container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'bootstrap_simple_carousel_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['bootstrap_simple_carousel.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('bootstrap_simple_carousel.settings');
    $form['interval'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Interval')->render(),
      '#description' => $this->t('The amount of time (ms, 1000ms=1s) to delay between automatically cycling an item.
        If 0, carousel does not cycle automatically. If empty, carousel cycles in default time: 5s.')->render(),
      '#default_value' => $config->get('interval'),
    ];
    $form['wrap'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Wrap')->render(),
      '#description' => $this->t('Whether the carousel should cycle continuously or have hard stops.')->render(),
      '#default_value' => $config->get('wrap'),
    ];
    $form['pause'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause on hover')->render(),
      '#description' => $this->t("If is checked, pauses the cycling of the carousel on mouseenter and resumes the
         cycling of the carousel on mouseleave. If is unchecked, hovering over the carousel won't pause it.")->render(),
      '#default_value' => $config->get('pause'),
    ];
    $form['indicators'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Indicators')->render(),
      '#description' => $this->t('Show carousel indicators')->render(),
      '#default_value' => $config->get('indicators'),
    ];
    $form['controls'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Controls')->render(),
      '#description' => $this->t('Show carousel arrows (next/prev).')->render(),
      '#default_value' => $config->get('controls'),
    ];
    $form['assets'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Assets')->render(),
      '#description' => $this->t("Includes bootstrap framework v4.0.0, don't check it, if you use the
        bootstrap theme, or the bootstrap framework are already included.")->render(),
      '#default_value' => $config->get('assets'),
    ];
    $form['image_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Bootstrap image type')->render(),
      '#description' => $this->t('Bootstrap image type for carousel items.')->render(),
      '#options' => $this->getImagesTypes(),
      '#default_value' => $config->get('image_type'),
    ];
    $form['image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image style'),
      '#description' => $this->t('Image style for carousel items. If you will be use the image styles for
         bootstrap items, you need to set up the same width for the "bootstrap carousel" container.')->render(),
      '#options' => $this->getImagesStyles(),
      '#default_value' => $config->get('image_style'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Return images styles.
   */
  protected function getImagesStyles(): array {
    $styles = $this->imageStyleService->loadMultiple();
    $options = [static::ORIGINAL_IMAGE_STYLE_ID => $this->t('Original image')->render()];
    foreach ($styles as $key => $value) {
      $options[$key] = $value->get('label');
    }

    return $options;
  }

  /**
   * Return bootstrap images types.
   */
  protected function getImagesTypes(): array {
    return [
      static::DEFAULT_IMAGE_TYPE_ID => $this->t('Image none')->render(),
      static::FLUID_IMAGE_TYPE_ID => $this->t('Image fluid')->render(),
      static::CIRCLE_IMAGE_TYPE_ID => $this->t('Image circle')->render(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->configFactory->getEditable('bootstrap_simple_carousel.settings')
      ->set('interval', $form_state->getValue('interval'))
      ->set('wrap', $form_state->getValue('wrap'))
      ->set('pause', $form_state->getValue('pause'))
      ->set('indicators', $form_state->getValue('indicators'))
      ->set('controls', $form_state->getValue('controls'))
      ->set('assets', $form_state->getValue('assets'))
      ->set('image_type', $form_state->getValue('image_type'))
      ->set('image_style', $form_state->getValue('image_style'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
