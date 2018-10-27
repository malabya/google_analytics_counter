<?php

namespace Drupal\google_analytics_counter\Plugin\Field\FieldFormatter;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\google_analytics_counter\GoogleAnalyticsCounterManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @FieldFormatter(
 *   id = "google_analytics_counter_default",
 *   label = @Translation("Google Analytics Counter"),
 *   field_types = {
 *     "google_analytics_counter"
 *   }
 * )
 */
class GoogleAnalyticsCounterDefaultFormatter extends FormatterBase {

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Drupal\google_analytics_counter\GoogleAnalyticsCounterManager definition.
   *
   * @var \Drupal\google_analytics_counter\GoogleAnalyticsCounterManager
   */
  protected $manager;
  /**
   * Constructs a new SiteMaintenanceModeForm.
   *
   * @param \Drupal\google_analytics_counter\GoogleAnalyticsCounterManager $manager
   *   Google Analytics Counter Manager object.
   */
  public function __construct(CurrentPathStack $current_path, GoogleAnalyticsCounterManager $manager) {

    $this->currentPath = $current_path;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path.current'),
      $container->get('google_analytics_counter.manager')
    );
  }

  /**
   * Builds a renderable array for a field value.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be rendered.
   * @param string $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   A renderable array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $countries = \Drupal::service('country_manager')->getList();
    foreach ($items as $delta => $item) {
      if (isset($countries[$item->value])) {
        $elements[$delta] = [
          '#type' => 'markup',
          '#markup' => '<h1>' . $countries[$item->value] . '</h1>',
        ];
      }
    }

    return $elements;
  }
}