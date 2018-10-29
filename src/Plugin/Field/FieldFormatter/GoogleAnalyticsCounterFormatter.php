<?php

namespace Drupal\google_analytics_counter\Plugin\Field\FieldFormatter;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\google_analytics_counter\GoogleAnalyticsCounterManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;



/**
 * Plugin implementation of the 'field_google_analytics_counter' formatter.
 *
 * @FieldFormatter(
 *   id = "field_google_analytics_counter",
 *   module = "google_analytics",
 *   label = @Translation("Google Analytics Counter"),
 *   field_types = {
 *     "field_google_analytics_counter"
 *   }
 * )
 */
class GoogleAnalyticsCounterFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Drupal\google_analytics_counter\GoogleAnalyticsCounterManager definition.
   *
   * @var \Drupal\google_analytics_counter\GoogleAnalyticsCounterManagerInterface
   */
  protected $manager;

  /**
   * Create an instance of GoogleAnalyticsCounterFormatter.
   *
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Field definition.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.

   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\google_analytics_counter\GoogleAnalyticsCounterManagerInterface $manager
   *   Google Analytics Counter Manager object.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, CurrentPathStack $current_path, GoogleAnalyticsCounterManagerInterface $manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentPath = $current_path;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('path.current'),
      $container->get('google_analytics_counter.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        // We create a render array to produce the desired markup,
        // "<p style="color: #hexcolor">The color code ... #hexcolor</p>".
        // See theme_html_tag().
        '#type' => 'html_tag',
        '#tag' => 'p',
//        '#attributes' => [
//          'style' => 'color: ' . $item->value,
//        ],
        '#value' => $item->value,
      ];
    }

    return $elements;
  }

}
