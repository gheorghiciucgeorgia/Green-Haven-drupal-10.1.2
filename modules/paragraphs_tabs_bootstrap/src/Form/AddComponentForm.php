<?php

namespace Drupal\paragraphs_tabs_bootstrap\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field_group\FormatterHelper;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphsTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AddComponentForm.
 *
 * Builds the form for add a new paragraph.
 */
class AddComponentForm extends FormBase {

  /**
   * The paragraphs generate.
   *
   * @var \Drupal\paragraphs\ParagraphInterface
   */
  protected $paragraph;

  /**
   * The paragraph type.
   *
   * @var \Drupal\paragraphs\Entity\ParagraphsType
   */
  protected $paragraphType;

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'paragraphs_add_form';
  }

  /**
   * {@inheritDoc}
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager, protected EntityRepositoryInterface $entityRepository, protected ModuleHandlerInterface $moduleHandler) {
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('module_handler'),
    );
  }

  /**
   * {@inheritDoc}
   *
   * @param array $form
   *   The form arrays.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param \Drupal\paragraphs\Entity\ParagraphsType $paragraph_type
   *   The paragraph types.
   * @param string $entity_type
   *   Entity Type.
   * @param string $entity_field
   *   Entity Field store paragraphs.
   * @param int $entity_id
   *   Entity Id.
   *
   * @return array
   *   FormBuilder format.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildForm(array $form, FormStateInterface $form_state, ParagraphsType $paragraph_type = NULL, $entity_type = NULL, $entity_field = NULL, $entity_id = 0) {
    $this->paragraph = $this->newParagraph($paragraph_type);
    $this->initFormLangcodes($form_state);
    $display = EntityFormDisplay::collectRenderDisplay($this->paragraph, 'default');

    $display->buildForm($this->paragraph, $form, $form_state);
    $this->paragraphType = $this->paragraph->getParagraphType();

    $form_state->set('field_name', $paragraph_type->id);
    $form_state->set('entity_field', $entity_field);
    if (!$form_state->has('entity')) {
      $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
      $form_state->set('entity', $entity);
    }

    $form += [
      '#title' => $this->formTitle(),
      '#paragraph' => $this->paragraph,
      '#entity_parent_type' => $entity_type,
      '#entity_field' => $entity_field,
      '#entity_id' => $entity_id,
      '#display' => $display,
      '#tree' => TRUE,
      '#after_build' => [
        [$this, 'afterBuild'],
      ],
      '#prefix' => '<div id="' . $this->getFormId() . '">',
      '#suffix' => '</div>',
      'actions' => [
        '#weight' => 100,
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#weight' => 100,
          '#value' => $this->t('Save'),
          '#attributes' => [
            'class' => ['tabs-btn--save'],
            'data-disable-refocus' => 'true',
          ],
        ],
        'cancel' => [
          '#type' => 'button',
          '#weight' => 200,
          '#value' => $this->t('Cancel'),
          '#attributes' => ['onClick' => 'history.go(-1); event.preventDefault();'],
        ],
      ],
    ];
    if ($this->getRequest()->isXmlHttpRequest()) {
      $form['actions']['cancel']['#ajax'] = [
        'callback' => '::cancel',
        'progress' => 'none',
      ];
      $form['actions']['cancel']['#attributes'] = [
        'class' => [
          'dialog-cancel',
          'tabs-btn--cancel',
        ],
      ];
    }
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    // Support for Field Group module based on Paragraphs module.
    // @todo Remove as part of https://www.drupal.org/node/2640056
    if ($this->moduleHandler->moduleExists('field_group')) {
      $context = [
        'entity_type' => $this->paragraph->getEntityTypeId(),
        'bundle' => $this->paragraph->bundle(),
        'entity' => $this->paragraph,
        'context' => 'form',
        'display_context' => 'form',
        'mode' => $display->getMode(),
      ];
      // phpcs:ignore
      field_group_attach_groups($form, $context);
      if (method_exists(FormatterHelper::class, 'formProcess')) {
        $form['#process'][] = [FormatterHelper::class, 'formProcess'];
      }
      elseif (function_exists('field_group_form_pre_render')) {
        $form['#pre_render'][] = 'field_group_form_pre_render';
      }
      elseif (function_exists('field_group_form_process')) {
        $form['#process'][] = 'field_group_form_process';
      }
    }

    return $form;
  }

  /**
   * After build callback fixes issues with data-drupal-selector.
   *
   * See https://www.drupal.org/project/drupal/issues/2897377
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form element.
   */
  public function afterBuild(array $element, FormStateInterface $form_state) {
    $parents = array_merge($element['#parents'], [$this->getFormId()]);
    $unprocessed_id = 'edit-' . implode('-', $parents);
    $element['#attributes']['data-drupal-selector'] = Html::getId($unprocessed_id);
    $element['#dialog_id'] = $unprocessed_id . '-dialog';
    return $element;
  }

  /**
   * Create the form title.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The form title.
   */
  protected function formTitle() {
    return $this->t('Add @type', ['@type' => $this->paragraph->getParagraphType()->label()]);
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $display = $form['#display'];

    $paragraph = clone $this->paragraph;
    $paragraph->getAllBehaviorSettings();

    $paragraph->setNeedsSave(TRUE);
    $display->extractFormValues($paragraph, $form, $form_state);

    $paragraph->isNew();
    $paragraph->save();

    $entity = $form_state->get('entity');
    $entity_field = $form_state->get('entity_field');
    $current = [];
    if (!empty($entity->get($entity_field))) {
      $current = $entity->get($entity_field)->getValue();
    }
    $current[] = [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];
    $entity->set($entity_field, $current);
    $entity->save();
    $form_state->disableRedirect(FALSE);
    $form_state->setRedirectUrl(Url::fromRoute('<current>'));

  }

  /**
   * Creates a new, empty paragraph empty of the provided type.
   *
   * @param \Drupal\paragraphs\ParagraphsTypeInterface $paragraph_type
   *   The paragraph type.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The new paragraph.
   */
  protected function newParagraph(ParagraphsTypeInterface $paragraph_type) {
    $entity_type = $this->entityTypeManager->getDefinition('paragraph');
    $bundle_key = $entity_type->getKey('bundle');
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph_entity */
    return $this->entityTypeManager->getStorage('paragraph')
      ->create([$bundle_key => $paragraph_type->id()]);
  }

  /**
   * Initializes form language code values.
   *
   * See Drupal\Core\Entity\ContentEntityForm.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function initFormLangcodes(FormStateInterface $form_state) {
    // Store the entity default language to allow checking whether the form is
    // dealing with the original entity or a translation.
    if (!$form_state->has('entity_default_langcode')) {
      $form_state->set('entity_default_langcode',
        $this->paragraph->getUntranslated()->language()->getId()
      );
    }

    // This value might have been explicitly populated to work with a particular
    // entity translation. If not we fall back to the most proper language based
    // on contextual information.
    if (!$form_state->has('langcode')) {

      // Imply a 'view' operation to ensure users edit entities in the same
      // language they are displayed. This allows to keep contextual editing
      // working also for multilingual entities.
      $form_state->set('langcode',
        $this->entityRepository->getTranslationFromContext($this->paragraph)
          ->language()->getId());
    }
  }

  /**
   * Form #ajax callback.
   *
   * Cancels the edit operation and closes the dialog.
   *
   * @param array $form
   *   The form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $form_state->setRebuild();
    return $response;
  }

}
