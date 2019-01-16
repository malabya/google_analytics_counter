<?php

namespace Drupal\google_analytics_counter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class GoogleAnalyticsAuthManager.
 *
 * Sets the auth methods.
 *
 * @package Drupal\google_analytics_counter
 */
class GoogleAnalyticsCounterAuthManager implements GoogleAnalyticsCounterAuthManagerInterface {

  use StringTranslationTrait;

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
   * Constructs a Google Analytics Counter object.
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
   * Check to make sure we are authenticated with google.
   *
   * @return bool
   *   True if there is a refresh token set.
   */
  public function isAuthenticated() {
    return $this->state->get('google_analytics_counter.access_token') != NULL ? TRUE : FALSE;
  }

  /**
   * Begin authentication to Google authentication page with the client_id.
   */
  public function beginGacAuthentication() {
    global $base_url;
    $current_path = \Drupal::service('path.current')->getPath();
    $uri = \Drupal::service('path.alias_manager')->getAliasByPath($current_path);
    $redirect_uri = $base_url . $uri;

    $gafeed = new GoogleAnalyticsCounterFeed();
    $gafeed->beginAuthentication($this->config->get('general_settings.client_id'), $redirect_uri);
  }

  /**
   * Instantiate a new GoogleAnalyticsCounterFeed object.
   *
   * @return object
   *   GoogleAnalyticsCounterFeed object to authorize access and request data
   *   from the Google Analytics Core Reporting API.
   */
  public function newGaFeed() {
    global $base_url;
    $config = $this->config;

    // If the access token is still valid, return an authenticated GAFeed.
    if ($this->state->get('google_analytics_counter.access_token') && time() < $this->state->get('google_analytics_counter.expires_at')) {
      return new GoogleAnalyticsCounterFeed($this->state->get('google_analytics_counter.access_token'));
    }
    // If the site has an access token and refresh token, but the access
    // token has expired, authenticate the user with the refresh token.
    elseif ($this->state->get('google_analytics_counter.refresh_token')) {
      $client_id = $config->get('general_settings.client_id');
      $client_secret = $config->get('general_settings.client_secret');
      $refresh_token = $this->state->get('google_analytics_counter.refresh_token');
      try {
        $gac_feed = new GoogleAnalyticsCounterFeed();
        $gac_feed->refreshToken($client_id, $client_secret, $refresh_token);
        $this->state->setMultiple([
          'google_analytics_counter.access_token' => $gac_feed->accessToken,
          'google_analytics_counter.expires_at' => $gac_feed->expiresAt,
        ]);
        return $gac_feed;
      }
      catch (Exception $e) {
        $this->messenger->addError($this->t('There was an authentication error. Message: %message', ['%message' => $e->getMessage()]));
        return NULL;
      }
    }
    // If there is no access token or refresh token and client is returned
    // to the config page with an access code, complete the authentication.
    elseif (isset($_GET['code'])) {
      try {
        $current_path = \Drupal::service('path.current')->getPath();
        $uri = \Drupal::service('path.alias_manager')->getAliasByPath($current_path);
        $redirect_uri = $base_url . $uri;

        $gac_feed = new GoogleAnalyticsCounterFeed();
        $gac_feed->finishAuthentication($config->get('general_settings.client_id'), $config->get('general_settings.client_secret'), $redirect_uri);

        $this->state->setMultiple([
          'google_analytics_counter.access_token' => $gac_feed->accessToken,
          'google_analytics_counter.expires_at' => $gac_feed->expiresAt,
          'google_analytics_counter.refresh_token' => $gac_feed->refreshToken,
        ]);

        $this->messenger->addStatus($this->t('You have been successfully authenticated.'), FALSE);

      }
      catch (Exception $e) {
        $this->messenger->addError($this->t('There was an authentication error. Message: %message', ['%message' => $e->getMessage()]));
        return NULL;
      }
    }

    return NULL;
  }

  /**
   * Get the list of available web properties.
   *
   * @return array
   *   Array of options.
   */
  public function getWebPropertiesOptions() {
    // When not authenticated, the only option is 'Unauthenticated'.
    $feed = $this->newGaFeed();
    if (empty($feed->response)) {
      $options = ['unauthenticated' => 'Unauthenticated'];
      return $options;
    }

    // Get the profiles information from Google.
    $web_properties = $feed->queryWebProperties()->results->items;
    $profiles = $feed->queryProfiles()->results->items;

    $options = [];
    // Add options for each web property.
    if (!empty($profiles)) {
      foreach ($profiles as $profile) {
        $webprop = NULL;
        foreach ($web_properties as $web_property) {
          if ($web_property->id == $profile->webPropertyId) {
            $webprop = $web_property;
            break;
          }
        }

        $options[$webprop->name][$profile->id] = $profile->name . ' (' . $profile->id . ')';
      }
    }
    return $options;
  }

}
