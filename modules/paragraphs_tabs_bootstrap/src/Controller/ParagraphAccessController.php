<?php

namespace Drupal\paragraphs_tabs_bootstrap\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check access page.
 */
class ParagraphAccessController extends ControllerBase {

  /**
   * Constructs a new paragraph tabs access.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $currentRouteMatch
   *   The route match service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   */
  public function __construct(protected RouteMatchInterface $currentRouteMatch, protected EntityFieldManagerInterface $entityFieldManager) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_field.manager'),
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
    $entity = $this->entityTypeManager()
      ->getStorage($entity_type)
      ->load($entity_id);
    if ($this->moduleHandler()->moduleExists('paragraphs_type_permissions')) {
      $bundle = $paragraph_type->getOriginalId();
      $entityAccess = $account->hasPermission('create paragraph content ' . $bundle);
      return AccessResult::allowedIf($entityAccess);
    }

    if ($this->moduleHandler()->moduleExists('field_permissions')) {
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
