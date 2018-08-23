<?php

namespace Drupal\Tests\google_analytics_counter\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the google analytics counter authentication settings form.
 *
 * @group google_analytics_counter
 */
class GoogleAnalyticsCounterAuthSettingsTest extends BrowserTestBase {
  const ADMIN_AUTH_SETTINGS_PATH = 'admin/config/system/google-analytics-counter/authentication';

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
    $queue->createItem([$this->randomMachineName() => $this->randomMachineName()]);

    $this->drupalGet(self::ADMIN_AUTH_SETTINGS_PATH);
    $this->assertSession()->statusCodeEquals(200);

    // Assert Fields.
    $assert = $this->assertSession();
    $assert->fieldExists('client_id');
    $assert->fieldExists('client_secret');
    $assert->fieldExists('redirect_uri');
    $assert->fieldExists('project_name');

    $edit = [
      'client_id' => $this->randomMachineName(),
      'client_secret' => $this->randomMachineName(),
      'redirect_uri' => $this->randomMachineName(),
      'project_name' => $this->randomMachineName(),
    ];

    // Post form. Assert response.
//    $this->submitForm($edit, t('Save configuration'), 'google_analytics_counter_admin_auth');
    $this->submitForm($edit, t('Save configuration'));
//    $assert->pageTextContains(t('The configuration options have been saved.'));
  }

}
