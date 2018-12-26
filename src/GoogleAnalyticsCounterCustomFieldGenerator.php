<?php

namespace Drupal\google_analytics_counter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\NodeTypeInterface;
use Psr\Log\LoggerInterface;

/**
 * Defines the Google Analytics Counter custom field generator.
 *
 * @package Drupal\google_analytics_counter
 */
class GoogleAnalyticsCounterCustomFieldGenerator implements GoogleAnalyticsCounterCustomFieldGeneratorInterface {

  /**
   * The table for the node__field_google_analytics_counter storage.
   */
  const TABLE = 'node__field_google_analytics_counter';

  /**
   * The google_analytics_counter.settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Drupal\google_analytics_counter\GoogleAnalyticsCounterCustomFieldGeneratorInterface.
   *
   * @var \Drupal\google_analytics_counter\GoogleAnalyticsCounterCustomFieldGeneratorInterface
   */
  protected $customField;

  /**
   * Constructs a GoogleAnalyticsCounterCustomFieldGenerator object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Connection $connection, LoggerInterface $logger, MessengerInterface $messenger) {
    $this->config = $config_factory->get('google_analytics_counter.settings');
    $this->connection = $connection;
    $this->logger = $logger;
    $this->messenger = $messenger;
  }

  /****************************************************************************/
  // Custom field generation functions.
  /****************************************************************************/

  /**
   * Prepares to add the custom field and saves the configuration.
   *
   * @param $type
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param $key
   * @param $value
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function gacPreAddField($type, $config_factory, $key, $value) {
    $this->gacAddField($type);

    // Update the gac_type_{content_type} configuration.
    $config_factory->getEditable('google_analytics_counter.settings')
      ->set("general_settings.$key", $value)
      ->save();
  }

  /**
   * Adds the checked the fields.
   *
   * @param \Drupal\node\NodeTypeInterface $type
   *   A node type entity.
   * @param string $label
   *   The formatter label display setting.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\field\Entity\FieldConfig|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function gacAddField(NodeTypeInterface $type, $label = 'Google Analytics Counter') {

    // Check if field storage exists.
    $config = FieldStorageConfig::loadByName('node', 'field_google_analytics_counter');
    if (!isset($config)) {
      // Obtain configuration from yaml files
      $config_path = 'modules/contrib/google_analytics_counter/config/optional';
      $source = new FileStorage($config_path);

      // Obtain the storage manager for field storage bases.
      // Create the new field configuration from the yaml configuration and save.
      \Drupal::entityTypeManager()->getStorage('field_storage_config')
        ->create($source->read('field.storage.node.field_google_analytics_counter'))
        ->save();
    }

    // Add the checked fields.
    $field_storage = FieldStorageConfig::loadByName('node', 'field_google_analytics_counter');
    $field = FieldConfig::loadByName('node', $type->id(), 'field_google_analytics_counter');
    if (empty($field)) {
      $field = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $type->id(),
        'label' => $label,
        'description' => t('This field stores Google Analytics pageviews.'),
        'field_name' => 'field_google_analytics_counter',
        'entity_type' => 'node',
        'settings' => array('display_summary' => TRUE),
      ]);
      $field->save();

      // Assign widget settings for the 'default' form mode.
      entity_get_form_display('node', $type->id(), 'default')
        ->setComponent('google_analytics_counter', array(
          'type' => 'textfield',
          '#maxlength' => 255,
          '#default_value' => 0,
          '#description' => t('This field stores Google Analytics pageviews.'),
        ))
        ->save();

      // Assign display settings for the 'default' and 'teaser' view modes.
      entity_get_display('node', $type->id(), 'default')
        ->setComponent('google_analytics_counter', array(
          'label' => 'hidden',
          'type' => 'textfield',
        ))
        ->save();

      // The teaser view mode is created by the Standard profile and therefore
      // might not exist.
      $view_modes = \Drupal::entityManager()->getViewModes('node');
      if (isset($view_modes['teaser'])) {
        entity_get_display('node', $type->id(), 'teaser')
          ->setComponent('google_analytics_counter', array(
            'label' => 'hidden',
            'type' => 'textfield',
          ))
          ->save();
      }
    }

    return $field;
  }

  /**
   * Prepares to delete the custom field and saves the configuration.
   *
   * @param $type
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param $key
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function gacPreDeleteField($type, $config_factory, $key) {
    $this->gacDeleteField($type);

    // Update the gac_type_{content_type} configuration.
    $config_factory->getEditable('google_analytics_counter.settings')
      ->set("general_settings.$key", NULL)
      ->save();
  }

  /**
   * Deletes the unchecked field configurations.
   *
   * @param \Drupal\node\NodeTypeInterface $type
   *   A node type entity.
   *
   * @return null|void
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @see GoogleAnalyticsCounterConfigureTypesForm
   */
  public function gacDeleteField(NodeTypeInterface $type) {
    // Check if field exists on the content type.
    $content_type = $type->id();
    $config = FieldConfig::loadByName('node', $content_type, 'field_google_analytics_counter');
    if (!isset($config)) {
      return NULL;
    }
    // Delete the field from the content type.
    FieldConfig::loadByName('node', $content_type, 'field_google_analytics_counter')->delete();
  }

  /****************************************************************************/
  // Custom field update functions.
  /****************************************************************************/

  /**
   * Update the Google Analytics Counter custom field with profile_id pageviews.
   *
   * @param $nid
   *   The node ID that has been read.
   * @param $sum_of_pageviews
   *   Count of pageviews via the hash of the paths.
   * @param $bundle
   *   The drupal content type
   * @param $vid
   *   The revision ID of the node that has been read
   *
   * @throws \Exception
   */
  public function gacUpdateCustomField($nid, $sum_of_pageviews, $bundle, $vid) {
    // Update the Google Analytics Counter field if it exists.
    if (!$this->connection->schema()->tableExists(static::TABLE)) {
      return;
    }

    // Todo: This can be faster by adding only the bundles that have been selected.
    $query = $this->connection->select('node__field_google_analytics_counter', 'n');
    $query->fields('n', ['entity_id']);
    $query->condition('entity_id', $nid);
    $entity_id = $query->execute()->fetchField();

    if ($entity_id) {
      $this->connection->update('node__field_google_analytics_counter')
        ->fields([
          'bundle' => $bundle,
          'deleted' => 0,
          'entity_id' => $nid,
          'revision_id' => $vid,
          'langcode' => 'en',
          'delta' => 0,
          'field_google_analytics_counter_value' => $sum_of_pageviews,
        ])
        ->condition('entity_id', $entity_id)
        ->execute();
    }
    else {
      $this->connection->insert('node__field_google_analytics_counter')
        ->fields([
          'bundle' => $bundle,
          'deleted' => 0,
          'entity_id' => $nid,
          'revision_id' => $vid,
          'langcode' => 'en',
          'delta' => 0,
          'field_google_analytics_counter_value' => $sum_of_pageviews,
        ])
        ->execute();
    }
  }

}
