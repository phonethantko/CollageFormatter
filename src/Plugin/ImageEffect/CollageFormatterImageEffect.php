<?php

/**
 * @file
 * Contains \Drupal\collageformatter\src\Plugin\ImageEffect\CollageFormatterImageEffect
 */

namespace Drupal\collageformatter\Plugin\ImageEffect;

use Drupal\image\ImageEffectBase;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Image\ImageInterface;

 /**
 * Description of the collageformatter image effect plugin.
 *
 * @ImageEffect(
 *   id = "collageformatter",
 *   label = @Translation("Collage Formatter"),
 *   description = @Translation("Desaturate converts an image to grayscale.")
 * )
 */

 class CollageFormatterEffect extends ImageEffectBase {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    if (Unicode::strpos(FileSystem::basename($image->source), '_copy_') !== FALSE
        || Unicode::strpos(FileSystem::basename($image->source), '_symlink_') !== FALSE
        || Unicode::strpos(FileSystem::basename($image->source), '_fake_') !== FALSE) {
        $dimensions = preg_replace('/.+\/([\d]+x[\d]+)_(copy|symlink|fake)_.+/', '$1', $image->source);
        list($image_width, $image_height) = explode('x', $dimensions);

        $original_image_uri = preg_replace('/(.+\/)collageformatter\/(.+\/)[\d]+x[\d]+_(copy|symlink|fake)_(.+)/', '$1$2$4', $image->source);

        // If it is a fake image - we need to load the real image resource.
        if (Unicode::strpos(FileSystem::basename($image->source), '_fake_') !== FALSE) {
          $original_image = Drupal::service('image.factory')->get($original_image_uri);
          $image->info = $original_image->info;
          $image->resource = $original_image->resource;
        }

        $image->source = $original_image_uri;
        $effect_callback = 'image_scale_and_crop_effect';
        if (\Drupal::moduleHandler()->moduleExists('focal_point')) {
          $effect_callback = 'focal_point_scale_and_crop_effect';
        }

        if (isset($image_width) && isset($image_height)) {
          return image_effect_apply($image, [
            'effect callback' => $effect_callback,
            'data' => [
              'width' => $image_width,
              'height' => $image_height,
            ],
          ]);
        }
      }
      return FALSE;
    }
 }
