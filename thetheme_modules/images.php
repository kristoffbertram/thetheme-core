<?php
function thetheme_image( $image_id , $size , $class_name = null , $as_array = false ) {

    if ($image_id && $size) {

        $image_src = wp_get_attachment_image_src( $image_id , $size )[0]; // first
        $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
        $image_srcset = wp_get_attachment_image_srcset( $image_id, $size );
        $image_sizes = '(max-width: 320px) 320px,(max-width: 640px) 640px,(max-width: 960px) 960px,(max-width: 1024px) 1024px,(max-width: 1280px) 1280px,(max-width: 1600px) 1600px,1024px';

        if ($image_src) {

            $image = '<img alt="' . esc_attr( $image_alt ) . '" src="' . esc_attr( $image_src ) . '" srcset="' . esc_attr( $image_srcset ) . '" sizes="' . esc_attr( $image_sizes ) . '"';
            if ($class_name) {
                $image .= ' class="' . esc_attr( $class_name ) . '"';
            }
            $image .= ' />';

        }

        if ($as_array) {

            return array(
                "image_src" => $image_src ,
                "image_alt" => $image_alt ,
                "image_srcset" => $image_srcset ,
                "image_sizes" => $image_sizes ,
                "image" => $image
            );

        } else {

            return $image;

        }

    }

}

/**
 * Stop WordPress generating intermediate sizes the theme never renders.
 *
 * WordPress creates a derivative file for every registered intermediate size on every
 * upload. A theme that renders none of them pays for all of them: disk, upload time,
 * and backup weight, permanently. Removing a name from `intermediate_image_sizes`
 * stops future generation; it does not delete files already on disk.
 *
 * DEFAULT IS AN EMPTY LIST — this does nothing unless a theme opts in.
 *
 * That default is deliberate and is the whole design of this filter. The obvious
 * alternative, shipping the usual four names as the default and letting themes opt
 * out, breaks sites silently: at least one theme in the estate renders
 * `medium_large` directly (it is the size its article listing and single-article
 * templates ask for), so a default-on stripper would blank those images with no
 * error and no log line. An opt-in cannot do that to anyone. Themes that want the
 * behaviour say so in one line; themes that never think about it are unaffected.
 *
 * USAGE — from the app layer, e.g. thetheme_functions/app/images.php:
 *
 *     add_filter('thetheme_removed_default_image_sizes', function (array $sizes): array {
 *         return [...$sizes, 'medium', 'medium_large', 'large', '1536x1536', '2048x2048'];
 *     });
 *
 * BEFORE YOU ADD THAT LINE, check what the theme actually renders. Two traps:
 *
 *  1. `medium_large` (768px) exists for srcset, not for direct use, so it is easy to
 *     strip without noticing — until a template that names it explicitly renders
 *     nothing.
 *  2. Stripping the defaults on a theme that also registers no sizes of its own
 *     leaves `thumbnail` and `full` as the entire inventory. srcset then has nothing
 *     to choose from and every image is served at full upload resolution, on every
 *     device. That is a real page-weight decision, not a tidy-up — make it knowingly.
 *
 * `thumbnail` is deliberately absent from the example: wp-admin's media grid renders
 * it, so removing it degrades the library UI rather than the front end.
 */
function thetheme_remove_default_image_sizes( $sizes ) {

    $remove = (array) apply_filters('thetheme_removed_default_image_sizes', []);

    if (empty($remove)) {
        return $sizes;
    }

    return array_values(array_diff($sizes, $remove));

}
add_filter('intermediate_image_sizes', 'thetheme_remove_default_image_sizes', 10, 1);
