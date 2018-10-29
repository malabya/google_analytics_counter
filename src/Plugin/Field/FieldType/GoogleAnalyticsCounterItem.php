<?php

namespace Drupal\google_analytics_counter\Plugin\Field\FieldType;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'field_google_analytics_counter' field type.
 *
 * @FieldType(
 *   id = "field_google_analytics_counter",
 *   label = @Translation("Google Analytics Counter"),
 *   module = "google_analytics_counter",
 *   description = @Translation("Demonstrates a field composed of an RGB color."),
 *   default_widget = "field_google_analytics_counter",
 *   default_formatter = "field_google_analytics_counter"
 * )
 */
class GoogleAnalyticsCounterItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'description' => t('Google Analytics Counter'),
          'length' => 11,
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Google Analytics Counter'));

    return $properties;
  }

}
