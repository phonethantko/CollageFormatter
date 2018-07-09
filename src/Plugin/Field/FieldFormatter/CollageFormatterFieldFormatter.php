<?php

namespace Drupal\collageformatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'collageformatter' formatter.
 *
 * @FieldFormatter(
 *   id = "collageformatter",
 *   label = @Translation("Collage Formatter"),
 *   field_types = {
 *     "Image"
 *   }
 * )
 */

class CollageFormatterFieldFormatter extends FormatterBase {

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
    $form = parent::settingsForm($form, $form_state);
    $form['csv_column'] = [
      '#title' => $this->t('Column name from CSV file.'),
      '#description' => $this->t('The column name that you want to render from uploaded CSV file. It is expected that all uploaded CSV files should be of same format.'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('csv_column'),
    ];
    $form['show_file'] = [
      '#title' => $this->t('Display CSV file.'),
      '#description' => $this->t('Check this checkbox to display CSV file in generic file formatter.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('show_file'),
    ];
    return $form;
  }

  private function _settingsForm($settings) {
    // drupal_map_assoc() has been removed in Drupal 8
    $options_0_50 = array_combine(range(0, 50), range(0, 50));
    $options_1_50 = array_combine(range(1, 50), range(1, 50));

    $element['collage_number'] = [
      '#type' => 'select',
      '#title' => t('Number of collages'),
      '#title_display' => 'invisible',
      '#options' => $options_1_50,
      '#default_value' => $settings['collage_number'],
      '#field_prefix' => t('Generate'),
      '#field_suffix' => t('collage(s)'),
      '#prefix' => '<div class="container-inline">',
    ];
    $element['images_per_collage'] = [
      '#type' => 'select',
      '#title' => t('Images per collage'),
      '#title_display' => 'invisible',
      '#options' => $options_1_50,
      '#default_value' => $settings['images_per_collage'],
      '#empty_option' => t('all'),
      '#field_prefix' => t('with'),
      '#field_suffix' => t('image(s) per collage') . ';',
    ];
    $element['images_to_skip'] = [
      '#type' => 'select',
      '#title' => t('Images to skip'),
      '#title_display' => 'invisible',
      '#options' => $options_0_50,
      '#default_value' => $settings['images_to_skip'],
      '#field_prefix' => t('Skip'),
      '#field_suffix' => t('image(s) from the start'),
      '#suffix' => '</div>',
    ];

    $element['collage_orientation'] = [
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
    $element['collage_width'] = [
      '#type' => 'textfield',
      '#title' => t('Collage width'),
      '#title_display' => 'invisible',
      '#default_value' => $settings['collage_width'],
      '#min' => 0,
      '#size' => 4,
      '#maxlength' => 4,
      '#prefix' => '<div class="container-inline"><strong>' . t('Collage width & height') . '</strong>',
      // '#field_prefix' => '</br>',
      '#field_suffix' => 'x',
    ];
    $element['collage_height'] = [
      '#type' => 'textfield',
      '#title' => t('Collage height'),
      '#title_display' => 'invisible',
      '#description' => t('Total collage width and height with all the borders and gaps. If you specify both then the images will be cropped.'),
      '#default_value' => $settings['collage_height'],
      '#min' => 0,
      '#size' => 4,
      '#maxlength' => 4,
      '#field_suffix' => 'px</br>',
      '#suffix' => '</div>',
    ];

    $element['collage_border_size'] = [
      '#type' => 'select',
      '#title' => t('Collage border'),
      '#options' => $options_0_50,
      '#default_value' => $settings['collage_border_size'],
      '#field_suffix' => 'px',
      '#prefix' => '<div class="container-inline">',
    ];
    //TODO Check whether the script is loaded
    $element['collage_border_color'] = [
      '#type' => 'textfield',
      '#title' => t('Collage border color'),
      '#default_value' => $settings['collage_border_color'],
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

    $element['gap_size'] = $element['collage_border_size'];
    $element['gap_size']['#title'] = t('Image gap');
    $element['gap_size']['#default_value'] = $settings['gap_size'];
    $element['gap_color'] = $element['collage_border_color'];
    $element['gap_color']['#title'] = t('Image gap color');
    $element['gap_color']['#default_value'] = $settings['gap_color'];

    $element['border_size'] = $element['collage_border_size'];
    $element['border_size']['#title'] = t('Image border');
    $element['border_size']['#default_value'] = $settings['border_size'];
    $element['border_color'] = $element['collage_border_color'];
    $element['border_color']['#title'] = t('Image border color');
    $element['border_color']['#default_value'] = $settings['border_color'];

    $element['image_link'] = [
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
    $element['image_link_image_style'] = [
      '#title' => t('Target image style'),
      '#type' => 'select',
      '#default_value' => $settings['image_link_image_style'],
      '#empty_option' => t('None (original image)'),
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
    $element['image_link_modal'] = [
      '#title' => t('Modal gallery'),
      '#type' => 'select',
      '#default_value' => $settings['image_link_modal'],
      '#empty_option' => t('None'),
      '#options' => $modal_options,
      '#suffix' => '</div>',
    ];

    $element['image_link_class'] = [
      '#type' => 'textfield',
      '#title' => t('Image link class'),
      // '#description' => t('Custom class to add to all image links.'),
      '#default_value' => $settings['image_link_class'],
      '#size' => 30,
      '#prefix' => '<div class="container-inline">',
    ];
    $element['image_link_rel'] = [
      '#type' => 'textfield',
      '#title' => t('Image link rel'),
      // '#description' => t('Custom rel attribute to add to all image links.'),
      '#default_value' => $settings['image_link_rel'],
      '#size' => 30,
      '#suffix' => '</div>',
    ];
    $element['generate_image_derivatives'] = [
      '#type' => 'checkbox',
      '#title' => t('Generate image derivatives'),
      '#description' => t('Generate image derivatives used in the collage while rendering it, before displaying.'),
      '#default_value' => $settings['generate_image_derivatives'],
    ];
    $element['prevent_upscale'] = [
      '#type' => 'checkbox',
      '#title' => t('Prevent images upscaling'),
      '#description' => t('Generated collage dimensions might be smaller.'),
      '#default_value' => $settings['prevent_upscale'],
    ];

    $element['advanced'] = [
      '#type' => 'fieldset',
      '#title' => t('Advanced settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $element['advanced']['original_image_reference'] = [
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

    return $element;
  }
}
