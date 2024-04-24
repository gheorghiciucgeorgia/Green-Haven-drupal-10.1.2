<?php

namespace Drupal\paragraphs_tabs_bootstrap\Controller;

use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\paragraphs\ParagraphsTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class definition for ComponentFormController.
 */
class ComponentFormController extends ControllerBase {

  use AjaxHelperTrait;
  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * The ModalFormExampleController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   */
  public function __construct(FormBuilder $formBuilder) {
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Responds with a component insert form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\paragraphs\ParagraphsTypeInterface $paragraph_type
   *   The Paragraph Type to insert.
   * @param string $entity_type
   *   Entity type.
   * @param string $entity_field
   *   Entity field to store paragraphs.
   * @param int $entity_id
   *   Entity id.
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   A build array or Ajax respone.
   */
  public function addForm(Request $request, ParagraphsTypeInterface $paragraph_type, $entity_type, $entity_field, $entity_id) {
    $modal_form = $this->formBuilder->getForm('\Drupal\paragraphs_tabs_bootstrap\Form\AddComponentForm', $paragraph_type, $entity_type, $entity_field, $entity_id);
    if ($this->isAjax()) {
      $response = new AjaxResponse();
      $settings = ['width' => '80%'];
      $selector = '#paragraphs_tabs_bootstraps';
      $response->addCommand(new OpenDialogCommand($selector, $modal_form['#title'], $modal_form, $settings));
      return $response;
    }
    return $modal_form;
  }

}
