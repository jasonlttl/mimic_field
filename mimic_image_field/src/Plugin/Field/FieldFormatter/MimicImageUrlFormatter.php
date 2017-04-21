<?php

namespace Drupal\mimic_image_field\Plugin\Field\FieldFormatter;

use Drupal\image\Plugin\Field\FieldFormatter\ImageUrlFormatter;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'image_url' formatter.
 *
 * @FieldFormatter(
 *   id = "mimic_image_url",
 *   label = @Translation("URL to image"),
 *   field_types = {
 *     "mimic_image"
 *   }
 * )
 */
class MimicImageUrlFormatter extends ImageUrlFormatter {

}
