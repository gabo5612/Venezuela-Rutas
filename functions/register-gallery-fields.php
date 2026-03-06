<?php
/**
 * ACF field group: Gallery page template
 * Assigned to: Template Name = Galería (page-gallery.php)
 */
add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) return;

    acf_add_local_field_group([
        'key'      => 'group_gallery_page',
        'title'    => 'Galería — Contenido',
        'fields'   => [
            [
                'key'           => 'field_gal_eyebrow',
                'label'         => 'Eyebrow (texto sobre el título)',
                'name'          => 'gallery_eyebrow',
                'type'          => 'text',
                'placeholder'   => 'Archivo Visual',
                'instructions'  => 'Texto pequeño que aparece encima del título.',
            ],
            [
                'key'           => 'field_gal_title',
                'label'         => 'Título',
                'name'          => 'gallery_title',
                'type'          => 'text',
                'placeholder'   => 'Nuestra Galería',
            ],
            [
                'key'           => 'field_gal_desc',
                'label'         => 'Descripción',
                'name'          => 'gallery_desc',
                'type'          => 'textarea',
                'rows'          => 3,
                'placeholder'   => 'Breve descripción de la galería.',
            ],
            [
                'key'          => 'field_gal_sections',
                'label'        => 'Secciones',
                'name'         => 'gallery_sections',
                'type'         => 'repeater',
                'instructions' => 'Agrupa las fotos en secciones con título opcional.',
                'button_label' => 'Agregar sección',
                'layout'       => 'block',
                'sub_fields'   => [
                    [
                        'key'         => 'field_gal_sec_title',
                        'label'       => 'Título de sección',
                        'name'        => 'section_title',
                        'type'        => 'text',
                        'placeholder' => 'Ej: Cumbres / Día 1 / Campamento Base',
                        'required'    => 0,
                    ],
                    [
                        'key'          => 'field_gal_sec_images',
                        'label'        => 'Imágenes',
                        'name'         => 'section_images',
                        'type'         => 'gallery',
                        'required'     => 1,
                        'return_format'=> 'array',
                        'preview_size' => 'medium',
                        'insert'       => 'append',
                        'library'      => 'all',
                        'min'          => 1,
                    ],
                ],
            ],
            [
                'key'           => 'field_gal_images',
                'label'         => 'Galería simple (alternativa a secciones)',
                'name'          => 'gallery_images',
                'type'          => 'gallery',
                'instructions'  => 'Úsalo si no necesitas dividir en secciones.',
                'return_format' => 'array',
                'preview_size'  => 'medium',
                'insert'        => 'append',
                'library'       => 'all',
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'page_template',
                    'operator' => '==',
                    'value'    => 'page-gallery.php',
                ],
            ],
        ],
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
    ]);
});
