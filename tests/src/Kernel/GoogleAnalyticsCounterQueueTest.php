<?php

namespace Drupal\Tests\google_analytics_counter\Kernel;

use Drupal\Tests\system\Kernel\System\CronQueueTest;
use Drupal\ultimate_cron\CronJobInterface;
use Drupal\ultimate_cron\Entity\CronJob;

/**
 * Update feeds on cron.
 *
 * @group google_analytics_counter
 */
class GoogleAnalyticsCounterQueueTest extends CronQueueTest {
  // Based on UltimateCronQueueTest.php

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('google_analytics_counter');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    module_load_install('google_analytics_counter');
  }

  /**
   * {@inheritdoc}
   */
  public function testExceptions() {
    // Get the queue to test the the queue worker.
    $queue = $this->container->get('queue')->get('google_analytics_counter_worker');

    // Enqueue an item for processing.
    $queue->createItem(['type' => 'fetch', 'index' => 0]);
    $queue->createItem(['type' => 'count', 'nid' => 1]);

    // Run cron; the worker for this queue should throw an exception and handle
    // it.
    $this->cron->run();

    // Expire the queue item manually. system_cron() relies in REQUEST_TIME to
    // find queue items whose expire field needs to be reset to 0. This is a
    // Kernel test, so REQUEST_TIME won't change when cron runs.
    // @see system_cron()
    // @see \Drupal\Core\Cron::processQueues()

    $query = $this->connection->update('queue');
    $query->condition('name', 'google_analytics_counter_worker');
    $query->fields(['expire' => REQUEST_TIME - 1]);
    $query->execute();

    $query = $this->connection->select('queue', 'q');
    $query->fields('q', ['name']);
    $query->condition('q.name', 'google_analytics_counter_worker');
    $all = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    self::assertEquals(count($all), 2, "All items in the queue have been processed");


    // Call the cron from the  .
//    google_analytics_counter_cron();

//    $this->cron->run();
//    $queue = $this->container->get('queue')->get('cron_queue_test_broken_queue');
  }


}
