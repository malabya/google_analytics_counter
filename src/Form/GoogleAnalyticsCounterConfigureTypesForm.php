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
use Drupal\Core\Config\ConfigFactoryInterface;


/**
 * The form for editing content types with the custom google analytics counter field.
 *
 * @internal
 */
class GoogleAnalyticsCounterConfigureTypesForm extends ConfigFormBase {

  /**
   * Config Factory Service Object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
  public function __construct(ConfigFactoryInterface $config_factory, MessengerInterface $messenger, GoogleAnalyticsCounterManagerInterface $manager) {
    parent::__construct($config_factory);
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
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

    // Add a checkbox to determine whether the storage for the custom field should be removed.
    $form['gac_custom_field_storage_status'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom field storage information'),
      '#open' => TRUE,
    ];
    $form['gac_custom_field_storage_status']['gac_type_remove_storage'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove the custom field'),
      '#description' => $this->t('Removes the custom Google Analytics Counter field from the system completely.'),
      '#default_value' => $config->get("general_settings.gac_type_remove_storage"),
    ];

    // Add a checkbox field for each content type.
    $form['gac_content_types'] = [
      '#type' => 'details',
      '#title' => $this->t('Content types'),
      '#open' => TRUE,
    ];
    $content_types = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
    foreach ($content_types as $machine_name => $content_type) {
      $form['gac_content_types']["gac_type_$machine_name"] = [
        '#type' => 'checkbox',
        '#title' => $content_type->label(),
        '#default_value' => $config->get("general_settings.gac_type_$machine_name"),
        '#states' => [
          'disabled' => [
            ':input[name="gac_type_remove_storage"]' => ['checked' => TRUE],
          ],
        ],

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

    // Save the remove_storage configuration.
    $config
      ->set('general_settings.gac_type_remove_storage', $values['gac_type_remove_storage'])
      ->save();

    // Loop through each content type. Add/subtract or do nothing to the content type.
    foreach ($values as $key => $value) {
      if ($key == 'gac_type_remove_storage') {
        continue;
      }

      // Get the NodeTypeInterface $type.
      $type = \Drupal::service('entity.manager')
        ->getStorage('node_type')
        ->load(substr($key, 9));

      // Add the field to the content type if the field has been checked.
      if ($values['gac_type_remove_storage'] == FALSE && $value == 1) {
        $this->manager->gacAddField($type);

        // Update the gac_type_ configuration.
        $config_factory->getEditable('google_analytics_counter.settings')
          ->set("general_settings.$key", $value)
          ->save();
      }
      else {
        if ($values['gac_type_remove_storage'] = TRUE && $value == 1) {
          $this->manager->gacDeleteField($type);

          // Update the gac_type_ configuration.
          $config_factory->getEditable('google_analytics_counter.settings')
            ->set("general_settings.$key", NULL)
            ->save();
        }

        // Delete the field for the type if it is unchecked.
        // If no types are checked, the field storage is removed.
        else {
          $this->manager->gacDeleteField($type);

          // Update the gac_type_ configuration.
          $config_factory->getEditable('google_analytics_counter.settings')
            ->set("general_settings.$key", NULL)
            ->save();
        }
      }
    }
  }

}