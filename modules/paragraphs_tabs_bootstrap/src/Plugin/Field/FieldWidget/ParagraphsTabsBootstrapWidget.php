<?php

namespace Drupal\paragraphs_tabs_bootstrap\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\paragraphs\Plugin\Field\FieldWidget\InlineParagraphsWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'paragraphs_tabs_bootstrap_widget' widget.
 *
 * @FieldWidget(
 *   id = "paragraphs_tabs_bootstrap_widget",
 *   label = @Translation("Paragraphs tabs Bootstrap"),
 *   description = @Translation("Paragraphs tabs form widget."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class ParagraphsTabsBootstrapWidget extends InlineParagraphsWidget {

  /**
   * Constructor paragraphs tabs widget.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The render service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, protected EntityTypeManagerInterface $entityTypeManager, protected AccountInterface $currentUser, protected RendererInterface $renderer) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('renderer'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    $element = parent::addMoreAjax($form, $form_state);

    // By default, the form element adds a div.ajax-new-content around the
    // newest item to be added - but this breaks vertical tabs because the
    // details elements need to be children (i.e.: not descendants) of the
    // vertical tab element. Remove the wrapper and set a default vertical tab
    // instead.
    $newestDelta = $element['#max_delta'];
    unset($element[$newestDelta]['#prefix']);
    unset($element[$newestDelta]['#suffix']);
    $element['#default_tab'] = !empty($element[$newestDelta]['#id']) ? $element[$newestDelta]['#id'] : '';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Widget settings defined by this module.
      'summary_selector' => '',

      // Collapsing a paragraph inside a vertical tab hides useful controls
      // behind extra clicks, so set defaults under the assumption that a user
      // won't want to collapse paragraphs.
      'edit_mode' => 'open',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    // Delete the "Active tab" form value that the vertical tab widget adds
    // before trying to extract the rest of the form values. This prevents the
    // widget from trying to extract paragraph sub-form data from the tab name
    // (which doesn't work).
    $field_name = $this->fieldDefinition->getName();
    $path = array_merge($form['#parents'], [$field_name]);
    $path[] = $field_name . '__active_tab';
    $form_state->unsetValue($path);

    return parent::extractFormValues($items, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    // Ask the base widget to render all the elements of the form in the way it
    // normally does get the field name, which will eventually become the
    // #group for all tabs in the widget.
    $widget = parent::formMultipleElements($items, $form, $form_state);
    $group = $widget['#field_name'];

    // Attach our library and the data it needs to complete its operation.
    $widget['#attached']['library'][] = 'paragraphs_tabs_bootstrap/paragraphs-vertical-tabs-bootstrap';
    $widget['#attached']['drupalSettings']['paragraphs_tabs_bootstrap_widget'][$group]['summarySelector']
      = $this->getSetting('summary_selector');

    $widgetId = Html::getUniqueId($group);
    $widget['#prefix'] = '<div id="' . $widgetId . '" data-paragraphs-tabs-widget-group-wrapper="' . Html::escape($group) . '">';
    $widget['#suffix'] = '</div>';

    // Replace the default way of rendering the widget with a vertical tabs
    // widget.
    unset($widget['#theme']);
    $widget['#type'] = 'vertical_tabs';
    $storage = $this->entityTypeManager->getStorage('paragraphs_type');
    $style = $this->entityTypeManager->getStorage('image_style')->load('thumbnail');

    // Loop through each single-element form.
    foreach (Element::children($widget) as $delta) {
      if (is_numeric($delta)) {
        $paragraphs_type = $storage->load($widget[$delta]["#paragraph_type"]);
        $imgIcon = '';
        if (method_exists($paragraphs_type, 'getIconUrl')) {
          $iconFile = $paragraphs_type->getIconFile();
          if ($iconFile) {
            $destination = $style->buildUri($uri = $iconFile->getFileUri());
            if (!file_exists($destination)) {
              $style->createDerivative($uri, $destination);
            }
            $render = [
              '#theme' => 'image_style',
              '#style_name' => 'thumbnail',
              '#uri' => $uri,
            ];
            $imgIcon = $this->renderer->render($render);
          }
        }
        $title = $imgIcon . $paragraphs_type->label();
        // Transform the single-element form into a details' element, with a
        // title and a group, so it can be placed into the correct vertical tabs
        // element.
        $widget[$delta]['#type'] = 'details';
        $widget[$delta]['#title'] = $title;
        $widget[$delta]['#description'] = $paragraphs_type->description;
        $widget[$delta]['#group'] = $group;

        // The default prefix and suffix define an HTML tag wrapper, which
        // interferes with the core/drupal.vertical-tabs library JavaScript,
        // which requires tabs to be children (i.e.: not descendants) of the
        // vertical tabs' widget.
        unset($widget[$delta]['#prefix']);
        unset($widget[$delta]['#suffix']);

        // Hide the paragraph title, since that's already in the tab.
        if (isset($widget[$delta]['top']['paragraph_type_title'])) {
          $widget[$delta]['top']['paragraph_type_title']['#printed'] = TRUE;
        }

        // Temporarily hide the weight element, as it doesn't display properly.
        if (isset($widget[$delta]['_weight'])) {
          $widget[$delta]['_weight']['#printed'] = TRUE;
        }

        // Modify the "Remove" button (and its follow-up "Confirm removal" and
        // "Restore" buttons) for the tab: tell AJAX to replace the whole field
        // wrapper - otherwise its wrapper selector doesn't refer to anything.
        $removeButtonNames = [
          'remove_button',
          'confirm_remove_button',
          'restore_button',
        ];
        foreach ($removeButtonNames as $removeButtonName) {
          if (isset($widget[$delta]['top']['links'][$removeButtonName]['#ajax']['wrapper'])) {
            $widget[$delta]['top']['links'][$removeButtonName]['#ajax']['wrapper'] = $widgetId;
          }
        }
      }
    }

    // Update the "add more" button for better compatibility with vertical tabs:
    // remove the default theme wrappers, remove the default "to field" suffix
    // (which ends up outside the wrapper and therefore at the top of every
    // tab), add an attribute to the add_more button so JavaScript can move it
    // into the vertical tabs' menu, and loop through each "add more" button &
    // tell AJAX to replace the whole field wrapper - otherwise its wrapper
    // selector doesn't refer to anything.
    unset($widget['add_more']['#suffix']);
    $widget['add_more']['#attributes']['data-paragraphs-tabs-widget-addmore-group'] = $group;
    foreach (Element::children($widget['add_more']) as $addMoreButtonKey) {
      if (isset($widget['add_more'][$addMoreButtonKey]['#ajax']['wrapper'])) {
        $widget['add_more'][$addMoreButtonKey]['#ajax']['wrapper'] = $widgetId;
      }
    }

    return $widget;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    // Collapsing a paragraph inside a vertical tab hides useful controls behind
    // extra clicks, so hide controls under the assumption that a user won't
    // want to collapse paragraphs.
    $elements['edit_mode']['#access'] = FALSE;

    // A jQuery selector for an HTML element inside the tab whose value will be
    // used as the tab summary.
    // Warning: This selector will be evaluated by jQuery in the client's
    // browser and therefore has security implications.
    $elements['summary_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tab summary selector'),
      '#description' => $this->t("A <a href='@jquery_api_selectors'>jQuery selector</a> for an HTML element inside the tab whose value will be used as the tab summary. Warning: This selector will be evaluated by jQuery in the client's browser and therefore has security implications.", [
        '@jquery_api_selectors' => 'https://api.jquery.com/category/selectors/',
      ]),
      '#default_value' => $this->getSetting('summary_selector'),
      '#access' => $this->currentUser->hasPermission('change paragraphs_tabs_widget summary_selector'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    // Widget settings inherited from field.widget.settings.paragraphs.
    $summary[] = $this->t('Title: @title', ['@title' => $this->getSetting('title')]);
    $summary[] = $this->t('Plural title: @title_plural', [
      '@title_plural' => $this->getSetting('title_plural'),
    ]);
    switch ($this->getSetting('add_mode')) {
      case 'select':
      default:
        $add_mode = $this->t('Select list');
        break;

      case 'button':
        $add_mode = $this->t('Buttons');
        break;

      case 'dropdown':
        $add_mode = $this->t('Dropdown button');
        break;
    }
    $summary[] = $this->t('Add mode: @add_mode', ['@add_mode' => $add_mode]);
    $summary[] = $this->t('Form display mode: @form_display_mode', [
      '@form_display_mode' => $this->getSetting('form_display_mode'),
    ]);
    if ($this->getDefaultParagraphTypeLabelName() !== NULL) {
      $summary[] = $this->t('Default paragraph type: @default_paragraph_type', [
        '@default_paragraph_type' => $this->getDefaultParagraphTypeLabelName(),
      ]);
    }

    // Widget settings defined by this module.
    if (!empty($this->getSetting('summary_selector'))) {
      $summary[] = $this->t('Tab summary selector: @summary_selector', [
        '@summary_selector' => $this->getSetting('summary_selector'),
      ]);
    }

    return $summary;
  }

}
