<?php

namespace Drupal\Tests\google_analytics_counter\Functional;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the google analytics counter settings form.
 *
 * @group statistics
 */
class GoogleAnalyticsCounterSettingsTest extends BrowserTestBase {
  const ADMIN_SETTINGS_PATH = 'admin/config/system/google-analytics-counter';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['google_analytics_counter'];

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Verifies that the google analytics counter settings page works.
   *
   * @see MediaSourceTest for good example code.
   */
  public function testForm() {
    $admin_user = $this->drupalCreateUser(array(
      'administer site configuration',
      'administer google analytics counter',
    ));
    $this->drupalLogin($admin_user);

    // Create item(s) in the queue.
    $queue_name = 'google_analytics_counter_worker';
    $queue = \Drupal::queue($queue_name);

    // Enqueue an item for processing.
    $queue->createItem(array($this->randomMachineName() => $this->randomMachineName()));

    $this->drupalGet(self::ADMIN_SETTINGS_PATH);
    $this->assertSession()->statusCodeEquals(200);

    // Assert Fields.
    $assert_session = $this->assertSession();
    $assert_session->fieldExists('cron_interval');
    $assert_session->fieldExists('chunk_to_fetch');
    $assert_session->fieldExists('api_dayquota');
    $assert_session->fieldExists('cache_length');
    $assert_session->fieldExists('queue_time');
    $assert_session->fieldExists('start_date');
    $assert_session->fieldExists('advanced_date_checkbox');
    $assert_session->fieldExists('fixed_start_date');
    $assert_session->fieldExists('fixed_end_date');

    // Cron Settings.
    $edit = [
      'cron_interval' => 0,
      'chunk_to_fetch' => 5000,
      'api_dayquota' => 10000,
      'cache_length' => 24,
    ];

    // Post form. Assert response.
    $this->drupalPostForm(self::ADMIN_SETTINGS_PATH, $edit, t('Save configuration'));
    $this->assertSession()->responseContains('The configuration options have been saved.');
  }

}
