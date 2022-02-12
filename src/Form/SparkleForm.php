<?php

namespace Drupal\sparkle_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystem;
use Drupal\quant\Plugin\QueueItem\RouteItem;
use Drupal\Core\Archiver\Zip;
use Drupal\Core\Archiver\ArchiverException;

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
            '#description' => $this->t('A zip file containing the main update package (.tar.xz) as well as delta (.delta) files.'),
            '#upload_location' => 'private://sparkle_staging',
            '#upload_validators' => [
                'file_validate_extensions' => ['zip'],
            ],
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
            'init_message' => t('Drupal is handling your request.'),
            'progress_message' => t('Estimated time: @estimate.'),
            'error_message' => t('The release job encountered a fatal error and has been aborted.'),
        ];
        
        $appcast = $form_state->getValue('appcast', 0);
        $package = $form_state->getValue('update', 0);

        if (isset($appcast[0]) && !empty($appcast[0])) {
            $appcast_file = File::load($appcast[0]);
            $source = 'private://sparkle_staging/' . $appcast_file->getFilename();
            $destination = 'public://update_data/xivonmac_appcast.xml';
            $batch['operations'][] = [['\Drupal\sparkle_integration\Form\SparkleForm', 'copyFile'], [$source, $destination]];
            $batch['operations'][] = [['\Drupal\sparkle_integration\Form\SparkleForm', 'hydrateCDN'], ['/sites/default/files/update_data/xivonmac_appcast.xml']];
            $batch['operations'][] = [['\Drupal\sparkle_integration\Form\SparkleForm', 'clean'], [$appcast_file]];
        }  

        if (isset($package[0]) && !empty($package[0])) {
            $package_file = File::load($package[0]);
            $abs = \Drupal::service('file_system')->realpath($package_file->getFileUri());
            $zip = new Zip($abs);
            $files_to_copy = [];

            // Only extract tar.xz and .delta
            foreach ($zip->listContents() as $archive_file) {
                if ((str_contains($archive_file, '.tar.xz') || str_contains($archive_file, '.delta')) && !(str_contains($archive_file, 'MACOSX'))) {
                    array_push($files_to_copy, $archive_file);
                }
            }
            $zip->extract('private://sparkle_staging/', $files_to_copy);

            foreach ($files_to_copy as $file_to_copy) {
                $source = 'private://sparkle_staging/' . $file_to_copy;
                $destination = 'public://update_data/' . $file_to_copy;
                $batch['operations'][] = [['\Drupal\sparkle_integration\Form\SparkleForm', 'copyFile'], [$source, $destination]];
                $batch['operations'][] = [['\Drupal\sparkle_integration\Form\SparkleForm', 'hydrateCDN'], ['/sites/default/files/update_data/' . $file_to_copy]];
            }
            $batch['operations'][] = [['\Drupal\sparkle_integration\Form\SparkleForm', 'clean'], [$package_file]];

        }
        if (empty($batch['operations'])) {
            \Drupal::service('messenger')->addMessage('Nothing to do!');
        } else {
            batch_set($batch);
        }

    }

    public static function copyFile($source, $destination) {
        $fs_driver = \Drupal::service('file_system');
        $directory = 'public://update_data';
        $fs_driver->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY | \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS);
        copy($source, $destination);
    }


    public static function hydrateCDN($url) {
        try {
            $item = new RouteItem(['route' => $url]);
            $item->send();
            \Drupal::service('messenger')->addMessage('Successfully pushed ' . $url . ' to QuantCDN.');
        } catch (\Exception $e) {
            \Drupal::service('messenger')->addError('Error connecting to QuantCDN');
        }
    }

    public static function clean(File $file) {
        $file->delete();
    }

}