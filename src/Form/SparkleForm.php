<?php

namespace Drupal\sparkle_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystem;
use Drupal\quant\Plugin\QueueItem\RouteItem;

class SparkleForm extends FormBase {

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['appcast'] = [
            '#type' => 'managed_file',
            '#title' => $this->t('AppCast XML file'),
            '#description' => $this->t('Upload a new AppCast XML. The existing file will be replaced automatically.'),
            '#upload_location' => 'private://sparkle_staging',
            '#upload_validators' => [
                'file_validate_extensions' => ['xml'],
            ],
        ];

        $form['update'] = [
            '#type' => 'managed_file',
            '#title' => $this->t('Update package'),
            '#description' => $this->t('Update package. If an existing version exists it will be automatically replaced.'),
            '#upload_location' => 'private://sparkle_staging',
            '#upload_validators' => [
                'file_validate_extensions' => ['tar.xz'],
            ],
        ];

        $form['update_version'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Update Version'),
            '#description' => $this->t('Finalised package will be saved as "XIV on Mac version.tar.xz".'),
            '#required' => TRUE,
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Confirm Release'),
          '#button_type' => 'primary',
        ];

        return $form;
    }

    public function getFormId() {
        return 'sparkle_integration.release';
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        
        $batch = [
            'title' => $this->t('Processing'),
            'operations' => [],
            'init_message' => t('Initializing.'),
            'progress_message' => t('Estimated time: @estimate.'),
            'error_message' => t('The process has encountered an error.'),
        ];
        
        $appcast_op = [
            'op' => 'update_feed',
            'data' => $form_state
        ];

        $package_op = [
            'op' => 'copy_package',
            'data' => $form_state
        ];

        $hydrate_op = [
            'op' => 'hydrate_cdn',
            'data' => [
                '/sites/default/files/update_data/xivonmac_appcast.xml',
                //'/sites/default/files/update_data/XIVonMac' . $form_state->getValue('update_version') . '.tar.xz'
            ],
        ];
        
        $batch['operations'][] = [['\Drupal\sparkle_integration\Form\SparkleForm', 'dispatchRequest'], [$appcast_op]];
        $batch['operations'][] = [['\Drupal\sparkle_integration\Form\SparkleForm', 'dispatchRequest'], [$package_op]];
        $batch['operations'][] = [['\Drupal\sparkle_integration\Form\SparkleForm', 'dispatchRequest'], [$hydrate_op]];


        batch_set($batch);
    }

    public static function dispatchRequest($data, &$context) {
        switch ($data['op']) {
            case 'update_feed':
                self::update_feed($data['data']);
                break;
            case 'copy_package':
                self::copy_package($data['data']);
            case 'hydrate_cdn':
                self::hydrate_cdn($data['data']);
            default:
                break;
        }
    }

    private static function hydrate_cdn($urls) {
        foreach ($urls as $url) {
            try {
                $item = new RouteItem(['route' => $url]);
                $item->send();
                \Drupal::service('messenger')->addMessage('Successfully pushed ' . $url . ' to QuantCDN.');
            } catch (\Exception $e) {
                \Drupal::service('messenger')->addError('Error connecting to QuantCDN');
            }
        }
    }

    // Combine these
    private static function copy_package($form_state) {
        // Make sure the file system is in a sane state.
        $fs_driver = \Drupal::service('file_system');
        $directory = 'public://update_data';
        $fs_driver->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);

        $package_version = $form_state->getValue('update_version');
        $package_file_name = 'XIVonMac' . $package_version . '.tar.xz';
        $package_file_location = 'public://update_data/' . $package_file_name;

        $package = $form_state->getValue('update', 0);
        if (isset($package[0]) && !empty($package[0])) {
            $file = File::load($package[0]);
            $new_package_file_location = 'private://sparkle_staging/' . $file->getFilename();

            // Copy the new update package into place.
            copy($new_package_file_location, $package_file_location);

            // Clean up the temporary file.
            $file->delete();
            \Drupal::service('messenger')->addMessage("Move new file into place: " . $package_file_location);
        }
    }

    private static function update_feed($form_state) {
        // Make sure the file system is in a sane state.
        $fs_driver = \Drupal::service('file_system');
        $directory = 'public://update_data';
        $fs_driver->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);

        $appcast_file_name = 'xivonmac_appcast.xml';
        $appcast_file_location = 'public://update_data/' . $appcast_file_name;
        
        // Process files
        $appcast = $form_state->getValue('appcast', 0);
        if (isset($appcast[0]) && !empty($appcast[0])) {
            $file = File::load($appcast[0]);
            $new_appcast_file_location = 'private://sparkle_staging/' . $file->getFilename();

            // Copy the new AppCast feed into place.
            copy($new_appcast_file_location, $appcast_file_location);

            // Clean up the temporary file.
            $file->delete();
            \Drupal::service('messenger')->addMessage("Move new file into place: " . $appcast_file_location);
        }
    }
}