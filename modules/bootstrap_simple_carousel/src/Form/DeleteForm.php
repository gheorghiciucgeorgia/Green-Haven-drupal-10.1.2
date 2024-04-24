<?php

declare(strict_types=1);

namespace Drupal\bootstrap_simple_carousel\Form;

use Drupal\bootstrap_simple_carousel\CarouselItemStorage;
use Drupal\bootstrap_simple_carousel\Entity\CarouselItem;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DeleteForm.
 *
 * Delete item form.
 *
 * @package Drupal\bootstrap_simple_carousel\Form
 */
class DeleteForm extends ConfirmFormBase {
  use StringTranslationTrait;

  /**
   * Carousel Storage.
   */
  protected CarouselItemStorage $carouselItemStorage;
  /**
   * Entity Manager.
   */
  protected ?EntityTypeManagerInterface $entityTypeManager;
  /**
   * Carousel Entity.
   */
  protected CarouselItem $entity;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param ?EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(?EntityTypeManagerInterface $entityTypeManager) {
    $this->carouselItemStorage = $entityTypeManager->getStorage('bootstrap_simple_carousel');
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'bootstrap_simple_carousel_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to delete the item?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('bootstrap_simple_carousel.table');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL): array {
    $form = parent::buildForm($form, $form_state);
    if (is_numeric($id)) {
      $this->entity = $this->carouselItemStorage->load($id);
    }

    if (!is_null($this->entity)) {
      $form['cid'] = ['#type' => 'hidden', '#required' => FALSE, '#default_value' => $this->entity->id()];
      $form['image_id'] = ['#type' => 'hidden', '#required' => FALSE, '#default_value' => $this->entity->getImageId()];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $message = $this->t('Item has been removed!');
    if (!is_null($this->entity)) {
      try {
        $file = $this->entityTypeManager->getStorage('file')->load($form_state->getValue('image_id'));
        $file->setTemporary();
        $file->save();
        $this->entity->delete();
      }
      catch (\Exception $e) {
        $message = $this->t('Item was not removed!');
      }
    }

    $this->messenger()->addMessage($message);
    $form_state->setRedirect('bootstrap_simple_carousel.table');
  }

}
