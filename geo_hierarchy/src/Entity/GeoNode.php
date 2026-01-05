<?php

namespace Drupal\geo_hierarchy\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityOwnerInterface;
use Drupal\Core\Entity\EntityOwnerTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

#[\Drupal\Core\Entity\Attribute\ContentEntityType(
  id: 'geo_node',
  label: new TranslatableMarkup('Geo Node'),
  label_collection: new TranslatableMarkup('Geo Nodes'),
  handlers: [
    'list_builder' => 'Drupal\\Core\\Entity\\EntityListBuilder',
    'views_data' => 'Drupal\\views\\EntityViewsData',
    'access' => 'Drupal\\Core\\Entity\\EntityAccessControlHandler',
    'form' => [
      'default' => 'Drupal\\Core\\Entity\\ContentEntityForm',
      'add' => 'Drupal\\Core\\Entity\\ContentEntityForm',
      'edit' => 'Drupal\\Core\\Entity\\ContentEntityForm',
      'delete' => 'Drupal\\Core\\Entity\\ContentEntityDeleteForm',
    ],
  ],
  base_table: 'geo_node',
  data_table: 'geo_node_field_data',
  translatable: true,
  fieldable: true,
  admin_permission: 'administer geo hierarchy',
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'label' => 'name',
    'uid' => 'uid',
    'status' => 'status',
    'langcode' => 'langcode',
  ],
  indexes: [
    'geo_node_type_parent' => ['type', 'parent_target_id'],
  ],
  links: [
    'canonical' => '/admin/content/geo-node/{geo_node}',
    'add-form' => '/admin/content/geo-node/add',
    'edit-form' => '/admin/content/geo-node/{geo_node}/edit',
    'delete-form' => '/admin/content/geo-node/{geo_node}/delete',
    'collection' => '/admin/content/geo-node',
  ],
  field_ui_base_route: 'entity.geo_node.collection',
)]
class GeoNode extends ContentEntityBase implements ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {
  use EntityOwnerTrait;
  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Name'))
      ->setRequired(true)
      ->setTranslatable(true)
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Type'))
      ->setRequired(true)
      ->setTranslatable(true)
      ->setSettings([
        'allowed_values' => [
          'country' => 'Country',
          'region' => 'Region',
          'province' => 'Province',
          'municipality' => 'Municipality',
          'locality' => 'Locality',
          'area' => 'Area',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['parent'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Parent'))
      ->setSetting('target_type', 'geo_node')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['iso_code'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('ISO code'))
      ->setTranslatable(true)
      ->setSettings([
        'max_length' => 64,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['external_id'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('External ID'))
      ->setTranslatable(true)
      ->setSettings([
        'max_length' => 128,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Authored by'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getCurrentUserId')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Published'))
      ->setDefaultValue(true)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setTranslatable(true);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setTranslatable(true);

    return $fields;
  }

}
