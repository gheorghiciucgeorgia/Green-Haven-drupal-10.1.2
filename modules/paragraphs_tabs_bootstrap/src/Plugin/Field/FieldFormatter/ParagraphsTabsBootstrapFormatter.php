<?php

namespace Drupal\paragraphs_tabs_bootstrap\Plugin\Field\FieldFormatter;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\entity_reference_revisions\Plugin\Field\FieldFormatter\EntityReferenceRevisionsEntityFormatter;

/**
 * Plugin implementation of the 'paragraphs_tabs_bootstrap_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "paragraphs_tabs_bootstrap_formatter",
 *   module = "paragraphs_tabs_bootstrap",
 *   label = @Translation("Paragraphs tabs Bootstrap"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class ParagraphsTabsBootstrapFormatter extends EntityReferenceRevisionsEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    // Implement default settings.
    return [
      // 'view_mode' => 'default',
      'vertical' => TRUE,
      'mode' => 'tab',
      'empty_cell_value' => FALSE,
      'empty' => FALSE,
      'header_text' => '',
      'footer_text' => '',
      'custom_class' => '',
      'hide_line_operations' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settingForm = [
      'vertical' => [
        '#title' => $this->t('Tabs vertical'),
        '#description' => $this->t('If checked, table data will show in vertical mode'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSettings()['vertical'],
      ],
      'mode' => [
        '#title' => $this->t('Mode'),
        '#type' => 'select',
        '#options' => [
          'tab' => $this->t('Tabs'),
          'pill' => $this->t('Pills'),
        ],
        '#default_value' => $this->getSetting('mode'),
      ],
      'empty' => [
        '#title' => $this->t('Hide empty tabs'),
        '#description' => $this->t('If enabled, hide empty paragraphs tabs'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSettings()['empty'],
      ],
      'header_text' => [
        '#title' => $this->t('Custom text at the header of each paragraph'),
        '#description' => $this->t('Variable available {{ paragraph_name }}, {{ paragraph_type }}, {{ paragraph_id }}, {{ paragraph_revision_id }}, {{ entity_type }}, {{ entity_field }}, {{ entity_id }}'),
        '#type' => 'textarea',
        '#default_value' => $this->getSettings()['header_text'],
      ],
      'footer_text' => [
        '#title' => $this->t('Custom text at the footer of each paragraph'),
        '#description' => $this->t('Variable available {{ paragraph_name }}, {{ paragraph_type }}, {{ paragraph_id }}, {{ paragraph_revision_id }}, {{ entity_type }}, {{ entity_field }}, {{ entity_id }}'),
        '#type' => 'textarea',
        '#default_value' => $this->getSettings()['header_text'],
      ],
      'custom_class' => [
        '#title' => $this->t('Set table class'),
        '#type' => 'textfield',
        '#default_value' => $this->getSettings()['custom_class'],
      ],
      'hide_line_operations' => [
        '#title' => $this->t('Hide line operations'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSettings()['hide_line_operations'],
      ],
    ];
    return $settingForm + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if (!empty($this->getSetting('vertical'))) {
      $summary[] = $this->t('Tabs mode vertical');
    }
    if (!empty($this->getSetting('empty'))) {
      $summary[] = $this->t('Hide empty content');
    }
    if (!empty($this->getSetting('custom_class'))) {
      $summary[] = $this->t('Custom class: @class', ['@class' => $this->getSetting('custom_class')]);
    }
    if (!empty($this->getSetting('hide_line_operations'))) {
      $summary[] = $this->t('Hide line operations.');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $setting = $this->getSettings();
    $parent = $items->getParent()->getEntity();
    $parentType = $parent->getEntityTypeId();
    $entity_type_id = $this->getFieldSetting('target_type');
    $field_definition = $items->getFieldDefinition();
    $selectionHandler = \Drupal::service('plugin.manager.entity_reference_selection')->getSelectionHandler($field_definition);
    $bundles = $selectionHandler->entityTypeBundleInfo->getBundleInfo($entity_type_id);
    $field_name = $field_definition->getName();
    $field_label = $field_definition->getLabel();
    $entity = $items->getEntity();
    $entityId = $entity->id();
    $currentURL = \Drupal::service('path.current')->getPath();
    $destination = $currentURL;
    $hasPermission = $this->checkPermissionOperation($entity, $field_name);
    if (!empty($setting['hide_line_operations'])) {
      $hasPermission = FALSE;
    }
    $handlers = $field_definition->getSetting("handler_settings");
    $direction = 'horizontal';
    $mode = $setting['mode'];
    if ($setting['vertical']) {
      $direction = 'vertical';
      $mode = 'pill';
    }
    $btnDropdown = $build_nav = [];
    $btnAttr = [
      'role' => "tab",
      'type' => 'button',
      'aria-selected' => "false",
    ];
    if ($setting['vertical']) {
      $btnAttr['type'] = "button";
    }
    $dialog_width = '80%';
    $storage = \Drupal::entityTypeManager()->getStorage('paragraphs_type');
    $style = \Drupal::entityTypeManager()->getStorage('image_style')->load('thumbnail');

    foreach ($handlers['target_bundles'] as $handler) {
      /** @var \Drupal\paragraphs\Entity\ParagraphsType $paragraphs_type */
      $paragraphs_type = $storage->load($handler);
      $imgIcon = '';
      if (method_exists($paragraphs_type, 'getIconUrl')) {
        $iconFile = $paragraphs_type->getIconFile();
        if ($iconFile) {
          $iconFileStyle = $style->buildUri($uri = $iconFile->getFileUri());
          if (!file_exists($iconFileStyle)) {
            $style->createDerivative($uri, $iconFileStyle);
          }
          $render = [
            '#theme' => 'image_style',
            '#style_name' => 'thumbnail',
            '#uri' => $uri,
            '#attributes' => ['class' => ['float-md-start']],
          ];
          $imgIcon = \Drupal::service('renderer')->render($render);
        }
      }
      $title = $bundles[$handler]['label'];
      $attr = $btnAttr + [
        'class' => ['nav-link', 'text-start', $handler],
        'data-bs-toggle' => $mode,
        'id' => Html::getId("nav-" . $handler),
        'data-bs-target' => '#' . $handler,
        'aria-controls' => $handler,
        'data-group' => $field_name,
        'data-bs-placement' => "top",
        'title' => $paragraphs_type->description,
      ];
      $build_nav[$handler] = [
        'attributes' => new Attribute($attr),
        'label' => $title,
        'image' => $imgIcon,
      ];
      $route_params = [
        'paragraph_type' => $handler,
        'entity_type' => $parentType,
        'entity_field' => $field_name,
        'entity_id' => $entityId,
      ];
      $btnDropdown[$handler] = [
        '#type' => 'link',
        '#url' => Url::fromRoute('paragraphs.add', $route_params)
          ->setOption('query', ['destination' => $destination]),
        '#title' => Markup::create($imgIcon . $title),
        '#attributes' => [
          'class' => [
            // 'use-ajax',
            'dropdown-item',
          ],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode(['width' => $dialog_width]),
        ],
      ];
    }
    if (count($btnDropdown) == 1) {
      $imgIcon = '<i class="bi bi-plus"></i> ';
      $btnDropdown[$handler]['#attributes']['class'] = ['btn', 'btn-success'];
      $btnDropdown[$handler]['#title'] = Markup::create($imgIcon . $title);
    }
    $elements = [
      '#type' => 'container',
      '#theme' => 'paragraphs_tabs_bootstrap_wrapper',
      '#field_name' => $field_name,
      '#title' => $field_label,
      '#class_wrapper' => 'paragraphs-bootstrap-tabs-wrapper',
      '#navigation' => $build_nav,
      '#btn_add' => $btnDropdown,
      '#direction' => $direction,
      '#mode' => $mode,
      '#settings' => $setting,
      '#attached' => [
        'library' => ['paragraphs_tabs_bootstrap/paragraphs-tabs-bootstrap'],
      ],
    ];
    $childes = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tab-content'], 'id' => $field_name],
      '#cache' => [
        'contexts' => [
          'languages:' . LanguageInterface::TYPE_INTERFACE,
        ],
      ],
    ];
    if ($setting['vertical']) {
      $childes['#attributes']['class'][] = 'col-9';
      $elements['#class_wrapper'] .= ' col-3';
      $elements['#attributes'] = [
        'class' => [
          'd-flex',
          'align-items-start',
          'vertical-tabs-list',
          $setting['custom_class'],
        ],
      ];
    }
    $view_mode = $this->getSetting('view_mode');
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      // Protect ourselves from recursive rendering.
      static $depth = 0;
      $depth++;
      if ($depth > 20) {
        $this->loggerFactory->get('entity')
          ->error('Recursive rendering detected when rendering entity @entity_type @entity_id. Aborting rendering.', [
            '@entity_type' => $entity->getEntityTypeId(),
            '@entity_id' => $entity->id(),
          ]);
        return $elements;
      }
      $type = $entity->getType();
      if (empty($childes[$type])) {
        $childes[$type] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['tab-pane fade'],
            'id' => $type,
            'role' => "tabpanel",
            'aria-labelledby' => $type . "-tab",
          ],
        ];
        $bg = 0;
        if ($hasPermission && !empty($btnDropdown[$type])) {
          $childes[$type]['btn_add'] = [
            '#type' => 'link',
            '#url' => $btnDropdown[$type]['#url'],
            '#title' => Markup::create('<i class="bi bi-plus" aria-hidden="true"></i> ' . $this->t('Add')),
            '#weight' => -1,
            '#attributes' => [
              'class' => ['btn', 'btn-success', 'mt-3'],
            ],
          ];
          unset($btnDropdown[$type]);
        }
      }
      $view_builder = \Drupal::entityTypeManager()
        ->getViewBuilder($entity->getEntityTypeId());

      $paragraph_id = $entity->id();
      $childes[$type][$delta] = [
        '#theme' => 'paragraphs_tabs_bootstrap_content',
        '#content' => $view_builder->view($entity, $view_mode,
          $entity->language()->getId()),
        '#attributes' => new Attribute([
          'class' => [$bg++ % 2 ? 'bg-light' : ''],
          'data-delta' => $delta,
        ]),
        '#paragraph_id' => $paragraph_id,
      ];
      $context = [
        'paragraph_name' => $bundles[$type]['label'],
        'paragraph_type' => $type,
        'paragraph_id' => $paragraph_id,
        'paragraph_revision_id' => $entity->getRevisionId(),
        'entity_type' => $entity_type_id,
        'entity_field' => $field_name,
        'entity_id' => $entityId,
      ];
      $max_delta = 200;
      if (!empty($setting['header_text'])) {
        $childes[$type][$delta]['#header_text'] = [
          '#type' => 'inline_template',
          '#template' => $setting['header_text'],
          '#weight' => $max_delta,
          '#context' => $context,
        ];
      }
      if (!empty($setting['footer_text'])) {
        $childes[$type][$delta]['#footer_text'] = [
          '#type' => 'inline_template',
          '#template' => $setting['footer_text'],
          '#weight' => $max_delta,
          '#context' => $context,
        ];
      }
      if ($hasPermission) {
        $childes[$type][$delta]['#operation'] = $this->paragraphsTabsLinksAction($paragraph_id, $destination, $type);
        $childes[$type][$delta]['#operation']['#weight'] = $max_delta + 1;
      }

      // Add a resource attribute to set the mapping property's value to the
      // entity's url. Since we don't know what the markup of the entity will
      // be, we shouldn't rely on it for structured data such as RDFa.
      if (!empty($items[$delta]->_attributes) && !$entity->isNew() && $entity->hasLinkTemplate('canonical')) {
        $items[$delta]->_attributes += [
          'resource' => $entity->toUrl()
            ->toString(),
        ];
      }
      $depth = 0;
    }

    // Hidden tabs if empty.
    foreach ($elements['#navigation'] as $paragraphsType => $navigation) {
      if (empty($childes[$paragraphsType])) {
        if ($setting['empty']) {
          unset($elements['#navigation'][$paragraphsType]);
        }
        else {
          $childes[$paragraphsType] = [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['tab-pane fade'],
              'id' => $paragraphsType,
              'role' => 'tabpanel',
              'aria-labelledby' => $paragraphsType . '-tab',
            ],
            '#children' => ' ',
          ];
        }
      }
    }

    $elements['#content'] = $childes;
    $this->setActiveTab($elements, $field_name);
    return $elements;
  }

  /**
   * Set Active Tab.
   */
  protected function setActiveTab(&$elements, $field_name) {
    $active_tab = '';
    $cookie = \Drupal::service('request_stack')
      ->getCurrentRequest()->cookies->get('paragraphs_bootstrap_tabs');
    if (!empty($cookie)) {
      $active_tabs = Json::decode($cookie);
      if (!empty($active_tabs[$field_name])) {
        $active_tab = $active_tabs[$field_name];
      }
    }
    if (empty($active_tab)) {
      $active_tab = array_key_first($elements['#navigation']);
    }
    if (!empty($active_tab) && !empty($elements['#navigation'][$active_tab])) {
      $elements['#navigation'][$active_tab]['attributes']->addClass('active')
        ->offsetSet('aria-selected', 'true');
      $elements['#navigation'][$active_tab]['attributes']->offsetSet('aria-current', 'page');
      $elements['#content'][$active_tab]['#attributes']['class'][] = 'show active';
    }
  }

  /**
   * Check permission Operation.
   *
   * @param object $entity
   *   The entity.
   * @param string $fieldName
   *   Field name in Entity.
   *
   * @return bool
   *   Current user has permission or not.
   */
  public function checkPermissionOperation($entity, $fieldName) {
    $hasPermission = FALSE;
    $user = \Drupal::currentUser();
    $permissions = [
      'bypass node access',
      'administer nodes',
      'administer paragraphs_item fields',
      'create ' . $fieldName,
      'edit ' . $fieldName,
      'edit own ' . $fieldName,
    ];
    foreach ($permissions as $permission) {
      if ($user->hasPermission($permission)) {
        $hasPermission = TRUE;
        break;
      }
    }
    $entityType = $entity->getEntityTypeId();
    if (!$hasPermission && $entityType != 'user') {
      $uid = $entity->getOwnerId();
      if ($user->hasPermission($permission) && $uid && $uid == $user->id()) {
        $hasPermission = TRUE;
      }
    }
    return $hasPermission;
  }

  /**
   * Links action.
   */
  protected function paragraphsTabsLinksAction($paragraphsId = FALSE, $destination = '', $paragraphs_type = '') {
    if (!\Drupal::service('module_handler')->moduleExists('paragraphs_table')) {
      return FALSE;
    }
    $route_params = [
      'paragraph' => $paragraphsId,
    ];
    if (!empty($destination)) {
      $route_params['destination'] = $destination;
    }
    $dialog_width = '80%';
    $operation = [
      '#type' => 'container',
      '#attributes' => ['class' => ['btn-group', 'operation', $paragraphs_type]],
      'view' => [
        '#type' => 'link',
        '#url' => Url::fromRoute('entity.paragraphs_item.canonical', $route_params)
          ->setOption('query', ['destination' => $destination]),
        '#title' => [
          '#type' => 'inline_template',
          '#template' => '<i class="bi bi-eye"></i> {{ title }}',
          '#context' => [
            'title' => $this->t('View'),
          ],
        ],
        '#attributes' => [
          'class' => ['use-ajax', 'btn', 'btn-success'],
          'data-dialog-type' => "dialog",
          'data-dialog-options' => Json::encode(['width' => $dialog_width]),
        ],
      ],
      'edit' => [
        '#type' => 'link',
        '#url' => Url::fromRoute('entity.paragraphs_item.edit_form', $route_params)
          ->setOption('query', ['destination' => $destination]),
        '#title' => [
          '#type' => 'inline_template',
          '#template' => '<i class="bi bi-pencil-square"></i> {{ title }}',
          '#context' => [
            'title' => $this->t('Edit'),
          ],
        ],
        '#attributes' => [
          'class' => ['btn', 'btn-warning'],
          'data-dialog-type' => "dialog",
          'data-dialog-options' => Json::encode(['width' => $dialog_width]),
        ],
      ],
      'duplicate' => [
        '#type' => 'link',
        '#url' => Url::fromRoute('entity.paragraphs_item.clone_form', $route_params)
          ->setOption('query', ['destination' => $destination]),
        '#title' => [
          '#type' => 'inline_template',
          '#template' => '<i class="bi bi-files"></i> {{ title }}',
          '#context' => [
            'title' => $this->t('Duplicate'),
          ],
        ],
        '#attributes' => [
          'class' => ['btn', 'btn-primary'],
          'data-dialog-type' => "dialog",
          'data-dialog-options' => Json::encode(['width' => $dialog_width]),
        ],
      ],
      'delete' => [
        '#type' => 'link',
        '#url' => Url::fromRoute('entity.paragraphs_item.delete_form', $route_params)
          ->setOption('query', ['destination' => $destination]),
        '#title' => [
          '#type' => 'inline_template',
          '#template' => '<i class="bi bi-trash"></i> {{ title }}',
          '#context' => [
            'title' => $this->t('Remove'),
          ],
        ],
        '#attributes' => [
          'class' => ['btn', 'btn-danger'],
          'data-dialog-type' => "dialog",
          'data-dialog-options' => Json::encode(['width' => $dialog_width]),
        ],
      ],
    ];

    // Alter row operation.
    \Drupal::moduleHandler()
      ->alter('paragraphs_tabs_operations', $operation, $paragraphsId, $paragraphs_type);
    return $operation;
  }

}
