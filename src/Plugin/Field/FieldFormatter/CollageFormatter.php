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

error_reporting(E_ALL & ~E_NOTICE);
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
    // $summary = parent::settingsSummary();

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

      $elements[$delta] = [
        '#theme' => 'image_formatter', // NOTE: THEME TEMPLATE IS HERE!!!!!!!!!!! Have to implement hook function in the module.
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#image_style' => $image_style_setting,
        '#url' => $url,
        '#cache' => [
          'tags' => $cache_tags,
          'contexts' => $cache_contexts,
        ],
      ];
    }
    $elements[$delta] += $this->collageformatter_render_collage($files, $settings);
    return $elements;
  }

  /**
   * Returns renderable array of collages.
   */
  public function collageformatter_render_collage($images, $settings) {
    $collage = [];

    // Remove images to skip
    if ($settings['images_to_skip']) {
      $images = array_slice($images, $settings['images_to_skip']);
    }
    // Prepare images.
    foreach ($images as $delta => $image) {
      $image_properties = $image->_referringItem;
      if (!isset($image_properties->width) || !isset($image_properties->height)) $image += $image_info = $image->getFileUri() ?? $image_info;
      $image_properties->set('box_type', 'image');
      $image_properties->set('delta', $delta);
      $image_properties->set('total_width', $image_properties->width + 2 * $settings['border_size'] + $settings['gap_size']);
      $image_properties->set('total_height', $image_properties->height + 2 * $settings['border_size'] + $settings['gap_size']);

      // NOTE: Currently setting values individually. Find a way to set the whole array to the configs.
      // $image->_referringItem += [
      //   'box_type' => 'image',
      //   'delta' => $delta,
      //   'total_width' => $image_properties->width + 2 * $settings['border_size'] + $settings['gap_size'],
      //   'total_height' => $image_properties->height + (2 * $settings['border_size']) + $settings['gap_size'],
      // ];

    }

    // Determine the number of collages and how many images to include in a collage
    $collage_number = $settings['collage_number'];
    $images_per_collage = $settings['images_per_collage'] ? $settings['images_per_collage'] : round(count($images) / $collage_number);

    // Generate collages.
    while ($collage_number > 0) {

      $collage_number--;
      // If last collage and all images option - take all images.
      if ($collage_number == 0 && !$settings['images_per_collage']) {
        $collage_images = $images;
      }
      // Take set number of images for this collage.
      else {
        $collage_images = array_slice($images, 0, $images_per_collage);
        // Update images array and set as the last collage if there are no more images.
        if (!$images = array_slice($images, $images_per_collage)) {
          $collage_number = 0;
        }
      }

      // Generate collage layout.
      $box = $this->collageformatter_layout_box($collage_images, $settings['collage_orientation']);
    //   // Scale the collage.
    //   if ($settings['collage_width']) {
    //     $box['parent_total_width'] = $settings['collage_width'] - 2 * $settings['collage_border_size'];
    //     $dimensions = array('width' => $box['parent_total_width'] - $settings['gap_size']);
    //     $box = $this->collageformatter_scale_box($box, $dimensions);
    //     $box['parent_total_height'] = $box['total_height'] + $settings['gap_size'];
    //   }
    //   elseif ($settings['collage_height']) {
    //     $box['parent_total_height'] = $settings['collage_height'] - 2 * $settings['collage_border_size'];
    //     $dimensions = array('height' => $box['parent_total_height'] - $settings['gap_size']);
    //     $box = $this->collageformatter_scale_box($box, $dimensions);
    //     $box['parent_total_width'] = $box['total_width'] + $settings['gap_size'];
    //   }
    //   else {
    //     $box['parent_total_width'] = $box['total_width'] + $settings['gap_size'];
    //     $box['parent_total_height'] = $box['total_height'] + $settings['gap_size'];
    //   }

    //   // Resize the collage if both with and height are set.
    //   if ($settings['collage_width'] && $settings['collage_height']) {
    //     $box['parent_total_width'] = $settings['collage_width'] - 2 * $settings['collage_border_size'];
    //     $box['parent_total_height'] = $settings['collage_height'] - 2 * $settings['collage_border_size'];
    //     $dimensions = array(
    //       'width' => $box['parent_total_width'] - $settings['gap_size'],
    //       'height' => $box['parent_total_height'] - $settings['gap_size'],
    //     );
    //     $box = $this->collageformatter_resize_box($box, $dimensions);
    //   }
    //
    //   // Check for upscaled images and prevent upscaling.
    //   if ($settings['prevent_upscale']) {
    //     $scale = $this->collageformatter_upscaling_check($box, $settings);
    //     if ($scale < 1) {
    //       $dimensions = array('width' => $scale * $box['total_width']);
    //       $box = $this->collageformatter_scale_box($box, $dimensions);
    //       $box['parent_total_width'] = $box['total_width'] + $settings['gap_size'];
    //       $box['parent_total_height'] = $box['total_height'] + $settings['gap_size'];
    //     }
    //   }
    //
    //   $collage_wrapper_style = array();
    //   $collage_wrapper_style[] = 'max-width: ' . round($box['parent_total_width'] + 2 * $settings['collage_border_size'] - 0.5) . 'px;';
    //   // $collage_wrapper_style[] = 'box-sizing: border-box; -webkit-box-sizing: border-box; -moz-box-sizing: border-box;';
    //
    //   $collage_style = array();
    //   // $collage_style[] = 'box-sizing: border-box; -webkit-box-sizing: border-box; -moz-box-sizing: border-box;';
    //   if ($settings['collage_border_size']) {
    //     $border = 'border: ' . $settings['collage_border_size'] . 'px solid';
    //     $border .= $settings['collage_border_color'] ? ' ' . $settings['collage_border_color'] : '';
    //     $collage_style[] = $border . ';';
    //   }
    //   if ($settings['gap_color']) {
    //     $collage_style[] = 'background-color: ' . $settings['gap_color'] . ';';
    //   }
    //
    //   $collage_wrapper_class = array('collageformatter-collage-wrapper');
    //   if ($settings['image_link_modal'] == 'photoswipe') {
    //     $collage_wrapper_class[] = 'photoswipe-gallery';
    //   }
    //
    //   $collage[] = array(
    //     '#theme' => 'collageformatter_collage',
    //     '#collage' => $this->collageformatter_render_box($box, $settings),
    //     '#collage_wrapper_class' => implode(' ', $collage_wrapper_class),
    //     '#collage_wrapper_style' => implode(' ', $collage_wrapper_style),
    //     '#collage_style' => implode(' ', $collage_style),
    //     '#collage_bottom_style' => 'clear: both; margin-bottom: ' . 100 * ($settings['gap_size'] / round($box['parent_total_width'] - 0.5)) . '%',
    //   );
    }
    $collage = [];
    return $collage;
  }

  /**
   * Recursive function to build the layout.
   * * @param $type
   *   boolean - TRUE for portrait (horizontal contact - vertical box type);
   *             FALSE for landscape (vertical contact - horizontal box type).
   */
  function collageformatter_layout_box($images, $type) {
    $box = [];
    $count = count($images);
    if ($count >= 2) {
      $size1 = floor($count / 2);
      $size2 = $count - $size1;
      $images1 = array_slice($images, 0, $size1);
      $images2 = array_slice($images, $size1, $size2);
      $box = [
        'box_type' => 'box',
        'box_orientation' => $type ? 'vertical' : 'horizontal',
        'pixel_check' => FALSE,
      ];
      $box[1] = $this->collageformatter_layout_box($images1, !$type);
      $box[2] = $this->collageformatter_layout_box($images2, !$type);
      $box[1]['parent_box_orientation'] = $box[2]['parent_box_orientation'] = $box['box_orientation'];
      $box[1]['pixel_check'] = FALSE;
      $box[2]['pixel_check'] = TRUE;

      if ($type) {
        // Horizontal contact; vertical box type.
        $dimensions = [
          'width' => $box[1]['total_width']
        ];
      }
      else {
        // Vertical contact; horizontal box type.
        $dimensions = [
          'height' => $box[1]['total_height']
        ];
      }
      drupal_set_time_limit(300);

      // $box[2] = $this->collageformatter_scale_box($box[2], $dimensions);

      if ($type) {
        // Horizontal contact; vertical box type.
        $box['total_height'] = $box[1]['total_height'] + $box[2]['total_height'];
        $box['total_width'] = $box[1]['total_width'];
      }
      else {
        // Vertical contact; horizontal box type.
        $box['total_width'] = $box[1]['total_width'] + $box[2]['total_width'];
        $box['total_height'] = $box[1]['total_height'];
      }

      $box[1]['parent_total_width'] = $box[2]['parent_total_width'] = $box['total_width'];
      $box[1]['parent_total_height'] = $box[2]['parent_total_height'] = $box['total_height'];
      $box[1]['siblings_total_width'] = $box[2]['total_width'];
      $box[1]['siblings_total_height'] = $box[2]['total_height'];
      $box[2]['siblings_total_width'] = $box[1]['total_width'];
      $box[2]['siblings_total_height'] = $box[1]['total_height'];
    }
    elseif ($count == 1) {
      $box = [array_pop($images)];
      $box['pixel_check'] = FALSE;
    }

    return $box;
  }

  /**
   * Recursive function to scale the box using only one dimension.
   */
  function collageformatter_scale_box($box, $dimensions) {

    // If it is an image - just scale it (change dimensions).
    if ($box['box_type'] == 'image') {
      if (array_key_exists('width', $dimensions)) {
        $box['total_height'] = ($dimensions['width'] / $box['total_width']) * $box['total_height'];
        $box['total_width'] = $dimensions['width'];
      }
      elseif (array_key_exists('height', $dimensions)) {
        $box['total_width'] = ($dimensions['height'] / $box['total_height']) * $box['total_width'];
        $box['total_height'] = $dimensions['height'];
      }
      return $box;
    }

    // If it is a box - then it should consist of two box elements;
    // Determine sizes of elements and scale them.
    if (array_key_exists('width', $dimensions)) {
      // Vertical box type; horizontal contact.
      if ($box['box_orientation'] == 'vertical') {
        $dimensions1 = $dimensions2 = $dimensions;
      }
      // Horizontal box type; vertical contact.
      elseif ($box['box_orientation'] == 'horizontal') {
        $dimensions1 = ['width' => ($box[1]['total_width'] / ($box[1]['total_width'] + $box[2]['total_width'])) * $dimensions['width']];
        $dimensions2 = ['width' => ($box[2]['total_width'] / ($box[1]['total_width'] + $box[2]['total_width'])) * $dimensions['width']];
      }
    }
    elseif (array_key_exists('height', $dimensions)) {
      // Vertical box type; horizontal contact.
      if ($box['box_orientation'] == 'vertical') {
        $dimensions1 = ['height' => ($box[1]['total_height'] / ($box[1]['total_height'] + $box[2]['total_height'])) * $dimensions['height']];
        $dimensions2 = ['height' => ($box[2]['total_height'] / ($box[1]['total_height'] + $box[2]['total_height'])) * $dimensions['height']];
      }
      // Horizontal box type; vertical contact.
      elseif ($box['box_orientation'] == 'horizontal') {
        $dimensions1 = $dimensions2 = $dimensions;
      }
    }
    $box[1] = $this->collageformatter_scale_box($box[1], $dimensions1);
    $box[2] = $this->collageformatter_scale_box($box[2], $dimensions2);

    if ($box['box_orientation'] == 'vertical') {
      $box['total_height'] = $box[1]['total_height'] + $box[2]['total_height'];
      $box['total_width'] = $box[1]['total_width'];
    }
    elseif ($box['box_orientation'] == 'horizontal') {
      $box['total_width'] = $box[1]['total_width'] + $box[2]['total_width'];
      $box['total_height'] = $box[1]['total_height'];
    }

    $box[1]['parent_total_width'] = $box[2]['parent_total_width'] = $box['total_width'];
    $box[1]['parent_total_height'] = $box[2]['parent_total_height'] = $box['total_height'];
    $box[1]['siblings_total_width'] = $box[2]['total_width'];
    $box[1]['siblings_total_height'] = $box[2]['total_height'];
    $box[2]['siblings_total_width'] = $box[1]['total_width'];
    $box[2]['siblings_total_height'] = $box[1]['total_height'];

    return $box;
  }

  // /**
  //  * Recursive function to resize the box.
  //  */
  // function _collageformatter_resize_box($box, $dimensions) {
  //   // If it is an image - just resize it (change dimensions).
  //   if ($box['box_type'] == 'image') {
  //     $box['total_width'] = $dimensions['width'];
  //     $box['total_height'] = $dimensions['height'];
  //     return $box;
  //   }
  //
  //   // If it is a box - then it should consist of two box elements;
  //   // Determine sizes of elements and resize them.
  //
  //   // Vertical box type; horizontal contact.
  //   if ($box['box_orientation'] == 'vertical') {
  //     $dimensions1 = array(
  //       'width' => $dimensions['width'],
  //       'height' => ($box[1]['total_height'] / ($box[1]['total_height'] + $box[2]['total_height'])) * $dimensions['height'],
  //     );
  //     $dimensions2 = array(
  //       'width' => $dimensions['width'],
  //       'height' => ($box[2]['total_height'] / ($box[1]['total_height'] + $box[2]['total_height'])) * $dimensions['height'],
  //     );
  //   }
  //   // Horizontal box type; vertical contact.
  //   elseif ($box['box_orientation'] == 'horizontal') {
  //     $dimensions1 = array(
  //       'width' => ($box[1]['total_width'] / ($box[1]['total_width'] + $box[2]['total_width'])) * $dimensions['width'],
  //       'height' => $dimensions['height'],
  //     );
  //     $dimensions2 = array(
  //       'width' => ($box[2]['total_width'] / ($box[1]['total_width'] + $box[2]['total_width'])) * $dimensions['width'],
  //       'height' => $dimensions['height'],
  //     );
  //   }
  //   $box[1] = _collageformatter_resize_box($box[1], $dimensions1);
  //   $box[2] = _collageformatter_resize_box($box[2], $dimensions2);
  //
  //   if ($box['box_orientation'] == 'vertical') {
  //     $box['total_height'] = $box[1]['total_height'] + $box[2]['total_height'];
  //     $box['total_width'] = $box[1]['total_width'];
  //   }
  //   elseif ($box['box_orientation'] == 'horizontal') {
  //     $box['total_width'] = $box[1]['total_width'] + $box[2]['total_width'];
  //     $box['total_height'] = $box[1]['total_height'];
  //   }
  //
  //   $box[1]['parent_total_width'] = $box[2]['parent_total_width'] = $box['total_width'];
  //   $box[1]['parent_total_height'] = $box[2]['parent_total_height'] = $box['total_height'];
  //   $box[1]['siblings_total_width'] = $box[2]['total_width'];
  //   $box[1]['siblings_total_height'] = $box[2]['total_height'];
  //   $box[2]['siblings_total_width'] = $box[1]['total_width'];
  //   $box[2]['siblings_total_height'] = $box[1]['total_height'];
  //
  //   return $box;
  // }
  //
  // /**
  //  * Recursive function to render the box.
  //  */
  // function _collageformatter_render_box($box, $settings) {
  //   $output = '';
  //
  //   // Check if parent dimensions changed - and change yourself.
  //   if (array_key_exists('parent_box_orientation', $box)) {
  //     if ($box['parent_box_orientation'] == 'vertical') {
  //       $box['total_width'] = $box['parent_total_width'];
  //     }
  //     elseif ($box['parent_box_orientation'] == 'horizontal') {
  //       $box['total_height'] = $box['parent_total_height'];
  //     }
  //   }
  //
  //   // Perform pixel check.
  //   if ($box['pixel_check']) {
  //     if ($box['parent_box_orientation'] == 'vertical') {
  //       $pixels = round($box['parent_total_height'] - 0.5) - round($box['total_height'] - 0.5) - round($box['siblings_total_height'] - 0.5);
  //       if ($pixels) {
  //         $box['total_height'] += $pixels;
  //       }
  //     }
  //     elseif ($box['parent_box_orientation'] == 'horizontal') {
  //       $pixels = round($box['parent_total_width'] - 0.5) - round($box['total_width'] - 0.5) - round($box['siblings_total_width'] - 0.5);
  //       if ($pixels) {
  //         $box['total_width'] += $pixels;
  //       }
  //     }
  //   }
  //
  //   // Ensure that children have correct parent dimensions.
  //   if ($box['box_type'] == 'box') {
  //     $box[1]['parent_total_height'] = $box[2]['parent_total_height'] = $box['total_height'];
  //     $box[1]['parent_total_width'] = $box[2]['parent_total_width'] = $box['total_width'];
  //   }
  //
  //   if ($box['box_type'] == 'box') {
  //     $box_style = array(
  //       'float: left;',
  //       'max-width: ' . round($box['total_width'] - 0.5) . 'px;',
  //     );
  //     $box_style[] = 'width: ' . 100 * (round($box['total_width'] - 0.5) / (round($box['parent_total_width'] - 0.5))) . '%;';
  //     $content[] = _collageformatter_render_box($box[1], $settings);
  //     $content[] = _collageformatter_render_box($box[2], $settings);
  //     $output = array(
  //       '#theme' => 'collageformatter_collage_box',
  //       '#box' => $content,
  //       '#box_style' => implode(' ', $box_style),
  //     );
  //   }
  //   elseif ($box['box_type'] == 'image') {
  //     $image_uri = _collageformatter_image_file_check($box, $settings);
  //
  //     $image_style = array(
  //       'display: block;',
  //       'max-width: 100%;',
  //       'height: auto;',
  //       'margin: 0;',
  //     );
  //
  //     // TODO: use theme('image_formatter', ... ?
  //     $image = theme('image_style', array(
  //       'style_name' => 'collageformatter',
  //       'path' => $image_uri,
  //       'alt' => $box['alt'],
  //       'title' => $box['title'],
  //       'attributes' => array(
  //         'style' => implode(' ', $image_style),
  //       ),
  //     ));
  //
  //     // Create image derivatives.
  //     if ($settings['generate_image_derivatives']) {
  //       $derivative_uri = image_style_path('collageformatter', $image_uri);
  //       if (!file_exists($derivative_uri)) {
  //         $image_style = image_style_load('collageformatter');
  //         if (!image_style_create_derivative($image_style, $image_uri, $derivative_uri)) {
  //           watchdog('collageformatter', 'Unable to generate the derived image located at %path.', array('%path' => $derivative_uri));
  //         }
  //       }
  //     }
  //
  //     $attached = array();
  //     // Process image linking and modal gallery settings.
  //     if ($settings['image_link'] == 'content') {
  //       $class = $settings['image_link_class'] ? array($settings['image_link_class']) : array();
  //       $rel = $settings['image_link_rel'];
  //       $image = l($image,
  //         $box['content_uri'],
  //         array(
  //           'attributes' => array(
  //             'title' => $box['title'],
  //             'class' => $class,
  //             'rel' => $rel,
  //           ),
  //           'html' => TRUE,
  //         )
  //       );
  //     }
  //     elseif ($settings['image_link'] == 'file') {
  //       $image_dimensions = array(
  //         'width' => $box['width'],
  //         'height' => $box['height'],
  //       );
  //       if (empty($settings['image_link_image_style'])) {
  //         $image_url = file_create_url($box['uri']);
  //       }
  //       else {
  //         $image_url = image_style_url($settings['image_link_image_style'], $box['uri']);
  //         image_style_transform_dimensions($settings['image_link_image_style'], $image_dimensions);
  //       }
  //
  //       $class = $settings['image_link_class'] ? array($settings['image_link_class']) : array();
  //       $rel = $settings['image_link_rel'];
  //       $attributes = array();
  //       switch ($settings['image_link_modal']) {
  //         case 'colorbox':
  //           $class[] = 'colorbox';
  //           $rel = 'colorbox-' . $settings['gallery'];
  //           break;
  //         case 'shadowbox':
  //           $rel = 'shadowbox[' . $settings['gallery'] . ']';
  //           break;
  //         case 'fancybox':
  //           $class[] = 'fancybox';
  //           $attributes['data-fancybox-group'] = 'fancybox-' . $settings['gallery'];
  //           break;
  //         case 'photobox':
  //           $class[] = 'photobox';
  //           $attached = photobox_attached_resources();
  //           $attributes['data-photobox-gallery'] = 'photobox-' . $settings['gallery'];
  //           break;
  //         case 'photoswipe':
  //           $class[] = 'photoswipe';
  //           photoswipe_load_assets();
  //           $attributes['data-size'] = $image_dimensions['width'] . 'x' . $image_dimensions['height'];
  //           break;
  //         case 'lightbox2':
  //           $rel = 'lightbox[' . $settings['gallery'] . ']';
  //           break;
  //         default:
  //       }
  //
  //       $image = l($image, $image_url,
  //         array(
  //           'attributes' => array(
  //             'title' => $box['title'],
  //             'class' => $class,
  //             'rel' => $rel,
  //           ) + $attributes,
  //           'html' => TRUE,
  //         )
  //       );
  //     }
  //
  //     $image_wrapper_style = array(
  //       'float: left;',
  //       'max-width: ' . round($box['total_width'] - $settings['gap_size'] - 0.5) . 'px;',
  //       'width: ' . 100 * (round($box['total_width'] - $settings['gap_size'] - 0.5) / round($box['parent_total_width'] - 0.5)) . '%;',
  //     );
  //     if ($settings['gap_size']) {
  //       $margin_percentage = 100 * ($settings['gap_size'] / round($box['parent_total_width'] - 0.5));
  //       $image_wrapper_style[] = 'margin: ' . $margin_percentage . '% 0 0 ' . $margin_percentage . '%;';
  //     }
  //     $border = '';
  //     if ($settings['border_size']) {
  //       $border = 'border: ' . $settings['border_size'] . 'px solid';
  //       if ($settings['border_color']) {
  //         $border .= ' ' . $settings['border_color'];
  //       }
  //       $border .= ';';
  //     }
  //     $output = array(
  //       '#theme' => 'collageformatter_collage_image',
  //       '#image' => $image,
  //       '#image_wrapper_class' => array('collageformatter-collage-image-wrapper-' . $box['delta']),
  //       '#image_wrapper_style' => implode(' ', $image_wrapper_style),
  //       '#image_style' => $border,
  //       '#attached' => $attached,
  //     );
  //   }
  //
  //   return $output;
  // }
  //
  // /**
  //  * Checks for/creates the original image reference file.
  //  */
  // function _collageformatter_image_file_check($box, $settings) {
  //   $image_width = round($box['total_width'] - 2 * $settings['border_size'] - $settings['gap_size'] - 0.5);
  //   $image_height = round($box['total_height'] - 2 * $settings['border_size'] - $settings['gap_size'] - 0.5);
  //   $filename = $image_width . 'x' . $image_height . '_' . $settings['advanced']['original_image_reference'] . '_' . drupal_basename($box['uri']);
  //
  //   $directory = drupal_dirname(file_build_uri('collageformatter/' . file_uri_target($box['uri'])));
  //   $image_uri = $directory . '/' . $filename;
  //
  //   if (!file_exists($image_uri)) {
  //     if (file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
  //       if ($settings['advanced']['original_image_reference'] == 'symlink') {
  //         if (!symlink(drupal_realpath($box['uri']), drupal_realpath($image_uri))) {
  //           watchdog('collageformatter', 'Failed to symlink file @source to @destination.', array('@source' => $box['uri'], '@destination' => $image_uri));
  //         }
  //       }
  //       elseif ($settings['advanced']['original_image_reference'] == 'copy') {
  //         if (!file_unmanaged_copy($box['uri'], $image_uri, FILE_EXISTS_REPLACE)) {
  //           watchdog('collageformatter', 'Failed to copy file from @source to @destination.', array('@source' => $box['uri'], '@destination' => $image_uri));
  //         }
  //       }
  //       elseif ($settings['advanced']['original_image_reference'] == 'fake') {
  //         $image = image_load($box['uri']);
  //         image_effect_apply($image, array(
  //           'effect callback' => 'image_scale_effect',
  //           'data' => array(
  //             'width' => 1,
  //             'height' => 1,
  //           ),
  //         ));
  //         image_save($image, $image_uri);
  //       }
  //     }
  //   }
  //
  //   return $image_uri;
  // }
  //
  // /**
  //  * Checks for upscaled images and returns the scaling factor.
  //  */
  // function _collageformatter_upscaling_check($box, $settings) {
  //   $scale1 = $scale2 = 1;
  //   if ($box['box_type'] == 'box') {
  //     $scale1 = _collageformatter_upscaling_check($box[1], $settings);
  //     $scale2 = _collageformatter_upscaling_check($box[2], $settings);
  //   }
  //   elseif ($box['box_type'] == 'image') {
  //     $width = $box['total_width'] - 2 * $settings['border_size'] - $settings['gap_size'];
  //     $height = $box['total_height'] - 2 * $settings['border_size'] - $settings['gap_size'];
  //     if ($box['width'] < $width) {
  //       $scale1 = $box['width'] / $width;
  //     }
  //     if ($box['height'] < $height) {
  //       $scale1 = $box['height'] / $height;
  //     }
  //   }
  //   return $scale1 <= $scale2 ? $scale1 : $scale2;
  // }
}
