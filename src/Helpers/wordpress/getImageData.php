<?php
/**
 * Retrieves image data by reference (ID, array, or object) and size
 *
 * @param mixed $reference The attachment ID, array, or object returned from ACF image field
 * @param string $size The image size (default: 'full')
 * @return array An array containing the image data or empty array if invalid
 */
function getImageData($reference, $size = 'full')
{
    if (!$reference)
        return [];

    // Handle if reference is an array or object from ACF
    if (is_array($reference) && isset($reference['ID'])) {
        $id = $reference['ID'];
    } elseif (is_object($reference) && isset($reference->ID)) {
        $id = $reference->ID;
    } elseif (is_numeric($reference)) {
        $id = $reference;
    } else {
        return [];
    }

    $image = wp_get_attachment_image_src($id, $size);
    if (!$image)
        return [];

    $image_data = [
        'src'    => $image[0],
        'width'  => $image[1],
        'height' => $image[2],
        'alt'    => get_post_meta($id, '_wp_attachment_image_alt', true),
        'full'   => wp_get_attachment_image_src($id, 'full')[0],
        'srcset' => wp_get_attachment_image_srcset($id, $size),
        'sizes'  => wp_get_attachment_image_sizes($id, $size),
    ];

    return $image_data;
}
