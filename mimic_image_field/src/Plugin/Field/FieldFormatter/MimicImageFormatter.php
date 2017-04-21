<?php

namespace Drupal\mimic_image_field\Plugin\Field\FieldFormatter;

use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Plugin implementation of the 'mimic_image' formatter.
 *
 * @FieldFormatter(
 *   id = "mimic_image",
 *   label = @Translation("Image"),
 *   field_types = {
 *     "mimic_image"
 *   },
 *   quickedit = {
 *     "editor" = "image"
 *   }
 * )
 */
class MimicImageFormatter extends ImageFormatter {


}
