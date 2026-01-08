<?php

namespace Drupal\geo_hierarchy\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Drupal\geo_hierarchy\Entity\GeoNode;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ExtensionListModule;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Drush commands for geo hierarchy.
 */
final class GeoHierarchyCommands extends DrushCommands {
  public function __construct(private EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct();
  }

  #[CLI\Command(name: 'geo-hierarchy:seed', aliases: ['gh-seed'])]
  #[CLI\Usage(name: 'geo-hierarchy:seed', description: 'Seed geo hierarchy from a JSON file.')]
  #[CLI\Option(name: 'file', description: 'Path to a JSON seed file. If omitted, uses geo_hierarchy/data/geo_hierarchy.seed.json inside the module.')]
  public function seed(array $options = ['file' => NULL]): void {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('geo_node');

    // Resolve JSON path.
    $file = $options['file'] ?? NULL;
    if (!$file) {
      /** @var \Drupal\Core\Extension\ExtensionListModule $module_list */
      $module_list = \Drupal::service('extension.list.module');
      $module_path = $module_list->getPath('geo_hierarchy');
      $file = $module_path . '/data/geo_hierarchy.seed.json';
    }

    if (!is_readable($file)) {
      $this->logger()->error(sprintf('Seed file not found or not readable: %s', $file));
      return;
    }

    $raw = file_get_contents($file);
    if ($raw === FALSE || $raw === '') {
      $this->logger()->error(sprintf('Seed file is empty or unreadable: %s', $file));
      return;
    }

    $data = json_decode($raw, TRUE);
    if (!is_array($data)) {
      $this->logger()->error(sprintf('Invalid JSON in seed file: %s', $file));
      return;
    }

    // Accept either {"country": {...}} or a direct node object {...}.
    $root = $data['country'] ?? $data;
    if (!is_array($root) || empty($root['name']) || empty($root['type'])) {
      $this->logger()->error('Seed JSON must contain a root object with at least "name" and "type".');
      return;
    }

    $created = 0;
    $updated = 0;

    $this->importNodeRecursive($storage, $root, NULL, $created, $updated);

    $this->logger()->success(sprintf('Geo hierarchy seeded from %s (created: %d, updated: %d).', $file, $created, $updated));
  }

  /**
   * Imports a node and its children recursively.
   */
  private function importNodeRecursive(EntityStorageInterface $storage, array $node, ?int $parent_id, int &$created, int &$updated): GeoNode {
    $name = (string) ($node['name'] ?? '');
    $type = (string) ($node['type'] ?? '');

    /** @var \Drupal\geo_hierarchy\Entity\GeoNode|null $entity */
    $entity = $this->loadExistingGeoNode($storage, $name, $type, $parent_id);

    $values = [
      'name' => $name,
      'type' => $type,
    ];

    // Parent is optional for the root.
    if ($parent_id) {
      $values['parent'] = $parent_id;
    }

    // Optional fields.
    if (!empty($node['iso_code'])) {
      $values['iso_code'] = (string) $node['iso_code'];
    }
    if (!empty($node['external_id'])) {
      $values['external_id'] = (string) $node['external_id'];
    }

    if ($entity) {
      $changed = FALSE;
      foreach ($values as $field => $value) {
        if ($entity->hasField($field) && !$entity->get($field)->isEmpty()) {
          // Compare current scalar value when possible.
          $current = $entity->get($field)->value ?? NULL;
          if ($field === 'parent') {
            $current = $entity->get('parent')->target_id ?? NULL;
          }
          if ((string) $current !== (string) $value) {
            $entity->set($field, $value);
            $changed = TRUE;
          }
        }
        elseif ($entity->hasField($field)) {
          $entity->set($field, $value);
          $changed = TRUE;
        }
      }

      if ($changed) {
        $entity->save();
        $updated++;
      }
    }
    else {
      $entity = $storage->create($values);
      $entity->save();
      $created++;
    }

    // Recurse into children.
    $children = $node['children'] ?? [];
    if (is_array($children)) {
      foreach ($children as $child) {
        if (!is_array($child) || empty($child['name']) || empty($child['type'])) {
          continue;
        }
        $this->importNodeRecursive($storage, $child, (int) $entity->id(), $created, $updated);
      }
    }

    return $entity;
  }

  /**
   * Loads an existing GeoNode by name, type, and parent.
   */
  private function loadExistingGeoNode(EntityStorageInterface $storage, string $name, string $type, ?int $parent_id): ?GeoNode {
    $props = [
      'name' => $name,
      'type' => $type,
    ];

    // Only constrain by parent when a parent id is provided.
    if ($parent_id) {
      $props['parent'] = $parent_id;
    }

    $existing = $storage->loadByProperties($props);
    if (!$existing) {
      return NULL;
    }

    $entity = reset($existing);
    return $entity instanceof GeoNode ? $entity : NULL;
  }

}
