<?php

/**
 * @file
 * Contains \Drupal\collageformatter\src\Plugin\Field\FieldFormatter\CollageFormatter.
 */

namespace Drupal\collageformatter\Plugin\Field\FieldFormatter;

use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;


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

    $form['collage_number'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of collages'),
      '#title_display' => 'invisible',
      '#options' => $options_1_50,
      '#default_value' => $this->getSetting('collage_number'),
      '#field_prefix' => $this->t('Generate'),
      '#field_suffix' => $this->t('collage(s)'),
      '#prefix' => '<div class="container-inline">',
    ];
    $form['images_per_collage'] = [
      '#type' => 'select',
      '#title' => $this->t('Images per collage'),
      '#title_display' => 'invisible',
      '#options' => $options_1_50,
      '#default_value' => $this->getSetting('images_per_collage'),
      '#empty_option' => $this->t('all'),
      '#field_prefix' => $this->t('with'),
      '#field_suffix' => $this->t('image(s) per collage') . ';',
    ];
    $form['images_to_skip'] = [
      '#type' => 'select',
      '#title' => $this->t('Images to skip'),
      '#title_display' => 'invisible',
      '#options' => $options_0_50,
      '#default_value' => $this->getSetting('images_to_skip'),
      '#field_prefix' => $this->t('Skip'),
      '#field_suffix' => $this->t('image(s) from the start'),
      '#suffix' => '</div>',
    ];

    $form['collage_orientation'] = [
      '#type' => 'select',
      '#title' => $this->t('Collage orientation'),
      '#description' => $this->t('Select if it should be a wide collage (landscape) or a tall one (portrait).'),
      '#options' => [
        '0' => $this->t('Landscape'),
        '1' => $this->t('Portrait'),
      ],
      '#default_value' => $this->getSetting('collage_orientation'),
      '#prefix' => '<div class="container-inline">',
      '#field_suffix' => '</br>',
      '#suffix' => '</div>',
    ];
    $form['collage_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Collage width'),
      '#title_display' => 'invisible',
      '#default_value' => $this->getSetting('collage_width'),
      '#min' => 0,
      '#size' => 4,
      '#maxlength' => 4,
      '#prefix' => '<div class="container-inline"><strong>' . t('Collage width & height: ') . '</strong>',
      // '#field_prefix' => '<br/>',
      '#field_suffix' => 'x',
    ];
    $form['collage_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Collage height'),
      '#title_display' => 'invisible',
      '#description' => $this->t('Total collage width and height with all the borders and gaps. If you specify both then the images will be cropped.'),
      '#default_value' => $this->getSetting('collage_height'),
      '#min' => 0,
      '#size' => 4,
      '#maxlength' => 4,
      '#field_suffix' => 'px ',
      '#suffix' => '</div>',
    ];

    $form['collage_border_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Collage border'),
      '#options' => $options_0_50,
      '#default_value' => $this->getSetting('collage_border_size'),
      '#field_suffix' => 'px',
      '#prefix' => '<div class="container-inline">',
    ];
    // TODO: Check whether the script is loaded
    // FIXME: The script is not being loaded
    $form['collage_border_color'] = [
      '#type' => 'textfield',
      '#title' => t('Collage border color'),
      '#default_value' => $this->getSetting('collage_border_color'),
      '#size' => 7,
      '#maxlength' => 7,
      '#suffix' => '<div class="collageformatter-color-picker"></div>' . '</div>',
      '#attached' => [
        'library' => [
          ['system', 'farbtastic'],
        ],
        'js' => [
          drupal_get_path('module', 'collageformatter') . '/js/collageformatter.admin.js' => [
            'type' => 'file',
          ],
        ],
      ],
    ];

    $form['gap_size'] = $form['collage_border_size'];
    $form['gap_size']['#title'] = $this->t('Image gap');
    $form['gap_size']['#default_value'] = $this->getSetting('gap_size');
    $form['gap_color'] = $form['collage_border_color'];
    $form['gap_color']['#title'] = $this->t('Image gap color');
    $form['gap_color']['#default_value'] = $this->getSetting('gap_color');

    $form['border_size'] = $form['collage_border_size'];
    $form['border_size']['#title'] = $this->t('Image border');
    $form['border_size']['#default_value'] = $this->getSetting('border_size');
    $form['border_color'] = $form['collage_border_color'];
    $form['border_color']['#title'] = $this->t('Image border color');
    $form['border_color']['#default_value'] = $this->getSetting('border_color');

    $form['image_link'] = [
      '#title' => $this->t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => $this->t('Nothing'),
      '#options' => [
        'content' => $this->t('Content'),
        'file' => $this->t('File'),
      ],
      '#prefix' => '<div class="container-inline">',
    ];

    $image_styles = image_style_options(FALSE);
    $form['image_link_image_style'] = [
      '#title' => $this->t('Target image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link_image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
    ];

    $modal_options = [];
    if (module_exists('colorbox')) {
      $modal_options['colorbox'] = t('Colorbox');
    }
    if (module_exists('shadowbox')) {
      $modal_options['shadowbox'] = t('Shadowbox');
    }
    if (module_exists('fancybox')) {
      $modal_options['fancybox'] = t('fancyBox');
    }
    if (module_exists('photobox')) {
      $modal_options['photobox'] = t('Photobox');
    }
    if (module_exists('photoswipe')) {
      $modal_options['photoswipe'] = t('PhotoSwipe');
    }
    if (module_exists('lightbox2')) {
      $modal_options['lightbox2'] = t('Lightbox2');
    }
    $form['image_link_modal'] = [
      '#title' => t('Modal gallery'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link_modal'),
      '#empty_option' => t('None'),
      '#options' => $modal_options,
      '#suffix' => '</div>',
    ];

    $form['image_link_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image link class'),
      // '#description' => t('Custom class to add to all image links.'),
      '#default_value' => $this->getSetting('image_link_class'),
      '#size' => 30,
      '#prefix' => '<div class="container-inline">',
    ];
    $form['image_link_rel'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image link rel'),
      // '#description' => t('Custom rel attribute to add to all image links.'),
      '#default_value' => $this->getSetting('image_link_rel'),
      '#size' => 30,
      '#suffix' => '</div>',
    ];
    $form['generate_image_derivatives'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generate image derivatives'),
      '#description' => $this->t('Generate image derivatives used in the collage while rendering it, before displaying.'),
      '#default_value' => $this->getSetting('generate_image_derivatives'),
    ];
    $form['prevent_upscale'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prevent images upscaling'),
      '#description' => $this->t('Generated collage dimensions might be smaller.'),
      '#default_value' => $this->getSetting('prevent_upscale'),
    ];

    $form['advanced'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['advanced']['original_image_reference'] = [
      '#type' => 'radios',
      '#title' => $this->t('Original image reference method'),
      '#description' => $this->t('If you need to add additional image effects to collageformatter image style before the "Collage Formatter" effect then you need to use "Symlink" or "Copy" method.'),
      '#options' => [
        'symlink' => $this->t('Symlink'),
        'fake' => $this->t('Fake image'),
        'copy' => $this->t('Copy'),
      ],
      '#default_value' => $this->getSetting('advanced')['original_image_reference'],
    ];

    return $form;
  }


}
