<?php

namespace Drupal\google_analytics_counter\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base functionality for Google Analytics Counter workers.
 *
 * @see See https://www.drupal.org/forum/support/module-development-and-code-questions/2017-03-20/queue-items-not-processed
 * @see https://drupal.stackexchange.com/questions/206838/documentation-or-tutorial-on-using-batch-or-queue-services-api-programmatically
 */
abstract class GoogleAnalyticsCounterQueueBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The table for the node__field_google_analytics_counter storage.
   */
  const TABLE = 'node__field_google_analytics_counter';


  // Here we don't use the Dependency Injection,
  // but the __construct() and create() methods are necessary.

  /**
   * {@inheritdoc}
   */
  public function __construct() {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if ($data['type'] == 'fetch') {
      \Drupal::service('google_analytics_counter.manager')->updatePathCounts($data['index']);
    }
    elseif ($data['type'] == 'count') {
      \Drupal::service('google_analytics_counter.manager')->updateStorage($data['nid']);

      // Update the Google Analytics Counter field if it exists.
      $db_connection = \Drupal::database();
      if (!$db_connection->schema()->tableExists(static::TABLE)) {
        return;
      }

      // Get the node from the nid. (Could be expensive).
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($data['nid']);

//      \Drupal::service('google_analytics_counter.manager')->updatePathCounts($data['index']);

        $db_connection->merge('node__field_google_analytics_counter')
        ->key('entity_id', $data['nid'])
        ->fields([
          'bundle' => $node->bundle(),
          'deleted' => 0,
          'entity_id' => $data['nid'],
          'revision_id' => $data['nid'],
          'langcode' => 'en',
          'delta' => 0,
          'field_google_analytics_counter_value' => 16,
        ])
        ->execute();
    }
  }

}
