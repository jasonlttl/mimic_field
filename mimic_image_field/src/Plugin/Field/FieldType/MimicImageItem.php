<?php

namespace Drupal\mimic_image_field\Plugin\Field\FieldType;

use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Plugin implementation of the 'image' field type.
 *
 * @FieldType(
 *   id = "mimic_image",
 *   label = @Translation("Mimic Image"),
 *   description = @Translation("This field mimics the best of a chosen set of image fields."),
 *   category = @Translation("Reference"),
 *   default_widget = "mimic_image_image",
 *   default_formatter = "mimic_image",
 *   column_groups = {
 *     "file" = {
 *       "label" = @Translation("File"),
 *       "columns" = {
 *         "target_id", "width", "height"
 *       },
 *       "require_all_groups_for_translation" = TRUE
 *     },
 *     "alt" = {
 *       "label" = @Translation("Alt"),
 *       "translatable" = TRUE
 *     },
 *     "title" = {
 *       "label" = @Translation("Title"),
 *       "translatable" = TRUE
 *     },
 *   },
 *   list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList",
 *   constraints = {"ReferenceAccess" = {}, "FileValidation" = {}}
 * )
 */
class MimicImageItem extends ImageItem {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'default_image' => [
        'uuid' => NULL,
        'alt' => '',
        'title' => '',
        'width' => NULL,
        'height' => NULL,
      ],
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = [
      'file_extensions' => 'png gif jpg jpeg',
      'alt_field' => 1,
      'alt_field_required' => 1,
      'title_field' => 0,
      'title_field_required' => 0,
      'max_resolution' => '',
      'min_resolution' => '',
      'field_preference' => '',
      'default_image' => [
        'uuid' => NULL,
        'alt' => '',
        'title' => '',
        'width' => NULL,
        'height' => NULL,
      ],
    ] + parent::defaultFieldSettings();

    unset($settings['description_field']);
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'target_id' => [
          'description' => 'The ID of the file entity.',
          'type' => 'int',
          'unsigned' => TRUE,
        ],
        'alt' => [
          'description' => "Alternative image text, for the image's 'alt' attribute.",
          'type' => 'varchar',
          'length' => 512,
        ],
        'title' => [
          'description' => "Image title text, for the image's 'title' attribute.",
          'type' => 'varchar',
          'length' => 1024,
        ],
        'width' => [
          'description' => 'The width of the image in pixels.',
          'type' => 'int',
          'unsigned' => TRUE,
        ],
        'height' => [
          'description' => 'The height of the image in pixels.',
          'type' => 'int',
          'unsigned' => TRUE,
        ],
      ],
      'indexes' => [
        'target_id' => ['target_id'],
      ],
      'foreign keys' => [
        'target_id' => [
          'table' => 'file_managed',
          'columns' => ['target_id' => 'fid'],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    unset($properties['display']);
    unset($properties['description']);

    $properties['alt'] = DataDefinition::create('string')
      ->setLabel(t('Alternative text'))
      ->setDescription(t("Alternative image text, for the image's 'alt' attribute."));

    $properties['title'] = DataDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t("Image title text, for the image's 'title' attribute."));

    $properties['width'] = DataDefinition::create('integer')
      ->setLabel(t('Width'))
      ->setDescription(t('The width of the image in pixels.'));

    $properties['height'] = DataDefinition::create('integer')
      ->setLabel(t('Height'))
      ->setDescription(t('The height of the image in pixels.'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];

    // We need the field-level 'default_image' setting, and $this->getSettings()
    // will only provide the instance-level one, so we need to explicitly fetch
    // the field.
    $settings = $this->getFieldDefinition()->getFieldStorageDefinition()->getSettings();

    $scheme_options = \Drupal::service('stream_wrapper_manager')->getNames(StreamWrapperInterface::WRITE_VISIBLE);
    $element['uri_scheme'] = [
      '#type' => 'radios',
      '#title' => t('Upload destination'),
      '#options' => $scheme_options,
      '#default_value' => $settings['uri_scheme'],
      '#description' => t('Select where the final files should be stored. Private file storage has significantly more overhead than public files, but allows restricted access to files within this field.'),
    ];

    // Add default_image element.
    static::defaultImageForm($element, $settings);
    $element['default_image']['#description'] = t('If no image is uploaded, this image will be shown on display.');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    // Get base form from FileItem.
    $element = parent::fieldSettingsForm($form, $form_state);

    $settings = $this->getSettings();

    unset($element['description_field']);
    unset($element['min_resolution']);
    unset($element['alt_field']);
    unset($element['alt_field_required']);
    unset($element['title_field']);
    unset($element['title_field_required']);
    unset($element['max_resolution']);
    unset($element['file_directory']);
    unset($element['max_filesize']);
    unset($element['file_extensions']);

    // Add default_image element.
    static::defaultImageForm($element, $settings);
    $element['default_image']['#description'] = t("If no image is uploaded, this image will be shown on display and will override the field's default image.");

    /*
    $entity_manager = \Drupal::EntityManager();
    $entity_type_manager = \Drupal::EntityTypeManager();
    */
    $entity_id = $this->getEntity()->getEntityTypeId();
    $bundle_id = $this->getEntity()->bundle();

    //$em = \Drupal::EntityTypeManager();
    //dpm (array($entity_id, $bundle_id));
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_id, $bundle_id);
    $options = [];
    //$fields = EntityFieldManager::getFieldDefinitions($entity_id, $bundle_id);

    foreach ($fields as $id => $field) {
      if ($field->getType() == 'image') {
        $options[$id] = $field->getLabel();
      }
    }
    $element['priority'] = [
      '#type' => 'fieldset',
      '#title' => t('Priority'),
      '#description' => t('Choose the field whose value this field should mimic.'),
    ];

    $example = t('Available Fields: ');
    if (count($options) > 0) {
      $example .= SafeMarkup::checkPlain(join(',', array_keys($options)));
    }
    else {
      $example .= t('No image fields found.');
    }

    $element['field_preference'] = [
      '#type' => 'textfield',
      '#title' => t('Field Preference'),
      '#default_value' => $settings['field_preference'],
      '#description' => t('Enter a comma delimited list of image field machine_names. This field will take the value of the first non-empty field in the list. ') . '<br />' . $example,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    /*
    parent::preSave();

    $width = $this->width;
    $height = $this->height;

    // Determine the dimensions if necessary.
    if (empty($width) || empty($height)) {
      $image = \Drupal::service('image.factory')->get($this->entity->getFileUri());
      if ($image->isValid()) {
        $this->width = $image->getWidth();
        $this->height = $image->getHeight();
      }
    }
    */

    $settings = $this->getSettings();
    $entity = $this->getEntity();
    $fields = $entity->toArray();
    dsm($fields);
    dsm($settings);
    // $delta = $this->name; // indeed!
    $value = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $settings = $field_definition->getSettings();
    static $images = [];

    $min_resolution = empty($settings['min_resolution']) ? '100x100' : $settings['min_resolution'];
    $max_resolution = empty($settings['max_resolution']) ? '600x600' : $settings['max_resolution'];
    $extensions = array_intersect(explode(' ', $settings['file_extensions']), ['png', 'gif', 'jpg', 'jpeg']);
    $extension = array_rand(array_combine($extensions, $extensions));
    // Generate a max of 5 different images.
    if (!isset($images[$extension][$min_resolution][$max_resolution]) || count($images[$extension][$min_resolution][$max_resolution]) <= 5) {
      $tmp_file = drupal_tempnam('temporary://', 'generateImage_');
      $destination = $tmp_file . '.' . $extension;
      file_unmanaged_move($tmp_file, $destination, FILE_CREATE_DIRECTORY);
      if ($path = $random->image(drupal_realpath($destination), $min_resolution, $max_resolution)) {
        $image = File::create();
        $image->setFileUri($path);
        $image->setOwnerId(\Drupal::currentUser()->id());
        $image->setMimeType(\Drupal::service('file.mime_type.guesser')->guess($path));
        $image->setFileName(drupal_basename($path));
        $destination_dir = static::doGetUploadLocation($settings);
        file_prepare_directory($destination_dir, FILE_CREATE_DIRECTORY);
        $destination = $destination_dir . '/' . basename($path);
        $file = file_move($image, $destination, FILE_CREATE_DIRECTORY);
        $images[$extension][$min_resolution][$max_resolution][$file->id()] = $file;
      }
      else {
        return [];
      }
    }
    else {
      // Select one of the images we've already generated for this field.
      $image_index = array_rand($images[$extension][$min_resolution][$max_resolution]);
      $file = $images[$extension][$min_resolution][$max_resolution][$image_index];
    }

    list($width, $height) = getimagesize($file->getFileUri());
    $values = [
      'target_id' => $file->id(),
      'alt' => $random->sentences(4),
      'title' => $random->sentences(4),
      'width' => $width,
      'height' => $height,
    ];
    return $values;
  }

  /**
   * Element validate function for resolution fields.
   */
  public static function validateResolution($element, FormStateInterface $form_state) {
    if (!empty($element['x']['#value']) || !empty($element['y']['#value'])) {
      foreach (['x', 'y'] as $dimension) {
        if (!$element[$dimension]['#value']) {
          // We expect the field name placeholder value to be wrapped in t()
          // here, so it won't be escaped again as it's already marked safe.
          $form_state->setError($element[$dimension], t('Both a height and width value must be specified in the @name field.', ['@name' => $element['#title']]));
          return;
        }
      }
      $form_state->setValueForElement($element, $element['x']['#value'] . 'x' . $element['y']['#value']);
    }
    else {
      $form_state->setValueForElement($element, '');
    }
  }

  /**
   * Builds the default_image details element.
   *
   * @param array $element
   *   The form associative array passed by reference.
   * @param array $settings
   *   The field settings array.
   */
  protected function defaultImageForm(array &$element, array $settings) {
    $element['default_image'] = [
      '#type' => 'details',
      '#title' => t('Default image'),
      '#open' => TRUE,
    ];
    // Convert the stored UUID to a FID.
    $fids = [];
    $uuid = $settings['default_image']['uuid'];
    if ($uuid && ($file = $this->getEntityManager()->loadEntityByUuid('file', $uuid))) {
      $fids[0] = $file->id();
    }
    $element['default_image']['uuid'] = [
      '#type' => 'managed_file',
      '#title' => t('Image'),
      '#description' => t('Image to be shown if no image is uploaded.'),
      '#default_value' => $fids,
      '#upload_location' => $settings['uri_scheme'] . '://default_images/',
      '#element_validate' => [
        '\Drupal\file\Element\ManagedFile::validateManagedFile',
        [get_class($this), 'validateDefaultImageForm'],
      ],
      '#upload_validators' => $this->getUploadValidators(),
    ];
    $element['default_image']['alt'] = [
      '#type' => 'textfield',
      '#title' => t('Alternative text'),
      '#description' => t('This text will be used by screen readers, search engines, and when the image cannot be loaded.'),
      '#default_value' => $settings['default_image']['alt'],
      '#maxlength' => 512,
    ];
    $element['default_image']['title'] = [
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#description' => t('The title attribute is used as a tooltip when the mouse hovers over the image.'),
      '#default_value' => $settings['default_image']['title'],
      '#maxlength' => 1024,
    ];
    $element['default_image']['width'] = [
      '#type' => 'value',
      '#value' => $settings['default_image']['width'],
    ];
    $element['default_image']['height'] = [
      '#type' => 'value',
      '#value' => $settings['default_image']['height'],
    ];
  }

  /**
   * Validates the managed_file element for the default Image form.
   *
   * This function ensures the fid is a scalar value and not an array. It is
   * assigned as a #element_validate callback in
   * \Drupal\image\Plugin\Field\FieldType\ImageItem::defaultImageForm().
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateDefaultImageForm(array &$element, FormStateInterface $form_state) {
    // Consolidate the array value of this field to a single FID as #extended
    // for default image is not TRUE and this is a single value.
    if (isset($element['fids']['#value'][0])) {
      $value = $element['fids']['#value'][0];
      // Convert the file ID to a uuid.
      if ($file = \Drupal::entityManager()->getStorage('file')->load($value)) {
        $value = $file->uuid();
      }
    }
    else {
      $value = '';
    }
    $form_state->setValueForElement($element, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function isDisplayed() {
    // Image items do not have per-item visibility settings.
    return TRUE;
  }

  /**
   * Gets the entity manager.
   *
   * @return \Drupal\Core\Entity\EntityManagerInterface
   */
  protected function getEntityManager() {
    if (!isset($this->entityManager)) {
      $this->entityManager = \Drupal::entityManager();
    }
    return $this->entityManager;
  }

}
