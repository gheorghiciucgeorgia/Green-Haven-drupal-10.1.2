<?php

declare(strict_types=1);

namespace Drupal\bootstrap_simple_carousel\Form;

use Drupal\bootstrap_simple_carousel\CarouselItemStorage;
use Drupal\bootstrap_simple_carousel\Entity\CarouselItem;
use Drupal\bootstrap_simple_carousel\Service\CarouselService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EditForm.
 *
 * Add/edit form.
 *
 * @package Drupal\bootstrap_simple_carousel\Form
 */
class EditForm extends FormBase {
  /**
   * The database connection object.
   */
  protected CarouselItemStorage $carouselItemStorage;
  /**
   * The carousel service.
   */
  protected CarouselService $carouselService;
  /**
   * The entity type manager.
   */
  protected ?EntityTypeManagerInterface $entityTypeManager;
  /**
   * The entity.
   */
  protected CarouselItem $entity;
  /**
   * The logger.
   */
  protected LoggerChannelInterface $logger;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'bootstrap_simple_carousel_edit_form';
  }

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\bootstrap_simple_carousel\Service\CarouselService $carouselService
   *   The CarouselService.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Logger.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CarouselService $carouselService, LoggerChannelInterface $logger) {
    $this->carouselItemStorage = $entity_type_manager->getStorage('bootstrap_simple_carousel');
    $this->entityTypeManager = $entity_type_manager;
    $this->carouselService = $carouselService;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static($container->get('entity_type.manager'), $container->get('bootstrap_simple_carousel.carousel_service'), $container->get('logger.channel.bootstrap_simple_carousel'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL): array {
    $this->entity = $this->carouselItemStorage->create();
    if (is_numeric($id)) {
      $this->entity = $this->carouselItemStorage->load($id);
    }

    if (!$this->entity->isNew()) {
      $form['cid'] = ['#type' => 'hidden', '#required' => FALSE, '#default_value' => $this->entity->id()];
      $form['image_preview'] = [
        '#markup' => $this->carouselService->renderImageById($this->entity->getImageId()),
        '#suffix' => '<br><b>NOTE: You can\'t change image, just remove\\set inactive the item and create new one</b>',
      ];
    }
    else {
      $form['image_id'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Image'),
        '#upload_validators' => ['file_validate_extensions' => ['gif png jpg jpeg'], 'file_validate_size' => [25600000]],
        '#upload_location' => 'public://bootstrap_simple_carousel/',
        '#required' => !$this->entity->isNew(),
        '#default_value' => !$this->entity->isNew() ? $this->entity->getImageId() : '',
      ];
    }

    $form['image_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image title'),
      '#required' => FALSE,
      '#default_value' => !$this->entity->isNew() ? $this->entity->getImageTitle() : '',
    ];
    $form['image_alt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image alt'),
      '#required' => FALSE,
      '#default_value' => !$this->entity->isNew() ? $this->entity->getImageAlt() : '',
    ];
    $form['image_link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image link'),
      '#description' => $this->t('For instance: `https://example.com`, `node/1`, `about`'),
      '#required' => FALSE,
      '#default_value' => !$this->entity->isNew() ? $this->entity->getImageLink() : '',
    ];
    $form['caption_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Caption title'),
      '#required' => FALSE,
      '#default_value' => !$this->entity->isNew() ? $this->entity->getCaptionTitle() : '',
    ];
    $form['caption_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Caption text'),
      '#required' => FALSE,
      '#default_value' => !$this->entity->isNew() ? $this->entity->getCaptionText() : '',
    ];
    $form['status'] = [
      '#type' => 'select',
      '#title' => ('Status'),
      '#options' => $this->carouselService->getStatuses(),
      '#default_value' => !$this->entity->isNew() ? $this->entity->getStatus() : '',
    ];
    $form['weight'] = [
      '#type' => 'number',
      '#title' => ('Weight'),
      '#required' => FALSE,
      '#default_value' => !$this->entity->isNew() ? $this->entity->getWeight() : 0,
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = ['#type' => 'submit', '#value' => $this->t('Save'), '#button_type' => 'primary'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if (!in_array((int) $form_state->getValue('status'), [0, 1], TRUE)) {
      $form_state->setErrorByName('status', $this->t('Status is incorrect.'));
    }
    if ($form_state->getValue('weight') < 0) {
      $form_state->setErrorByName('status', $this->t('Weight must be zero or greater than zero.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if (!empty($form_state->getValue('image_id'))) {
      $file = $this->entityTypeManager->getStorage('file')->load(current($form_state->getValue('image_id')));
      $file->setPermanent();
      $file->save();
    }
    $form_state->cleanValues();
    foreach ($form_state->getValues() as $field => $value) {
      $this->entity->set($field, $value);
    }

    try {
      $messageStatus = MessengerInterface::TYPE_STATUS;
      $this->entity->save();
      $message = $this->t('Item successfully saved!');
    }
    catch (\Exception $e) {
      $message = $this->t('Record was not saved!') . $e->getMessage();
      $messageStatus = MessengerInterface::TYPE_ERROR;
      $this->logger->error(
        'Can not save carousel item: ' . $e->getMessage(),
        ['exception' => $e, 'data' => $form_state->getValues()]
      );
    }
    $this->messenger()->addMessage($message, $messageStatus);
    $form_state->setRedirect('bootstrap_simple_carousel.table');
  }

}
