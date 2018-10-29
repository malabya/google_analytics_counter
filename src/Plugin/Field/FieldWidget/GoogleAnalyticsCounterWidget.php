<?php

namespace Drupal\google_analytics_counter\Plugin\Field\FieldWidget;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\google_analytics_counter\GoogleAnalyticsCounterManagerInterface;




/**
 * @FieldWidget(
 *   id = "google_analytics_counter",
 *   module = "google_analytics_counter",
 *   label = @Translation("Google Analytics Counter"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class GoogleAnalyticsCounterWidget extends WidgetBase implements ContainerFactoryPluginInterface {

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
   *   Field settings.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\google_analytics_counter\GoogleAnalyticsCounterManagerInterface $manager
   *   Google Analytics Counter Manager object.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, CurrentPathStack $current_path, GoogleAnalyticsCounterManagerInterface $manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->currentPath = $current_path;
    $this->manager = $manager;
  }

  /**s
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('path.current'),
      $container->get('google_analytics_counter.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $path = '';
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof \Drupal\node\NodeInterface) {
      // You can get nid and anything else you need from the node object.
      $nid = $node->id();
      $path = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $nid);
    }


//    $get_path = \Drupal::service('path.alias_manager')->getPath();

    $element += [
      '#type' => 'textfield',
      '#title' => t('Pageviews'),
      '#default_value' => $this->manager->displayGaCount($this->currentPath->getPath()),
      '#size' => 11,
      '#maxlength' => 11,
      '#disabled' => TRUE,
    ];
    return ['value' => $element];
  }

}