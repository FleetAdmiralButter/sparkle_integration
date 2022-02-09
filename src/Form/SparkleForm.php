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
            '#description' => $this->t('Update package. This will be placed alongside the AppCast XML feed.'),
            '#upload_location' => 'private://sparkle_staging',
        ];

        $form['replace_package'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Replace Update Package'),
            '#default_value' => TRUE,
            '#description' => $this->t('If an update package with the same name already exists, it will be replaced with the uploaded copy.'),
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
            \Drupal::service('messenger')->addMessage('Got file name: ' . $file->getFilename());

            // Copy the new AppCast feed into place.
            copy($new_appcast_file_location, $appcast_file_location);

            // Clean up the temporary file.
            $file->delete();
        }

        // Finish by hydrating the CDN with new content.
        try {
            $route = '/sites/default/files/update_data/xivonmac_appcast.xml';
            $item = new RouteItem(['route' => $route]);
            $item->send();
            \Drupal::service('messenger')->addMessage("CDN hydration started");
        } catch (\Exception $e) {
            \Drupal::service('messenger')->addError("Error connecting to QuantCDN");
        }


        
    }
}