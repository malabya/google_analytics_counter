<?php

namespace Drupal\google_analytics_counter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;

/**
 * Defines the Google Analytics Counter message manager.
 *
 * @package Drupal\google_analytics_counter
 */
class GoogleAnalyticsCounterMessageManager implements GoogleAnalyticsCounterMessageManagerInterface {

  use StringTranslationTrait;

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
   * The state where all the tokens are saved.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

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
   *
   * Constructs a GoogleAnalyticsCounterMessageManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state of the drupal site.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory, Connection $connection, StateInterface $state, LoggerInterface $logger, MessengerInterface $messenger) {
    $this->config = $config_factory->get('google_analytics_counter.settings');
    $this->connection = $connection;
    $this->state = $state;
    $this->logger = $logger;
    $this->messenger = $messenger;
  }

  /**
   * Prints a warning message when not authenticated.
   *
   * @param $build
   *
   */
  public function notAuthenticatedMessage($build = []) {
    $t_arg = [
      ':href' => Url::fromRoute('google_analytics_counter.admin_auth_form', [], ['absolute' => TRUE])
        ->toString(),
      '@href' => 'Authentication',
    ];
    $this->messenger->addWarning(t('Google Analytics have not been authenticated! Google Analytics Counter cannot fetch any new data. Please authenticate with Google from the <a href=:href>@href</a> page.', $t_arg));

    // Revoke Google authentication.
    $this->revokeAuthenticationMessage($build);
  }

  /**
   * Revoke Google Authentication Message.
   *
   * @param $build
   * @return mixed
   */
  public function revokeAuthenticationMessage($build) {
    $t_args = [
      ':href' => Url::fromRoute('google_analytics_counter.admin_auth_revoke', [], ['absolute' => TRUE])
        ->toString(),
      '@href' => 'revoking Google authentication',
    ];
    $build['cron_information']['revoke_authentication'] = [
      '#markup' => t("If there's a problem with OAUTH authentication, try <a href=:href>@href</a>.", $t_args),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    return $build;
  }

  /**
   * Returns the link with the Google project name if it is available.
   *
   * @return string
   *   Project name.
   */
  public function googleProjectName() {
    $config = $this->config;
    $project_name = !empty($config->get('general_settings.project_name')) ?
      Url::fromUri('https://console.developers.google.com/apis/api/analytics.googleapis.com/quotas?project=' . $config->get('general_settings.project_name'))
        ->toString() :
      Url::fromUri('https://console.developers.google.com/apis/api/analytics.googleapis.com/quotas')
        ->toString();

    return $project_name;
  }

  /**
   * Get the Profile name of the Google view from Drupal.
   *
   * @param string $profile_id
   *   The profile id used in the google query.
   *
   * @return string mixed
   */
  public function getProfileName($profile_id) {

    $profile_id = $this->state->get('google_analytics_counter.total_pageviews_' . $profile_id);
    if (!empty($profile_id)) {
      $profile_name = '<strong>' . $profile_id[key($profile_id)] . '</strong>';
    }
    else {
      $profile_name = '<strong>' . $this->t('(Profile name to come)') . '</strong>';
    }
    return $profile_name;
  }

  /**
   * Get the the top twenty results for pageviews and pageview_totals.
   *
   * @param string $table
   *   The table from which the results are selected.
   *
   * @return mixed
   */
  public function getTopTwentyResults($table) {
    $query = $this->connection->select($table, 't');
    $query->range(0, 20);
    $rows = [];
    switch ($table) {
      case 'google_analytics_counter':
        $query->fields('t', ['pagepath', 'pageviews']);
        $query->orderBy('pageviews', 'DESC');
        $result = $query->execute()->fetchAll();
        $rows = [];
        foreach ($result as $value) {
          $rows[] = [
            $value->pagepath,
            $value->pageviews,
          ];
        }
        break;
      case 'google_analytics_counter_storage':
        $query->fields('t', ['nid', 'pageview_total']);
        $query->orderBy('pageview_total', 'DESC');
        $result = $query->execute()->fetchAll();
        foreach ($result as $value) {
          $rows[] = [
            $value->nid,
            $value->pageview_total,
          ];
        }
        break;
      default:
        break;
    }

    return $rows;
  }


  /**
   * Voluminous on screen instructions about authentication.
   *
   * @param $web_properties
   *
   * @return string
   */
  public function authenticationInstructions($web_properties) {
    $t_arg = [
      ':href' => Url::fromRoute('google_analytics_counter.admin_dashboard_form', [], ['absolute' => TRUE])
        ->toString(),
      '@href' => 'Dashboard',
    ];
    $markup_description = ($web_properties !== 'Unauthenticated') ?
      '<ol><li>' . $this->t('Fill in your Client ID, Client Secret, Authorized Redirect URI, and Google Project Name.') .
      '</li><li>' . $this->t('Save configuration.') .
      '</li><li>' . $this->t('Click Authenticate in Authenticate with Google Analytics above.') .
      '</li><ul><li>' . $this->t('If you don\'t already have Google Analytics set up in Google, follow the instructions in the README.md included with this module.') .
      '</li><li>' . $this->t('After setting up Google Analytics, come back to this page and click the Authenticate button above.') .
      '</li></ul><li>' . $this->t('If authentication with Google is successful, the ') . '<strong>' . $this->t(' Google View ') . '</strong>' . $this->t('field will list your analytics profiles.') .
      '</li><li>' . $this->t('Select an analytics profile to collect analytics from and click Save configuration.') .
      '</li><ul><li>' . $this->t('If you are not authenticated or if the project you are authenticating to does not have Analytics, No options are available.') .
      '</strong>.</li></ul></ol></p>' .
      '</li></ul></ol></p>' :
      '<p>' . $this->t('Client ID, Client Secret, and Authorized redirect URI can only be changed when not authenticated.') .
      '<ol><li>' . $this->t('Now that you are authenticated with Google Analytics, select the') . '<strong>' . $this->t(' Google Views ') . '</strong>' . $this->t('to collect analytics from and click Save configuration.') .
      '</li><li>' . $this->t('Save configuration.') .
      '</li><li>' . $this->t('On the next cron job, analytics from the Google View field and the Additional Google Views field will be saved to Drupal.') .
      '</li><ul><li>' . $this->t('Information on the <a href=:href>@href</a> page is derived from the Google View field, not the Additional Google Views field.', $t_arg) .
      '</li><li>' . $this->t('After cron runs, check pageviews for all selected Google Views on the <a href=:href>@href</a>  page in the Top Twenty Results section.', $t_arg) .
      '</li></ul></ol></p>';

    return $markup_description;
  }

}
//       '#description' => $this->t("Choose a Google Analytics view. If you are not authenticated or if the project you are authenticating to does not have Analytics, No options are available."),
