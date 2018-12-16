<?php

namespace Drupal\google_analytics_counter\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\google_analytics_counter\GoogleAnalyticsCounterManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * The form for editing content types with the custom google analytics counter field.
 *
 * @internal
 */
class GoogleAnalyticsCounterConfigureTypesForm extends ConfigFormBase {

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Drupal\google_analytics_counter\GoogleAnalyticsCounterManagerInterface.
   *
   * @var \Drupal\google_analytics_counter\GoogleAnalyticsCounterManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public function __construct(MessengerInterface $messenger, GoogleAnalyticsCounterManagerInterface $manager) {
    $this->messenger = $messenger;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('google_analytics_counter.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_analytics_counter_configure_types_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['google_analytics_counter.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {
    $config = $this->config('google_analytics_counter.settings');

//    $form['message'] = [
//      '#type' => 'html_tag',
//      '#tag' => 'h6',
//      '#value' => $this->t('If none are checked, the field storage is also removed, and the queue will run faster.'),
//    ];

    // Remove the storage for the custom field.
    $form["gac_type_remove_storage"] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Removes the custom field'),
      '#description' => $this->t('Removes all traces of the custom field from the system'),
      '#default_value' => $config->get("general_settings.gac_type_remove_storage"),
    ];

    // Add a checkbox field for each content type.
    $content_types = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
    foreach ($content_types as $machine_name => $content_type) {
      $form["gac_type_$machine_name"] = [
        '#type' => 'checkbox',
        '#title' => $content_type->label(),
        '#default_value' => $config->get("general_settings.gac_type_$machine_name"),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('google_analytics_counter.settings');
    $config_factory = \Drupal::configFactory();
    $values = $form_state->cleanValues()->getValues();

    // Save the remove storage configuration and then unset it so the
    // content types can be processed.
    $config
      ->set('general_settings.gac_type_remove_storage', $values['gac_type_remove_storage'])->save();
    unset($values['gac_type_remove_storage']);

    // Loop through each content type and add/subtract or do nothing to the content type.
    foreach ($values as $key => $value) {
      // Add the field to the content type if the field has been checked.
      $type = \Drupal::service('entity.manager')
        ->getStorage('node_type')
        ->load(substr($key, 9));
      if ($value == 1) {
        $this->manager->gacAddField($type);

        // Update the gac_type_ configuration.
        $config_factory->getEditable('google_analytics_counter.settings')
          ->set("general_settings.$key", $value)
          ->save();
      }

      // Delete the field for the type if it is unchecked.
      // If no types are checked, the field storage is removed.
      // The field can be added again by checking a type.
      else {
        $this->manager->gacDeleteField($type);

        // Update the gac_type_ configuration.
        $config_factory->getEditable('google_analytics_counter.settings')
          ->set("general_settings.$key", NULL)
          ->save();
      }
    }
  }

  /**
   * Route title callback.
   */
  public function getTitle() {
    return $this->t('Check the content types you wish to add the custom field to.');
  }

}