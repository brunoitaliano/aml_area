<?php

namespace Drupal\geo_hierarchy;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\geo_hierarchy\Entity\GeoNode;

/**
 * Provides helper methods for geo hierarchy traversal.
 */
final class GeoHierarchyManager {
  public function __construct(private EntityTypeManagerInterface $entityTypeManager) {
  }

  /**
   * Returns the children of a given parent geo node.
   *
   * @param int $parentId
   *   The parent geo node ID. Use 0 for root nodes.
   *
   * @return \Drupal\geo_hierarchy\Entity\GeoNode[]
   *   The child nodes.
   */
  public function getChildren(int $parentId): array {
    $storage = $this->entityTypeManager->getStorage('geo_node');
    $properties = ['parent' => $parentId > 0 ? $parentId : NULL];

    return $storage->loadByProperties($properties);
  }

  /**
   * Returns the ancestors for a given geo node up to the root.
   *
   * @param int $nodeId
   *   The geo node ID.
   *
   * @return \Drupal\geo_hierarchy\Entity\GeoNode[]
   *   Ordered list of ancestors from root to the node itself.
   */
  public function getAncestors(int $nodeId): array {
    $storage = $this->entityTypeManager->getStorage('geo_node');
    $ancestors = [];
    $current = $storage->load($nodeId);

    while ($current instanceof GeoNode) {
      $ancestors[] = $current;
      $parent = $current->get('parent')->entity;
      $current = $parent instanceof GeoNode ? $parent : NULL;
    }

    return array_reverse($ancestors);
  }

}
