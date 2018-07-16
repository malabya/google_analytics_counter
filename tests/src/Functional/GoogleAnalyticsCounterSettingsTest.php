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
    $this->assertResponse(200, 'Access granted to settings page.');

    // Assert Fields.
    $settings_fields = $this->getAdminUserSettingsFields();
    foreach ($settings_fields as $field_name) {
      $this->assertField($field_name, SafeMarkup::format('@field_name field exists.', ['@field_name' => $field_name]));
    }

    // Cron Settings.
    $edit = [
      'cron_interval' => 0,
      'chunk_to_fetch' => 5000,
      'api_dayquota' => 10000,
      'cache_length' => 24,
    ];

    // Enable counter on content view.
    $this->drupalPostForm(self::ADMIN_SETTINGS_PATH, $edit, t('Save configuration'));
    $this->assertRaw('The configuration options have been saved.');
  }

  /**
   * Returns a list containing the admin settings fields.
   */
  protected function getAdminUserSettingsFields() {
    return [
      'cron_interval',
      'chunk_to_fetch',
      'api_dayquota',
      'cache_length',
      'queue_time',
      'start_date',
      'advanced_date_checkbox',
      'fixed_start_date',
      'fixed_end_date',
    ];
  }

}
