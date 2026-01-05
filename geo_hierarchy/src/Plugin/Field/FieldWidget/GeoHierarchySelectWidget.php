<?php

namespace Drupal\geo_hierarchy\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\geo_hierarchy\GeoHierarchyManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[\Drupal\Core\Field\Attribute\FieldWidget(
  id: 'geo_hierarchy_select',
  label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Hierarchical Geo Select'),
  field_types: ['entity_reference']
)]
final class GeoHierarchySelectWidget extends FieldWidgetBase implements ContainerFactoryPluginInterface {
  private const LEVELS = [
    'country' => 'Country',
    'region' => 'Region',
    'province' => 'Province',
    'municipality' => 'Municipality',
    'locality' => 'Locality',
    'area' => 'Area',
  ];

  public function __construct(
    string $plugin_id,
    array $plugin_definition,
    $field_definition,
    array $settings,
    array $third_party_settings,
    private GeoHierarchyManager $geoHierarchyManager,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('geo_hierarchy.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $field_name = $items->getName();
    $wrapper_id = $field_name . '-' . $delta . '-geo-hierarchy';
    $element['#tree'] = true;

    $selected_by_type = [];
    $input = $form_state->getValue([$field_name, $delta, 'hierarchy']);
    if (is_array($input)) {
      $selected_by_type = array_filter($input, static fn($value) => (int) $value > 0);
    }
    elseif (!empty($items[$delta]->target_id)) {
      $ancestors = $this->geoHierarchyManager->getAncestors((int) $items[$delta]->target_id);
      foreach ($ancestors as $ancestor) {
        $selected_by_type[$ancestor->get('type')->value] = (int) $ancestor->id();
      }
    }

    $element['hierarchy'] = [
      '#type' => 'container',
      '#attributes' => ['id' => $wrapper_id],
    ];

    $parent_id = 0;
    foreach (self::LEVELS as $type => $label) {
      $options = [];
      if ($parent_id !== NULL) {
        foreach ($this->geoHierarchyManager->getChildren($parent_id) as $child) {
          $options[$child->id()] = $child->label();
        }
      }

      $selected_value = $selected_by_type[$type] ?? NULL;
      $element['hierarchy'][$type] = [
        '#type' => 'select',
        '#title' => $this->t($label),
        '#options' => $options,
        '#empty_option' => $this->t('- Select -'),
        '#default_value' => $selected_value,
        '#ajax' => [
          'callback' => [$this, 'ajaxRefresh'],
          'wrapper' => $wrapper_id,
          'event' => 'change',
        ],
      ];

      $parent_id = $selected_value ?: NULL;
    }

    $element['target_id'] = [
      '#type' => 'hidden',
      '#value' => $this->getLowestSelectedId($selected_by_type),
    ];

    return $element;
  }

  /**
   * Ajax callback to refresh the widget.
   */
  public function ajaxRefresh(array &$form, FormStateInterface $form_state): array {
    $trigger = $form_state->getTriggeringElement();
    $parents = $trigger['#array_parents'];
    $container_parents = array_slice($parents, 0, 3);

    return NestedArray::getValue($form, $container_parents);
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    foreach ($values as &$value) {
      $hierarchy = $value['hierarchy'] ?? [];
      $selected = array_filter($hierarchy, static fn($value) => (int) $value > 0);
      $value['target_id'] = $this->getLowestSelectedId($selected);
    }

    return $values;
  }

  /**
   * Determines the lowest selected ID based on the hierarchy order.
   *
   * @param array $selected_by_type
   *   The selected values keyed by type.
   *
   * @return int|null
   *   The lowest selected geo node ID.
   */
  private function getLowestSelectedId(array $selected_by_type): ?int {
    $selected = NULL;
    foreach (self::LEVELS as $type => $label) {
      if (!empty($selected_by_type[$type])) {
        $selected = (int) $selected_by_type[$type];
      }
    }

    return $selected ?: NULL;
  }

}
