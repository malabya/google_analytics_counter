<?php

namespace Drupal\google_analytics_counter;


use Drupal\node\NodeTypeInterface;

/**
 * Google Analytics Counter custom field methods.
 */
interface GoogleAnalyticsCounterCustomFieldGeneratorInterface {

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
  public function gacPreAddField($type, $config_factory, $key, $value);

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
  public function gacAddField(NodeTypeInterface $type, $label = 'Google Analytics Counter');

  /**
   * Prepares to delete the custom field and saves the configuration.
   *
   * @param $type
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param $key
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function gacPreDeleteField($type, $config_factory, $key);

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
  public function gacDeleteField(NodeTypeInterface $type);

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
  public function gacUpdateCustomField($nid, $sum_of_pageviews, $bundle, $vid);
}