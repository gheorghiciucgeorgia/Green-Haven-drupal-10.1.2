<?php

namespace Drupal\paragraphs_tabs_bootstrap\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check access page.
 */
class ParagraphAccessController extends ControllerBase {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new paragraph table access.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The route match service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(RouteMatchInterface $current_route_match, ModuleHandlerInterface $module_handler, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentRouteMatch = $current_route_match;
    $this->moduleHandler = $module_handler;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('module_handler'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function accessAdd(AccountInterface $account) {
    $paragraph_type = $this->currentRouteMatch->getParameter('paragraph_type');
    $entity_type = $this->currentRouteMatch->getParameter('entity_type');
    $field_name = $this->currentRouteMatch->getParameter('entity_field');
    $entity_id = $this->currentRouteMatch->getParameter('entity_id');
    $entity = $this->entityTypeManager->getStorage($entity_type)
      ->load($entity_id);
    if ($this->moduleHandler->moduleExists('paragraphs_type_permissions')) {
      $bundle = $paragraph_type->getOriginalId();
      $entityAccess = $account->hasPermission('create paragraph content ' . $bundle);
      return AccessResult::allowedIf($entityAccess);
    }

    if ($this->moduleHandler->moduleExists('field_permissions')) {
      if ($account->hasPermission('access private fields')) {
        return AccessResult::allowedIf(TRUE);
      }
      $field_permission = TRUE;
      $bundle_fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $entity->bundle());
      $field_definition = $bundle_fields[$field_name];
      $permissionSetting = $field_definition->getFieldStorageDefinition();
      $field_permissions_type = $permissionSetting->getThirdPartySettings('field_permissions');
      $permission = !empty($field_permissions_type['permission_type']) ? $field_permissions_type['permission_type'] : FALSE;
      if ($permission == 'custom') {
        $field_permission = $account->hasPermission('create ' . $field_name);
      }
      if ($permission == 'private') {
        $field_permission = FALSE;
      }
      return AccessResult::allowedIf($field_permission);
    }
    return AccessResult::allowedIf('access content');
  }

}
