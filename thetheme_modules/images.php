<?php
function thetheme_image( $image_id , $size , $class_name = null , $as_array = false ) {

    if ($image_id && $size) {

        $image_src = wp_get_attachment_image_src( $image_id , $size )[0]; // first
        $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
        $image_srcset = wp_get_attachment_image_srcset( $image_id, $size );
        $image_sizes = '(max-width: 320px) 320px,(max-width: 640px) 640px,(max-width: 960px) 960px,(max-width: 1024px) 1024px,(max-width: 1280px) 1280px,(max-width: 1600px) 1600px,1024px';

        if ($image_src) {

            $image = '<img alt="'.$image_alt.'" src="' . esc_attr( $image_src ) . '" srcset="' . esc_attr( $image_srcset ) . '" sizes="' . esc_attr( $image_sizes ) . '"';
            if ($class_name) {
                $image .= ' class="' . $class_name . '"';
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