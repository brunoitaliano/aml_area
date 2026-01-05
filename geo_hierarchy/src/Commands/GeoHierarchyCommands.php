<?php

namespace Drupal\geo_hierarchy\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Drupal\geo_hierarchy\Entity\GeoNode;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Drush commands for geo hierarchy.
 */
final class GeoHierarchyCommands extends DrushCommands {
  public function __construct(private EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct();
  }

  #[CLI\Command(name: 'geo-hierarchy:seed', aliases: ['gh-seed'])]
  #[CLI\Usage(name: 'geo-hierarchy:seed', description: 'Seed a sample geo hierarchy (Spain > Andalusia > Malaga > Nerja).')]
  public function seed(): void {
    $storage = $this->entityTypeManager->getStorage('geo_node');

    $spain = $storage->create([
      'name' => 'Spain',
      'type' => 'country',
      'iso_code' => 'ES',
    ]);
    $spain->save();

    $andalusia = $storage->create([
      'name' => 'Andalusia',
      'type' => 'region',
      'parent' => $spain->id(),
    ]);
    $andalusia->save();

    $malaga = $storage->create([
      'name' => 'Malaga',
      'type' => 'province',
      'parent' => $andalusia->id(),
    ]);
    $malaga->save();

    $nerja = $storage->create([
      'name' => 'Nerja',
      'type' => 'municipality',
      'parent' => $malaga->id(),
    ]);
    $nerja->save();

    $this->logger()->success('Seeded sample geo hierarchy.');
  }

}
