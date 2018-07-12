<?php

/**
 * @file
 * Contains \Drupal\collageformatter\src\Plugin\Field\FieldFormatter\CollageFormatter.
 */

namespace Drupal\collageformatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Image\Image;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;


/**
 * Plugin implementation of the 'collageformatter' formatter.
 *
 * @FieldFormatter(
 *   id = "collageformatter",
 *   label = @Translation("Collage Formatter"),
 *   description = @Translation("Provides collage formatter for Image fields."),
 *   field_types = {
 *     "image"
 *   }
 * )
 */

class CollageFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'collage_number' => 1,
      'images_per_collage' => NULL,
      'images_to_skip' => 0,
      'collage_orientation' => 0,
      'collage_width' => 500,
      'collage_height' => '',
      'collage_border_size' => 0,
      'collage_border_color' => '#ffffff',
      'gap_size' => 0,
      'gap_color' => '#ffffff',
      'border_size' => 0,
      'border_color' => '#000000',
      'image_link' => 'file',
      'image_link_image_style' => NULL,
      'image_link_modal' => NULL,
      'image_link_class' => NULL,
      'image_link_rel' => NULL,
      'generate_image_derivatives' => 0,
      'prevent_upscale' => 0,
      'advanced' => [
        'original_image_reference' => 'symlink',
      ],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // TODO: 确认是否要留着旧设置
    // TODO: Set $this->getSetting() to an array locally
    // $form = parent::settingsForm($form, $form_state);
    // drupal_map_assoc() has been removed in Drupal 8
    $options_0_50 = array_combine(range(0, 50), range(0, 50));
    $options_1_50 = array_combine(range(1, 50), range(1, 50));
    $settings = $this->getSettings();

    $form['collage_number'] = [
      '#type' => 'select',
      '#title' => t('Number of collages'),
      '#title_display' => 'invisible',
      '#options' => $options_1_50,
      '#default_value' => $settings['collage_number'],
      '#field_prefix' => t('Generate'),
      '#field_suffix' => t('collage(s)'),
      '#prefix' => '<div class="container-inline">',
    ];
    $form['images_per_collage'] = [
      '#type' => 'select',
      '#title' => t('Images per collage'),
      '#title_display' => 'invisible',
      '#options' => $options_1_50,
      '#default_value' => $settings['images_per_collage'],
      '#empty_option' => t('all'),
      '#field_prefix' => t('with'),
      '#field_suffix' => t('image(s) per collage') . ';',
    ];
    $form['images_to_skip'] = [
      '#type' => 'select',
      '#title' => t('Images to skip'),
      '#title_display' => 'invisible',
      '#options' => $options_0_50,
      '#default_value' => $settings['images_to_skip'],
      '#field_prefix' => t('Skip'),
      '#field_suffix' => t('image(s) from the start'),
      '#suffix' => '</div>',
    ];

    $form['collage_orientation'] = [
      '#type' => 'select',
      '#title' => t('Collage orientation'),
      '#description' => t('Select if it should be a wide collage (landscape) or a tall one (portrait).'),
      '#options' => [
        '0' => t('Landscape'),
        '1' => t('Portrait'),
      ],
      '#default_value' => $settings['collage_orientation'],
      '#prefix' => '<div class="container-inline">',
      '#field_suffix' => '</br>',
      '#suffix' => '</div>',
    ];
    $form['collage_width'] = [
      '#type' => 'textfield',
      '#title' => t('Collage width'),
      '#title_display' => 'invisible',
      '#default_value' => $settings['collage_width'],
      '#min' => 0,
      '#size' => 4,
      '#maxlength' => 4,
      '#prefix' => '<div class="container-inline"><strong>' . t('Collage width & height: ') . '</strong>',
      // '#field_prefix' => '<br/>',
      '#field_suffix' => 'x',
    ];
    $form['collage_height'] = [
      '#type' => 'textfield',
      '#title' => t('Collage height'),
      '#title_display' => 'invisible',
      '#description' => t('Total collage width and height with all the borders and gaps. If you specify both then the images will be cropped.'),
      '#default_value' => $settings['collage_height'],
      '#min' => 0,
      '#size' => 4,
      '#maxlength' => 4,
      '#field_suffix' => 'px ',
      '#suffix' => '</div>',
    ];

    $form['collage_border_size'] = [
      '#type' => 'select',
      '#title' => t('Collage border'),
      '#options' => $options_0_50,
      '#default_value' => $settings['collage_border_size'],
      '#field_suffix' => 'px',
      '#prefix' => '<div class="container-inline">',
    ];
    $form['collage_border_color'] = [
      '#type' => 'textfield',
      '#title' => t('Collage border color'),
      '#default_value' => $settings['collage_border_color'],
      '#size' => 7,
      '#maxlength' => 7,
      '#suffix' => '<div class="collageformatter-color-picker"></div>' . '</div>',
    ];
    // TODO: Check whether the script is loaded
    // FIXME: The script is not being loaded
    $form['collage_border_color']['#attached']['library'][] = 'collageformatter/farbtastic';

    $form['gap_size'] = $form['collage_border_size'];
    $form['gap_size']['#title'] = t('Image gap');
    $form['gap_size']['#default_value'] = $settings['gap_size'];
    $form['gap_color'] = $form['collage_border_color'];
    $form['gap_color']['#title'] = t('Image gap color');
    $form['gap_color']['#default_value'] = $settings['gap_color'];

    $form['border_size'] = $form['collage_border_size'];
    $form['border_size']['#title'] = t('Image border');
    $form['border_size']['#default_value'] = $settings['border_size'];
    $form['border_color'] = $form['collage_border_color'];
    $form['border_color']['#title'] = t('Image border color');
    $form['border_color']['#default_value'] = $settings['border_color'];

    $form['image_link'] = [
      '#title' => t('Link image to'),
      '#type' => 'select',
      '#default_value' => $settings['image_link'],
      '#empty_option' => t('Nothing'),
      '#options' => [
        'content' => t('Content'),
        'file' => t('File'),
      ],
      '#prefix' => '<div class="container-inline">',
    ];

    $image_styles = image_style_options(FALSE);
    $form['image_link_image_style'] = [
      '#title' => t('Target image style'),
      '#type' => 'select',
      '#default_value' => $settings['image_link_image_style'],
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
    ];

    $modal_options = [];
    if (\Drupal::moduleHandler()->moduleExists('colorbox')) {
      $modal_options['colorbox'] = t('Colorbox');
    }
    if (\Drupal::moduleHandler()->moduleExists('shadowbox')) {
      $modal_options['shadowbox'] = t('Shadowbox');
    }
    if (\Drupal::moduleHandler()->moduleExists('fancybox')) {
      $modal_options['fancybox'] = t('fancyBox');
    }
    if (\Drupal::moduleHandler()->moduleExists('photobox')) {
      $modal_options['photobox'] = t('Photobox');
    }
    if (\Drupal::moduleHandler()->moduleExists('photoswipe')) {
      $modal_options['photoswipe'] = t('PhotoSwipe');
    }
    if (\Drupal::moduleHandler()->moduleExists('lightbox2')) {
      $modal_options['lightbox2'] = t('Lightbox2');
    }
    $form['image_link_modal'] = [
      '#title' => t('Modal gallery'),
      '#type' => 'select',
      '#default_value' => $settings['image_link_modal'],
      '#empty_option' => t('None'),
      '#options' => $modal_options,
      '#suffix' => '</div>',
    ];

    $form['image_link_class'] = [
      '#type' => 'textfield',
      '#title' => t('Image link class'),
      // '#description' => t('Custom class to add to all image links.'),
      '#default_value' => $settings['image_link_class'],
      '#size' => 30,
      '#prefix' => '<div class="container-inline">',
    ];
    $form['image_link_rel'] = [
      '#type' => 'textfield',
      '#title' => t('Image link rel'),
      // '#description' => t('Custom rel attribute to add to all image links.'),
      '#default_value' => $settings['image_link_rel'],
      '#size' => 30,
      '#suffix' => '</div>',
    ];
    $form['generate_image_derivatives'] = [
      '#type' => 'checkbox',
      '#title' => t('Generate image derivatives'),
      '#description' => t('Generate image derivatives used in the collage while rendering it, before displaying.'),
      '#default_value' => $settings['generate_image_derivatives'],
    ];
    $form['prevent_upscale'] = [
      '#type' => 'checkbox',
      '#title' => t('Prevent images upscaling'),
      '#description' => t('Generated collage dimensions might be smaller.'),
      '#default_value' => $settings['prevent_upscale'],
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => t('Advanced settings'),
      '#collapsible' => TRUE,
      '#open' => FALSE,
    ];
    $form['advanced']['original_image_reference'] = [
      '#type' => 'radios',
      '#title' => t('Original image reference method'),
      '#description' => t('If you need to add additional image effects to collageformatter image style before the "Collage Formatter" effect then you need to use "Symlink" or "Copy" method.'),
      '#options' => [
        'symlink' => t('Symlink'),
        'fake' => t('Fake image'),
        'copy' => t('Copy'),
      ],
      '#default_value' => $settings['advanced']['original_image_reference'],
    ];

    $form['image_link_image_style']['#states'] = [
      'visible' => [
        ':input[name="fields[field_image][settings_edit_form][settings][image_link]"]' => ['value' => 'file'],
      ],
    ];
    $form['image_link_modal']['#states'] = [
      'visible' => [
        ':input[name="fields[field_image][settings_edit_form][settings][image_link]"]' => ['value' => 'file'],
      ],
    ];
    $form['image_link_class']['#states'] = [
      'invisible' => [
        ':input[name="fields[field_image][settings_edit_form][settings][image_link]"]' => ['value' => ''],
      ],
    ];
    $form['image_link_rel']['#states'] = [
      'invisible' => [
        ':input[name="fields[field_image][settings_edit_form][settings][image_link]"]' => ['value' => ''],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    // TODO: Confirm whether the default settings are wanted.
    $summary = parent::settingsSummary();

    $summary[] = t('Generate') . ' <strong>' . $settings['collage_number'] . '</strong> ' . t('collage(s)') . ' '
             . t('with') . ' <strong>' . ($settings['images_per_collage'] ? $settings['images_per_collage'] : t('all')) . '</strong> ' . t('image(s) per collage') . '; '
             . t('Skip') . ' <strong>' . $settings['images_to_skip'] . '</strong> ' . t('image(s) from the start');
    $summary[] = t('Collage orientation') . ': ' . ($settings['collage_orientation'] ? t('Portrait') : t('Landscape'));
    $summary[] = t('Collage width') . ': ' . ($settings['collage_width'] ? $settings['collage_width'] . 'px' : t('Not set'));
    $summary[] = t('Collage height') . ': ' . ($settings['collage_height'] ? $settings['collage_height'] . 'px' : t('Not set'));
    $summary[] = t('Collage border') . ': ' . $settings['collage_border_size'] . 'px <span style="background-color: ' . $settings['collage_border_color'] . ';">' . $settings['collage_border_color'] . '</span>';
    $summary[] = t('Image gap') . ': ' . $settings['gap_size'] . 'px <span style="background-color: ' . $settings['gap_color'] . ';">' . $settings['gap_color'] . '</span>';
    $summary[] = t('Image border') . ': ' . $settings['border_size'] . 'px <span style="background-color: ' . $settings['border_color'] . ';">' . $settings['border_color'] . '</span>';

    $link_types = [
      'content' => t('Images linked to content'),
      'file' => t('Images linked to file'),
    ];

    if (!empty($link_types[$settings['image_link']])) {
      $summary[] = $link_types[$settings['image_link']];
      if ($settings['image_link'] == 'file') {
        if (empty($settings['image_link_image_style'])) {
          $summary[] = t('Target image style') . ': ' . t('None (Original Image)');
        } else {
          $image_styles = image_style_options(FALSE);
          $summary[] = t('Target image style') . ': ' . $image_styles[$settings['image_link_image_style']];
        }

        // Modal gallery summary
        if (empty($settings['image_link_modal'])) {
          $summary[] = t('Modal gallery') . ': ' . t('None');
        } else {
          $summary[] = t('Modal gallery') . ': ' . $settings['image_link_modal'];
        }

        // Custom class/rel summary.
        $custom = [];
        if (!empty($settings['image_link_class'])) {
          $custom[] = 'class="' . Html::escape($settings['image_link_class']) . '"';
        }
        if (!empty($settings['image_link_rel'])) {
          $custom[] = 'rel="' . Html::escape($settings['image_link_rel']) . '"';
        }
        $summary[] = implode(' ', $custom);
      }
    }
    else {
      $summary[] = t('Images without links');
    }
    if ($settings['generate_image_derivatives']) {
      $summary[] = t('Generate image derivatives');
    }
    else {
      $summary[] = t('Do not generate image derivatives');
    }
    if ($settings['prevent_upscale']) {
      $summary[] = t('Prevent images upscaling');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // $elements = parent::viewElements($items, $langcode);
    $elements = [];
    $files = $this->getEntitiesToView($items, $langcode);
    $settings = $this->getSettings();
    $entity = $items->getEntity();

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $settings['gallery'] = 'collageformatter-' . 'field_image' . '-' . $entity->id();
    $url = NULL;
    $image_link_settings = $this->getSetting('image_link');

    // Link the contents if the image is supposed to link with contents
    if ($image_link_settings == 'content') {
      if (!$entity->isNew()) {
        $url = $entity->urlInfo();
      }
    } elseif ($image_link_settings == 'file') {
      $link_file = TRUE;
    }
    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $base_cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage
        ->load($image_style_setting);
      $base_cache_tags = $image_style
        ->getCacheTags();
    }

    foreach ($files as $delta => $file) {
      $cache_contexts = [];
      if (isset($link_file)) {
        $image_uri = $file
          ->getFileUri();

        // @todo Wrap in file_url_transform_relative(). This is currently
        // impossible. As a work-around, we currently add the 'url.site' cache
        // context to ensure different file URLs are generated for different
        // sites in a multisite setup, including HTTP and HTTPS versions of the
        // same site. Fix in https://www.drupal.org/node/2646744.
        $url = Url::fromUri(file_create_url($image_uri));
        $cache_contexts[] = 'url.site';
      }
      $cache_tags = Cache::mergeTags($base_cache_tags, $file
        ->getCacheTags());

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);
      // NOTE: Think how you can link this code with the Drupal 7 version.
      // TODO: Modify this code block to customize the view.
      // $elements[$delta] = [
      //   '#theme' => 'image_formatter', // NOTE: THEME TEMPLATE IS HERE!!!!!!!!!!! Have to implement hook function in the module.
      //   '#item' => $item,
      //   '#item_attributes' => $item_attributes,
      //   '#image_style' => $image_style_setting,
      //   '#url' => $url,
      //   // '#cache' => [
      //   //   'tags' => $cache_tags,
      //   //   'contexts' => $cache_contexts,
      //   // ],
      // ];
      $elements[$delta] = ['#theme' => 'image_formatter'];
    }
    return $elements;
  }



  // TODO: To write methods for recursive rendering
  /**
   * Returns renderable array of collages.
   */
  public static function collageformatter_render_collage() {
    $collage = [];

    // // Remove images to skip
    // if ($settings['images_to_skip']) {
    //   $images = array_slice($images, $settings['images_to_skip']);
    // }
    // // Prepare images.
    // foreach ($images as $delta => $image) {
    //   if (!isset($image['width']) || !isset($image['height'])) {
    //     if ($image_info = $image->getFileUri()) {
    //       $image += $image_info;
    //     }
    //   }
    //   $image += [
    //     'box_type' => 'image',
    //     'delta' => $delta,
    //     'total_width' => $image['width'] + 2 * $settings['border_size'] + $settings['gap_size'],
    //     'total_height' => $image['height'] + 2 * $settings['border_size'] + $settings['gap_size'],
    //   ];
    // }

    $collage = [
      '#theme' => 'image_formatter',
    ];
    return $collage;
  }
}
