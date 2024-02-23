<?php
/**
 * The Theme Header
 * 
 * @since 1.0.0
 * 
 * Sets the @thetheme <html> headers and
 * injects the header template parts
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, shrink-to-fit=no">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="<?php bloginfo('name'); ?>">
<link rel="profile" href="http://gmpg.org/xfn/11">
<?php wp_head(); ?>
</head>
<?php
// Add any targeting conditionals to your <body>
$body_class = '';
// For post (and not page) types
if ('post' === get_post_type()) {
    $body_class = 'post-type-post';
}
?>
<body <?php body_class($body_class); ?>>

<?php
get_template_part('template-parts/header');