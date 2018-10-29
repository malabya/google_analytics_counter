<?php

namespace Drupal\google_analytics_counter\Plugin\Field\FieldWidget;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldWidget(
 *   id = "google_analytics_counter",
 *   module = "google_analytics_counter",
 *   label = @Translation("Google Analytics Counter"),
 *   field_types = {
 *     "google_analytics_counter"
 *   }
 * )
 */
class GoogleAnalyticsCounterWidget extends WidgetBase {

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
      '#default_value' => $path,
      '#size' => 11,
      '#maxlength' => 11,
      '#disabled' => TRUE,
    ];
    return ['value' => $element];
  }

}