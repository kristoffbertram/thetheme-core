# The Theme Core (thetheme)

_Version 1.0.1_

This is a stand-alone update to @thetheme containing updates to each of its inner workings. It intents to limit itself to ``~/thetheme-modules`` and configuration options (as user defined in ``~/thetheme-functions.php``).  

## Installing

#### Confirm

Confirm if the following files in your theme remained unaltered and can be replaced;

* thetheme_modules
* functions.php
* header.php
* footer.php
* safelist.txt
* style.css
* README.md (will replace @thetheme/README. For the very latest version, refer to @thetheme)
* CHANGELOG.md (will replace @thetheme/CHANGELOG. For the very latest version, refer to @thetheme)
* **package.json**

#### Cloning

1. Clone this repository to a separate directory.

```
git clone git@github-thermoking:kristoffbertram/thetheme-core.
```

2. Copy the required files.

## Usage

### Theme Images

Built-in support for custom image sizes.  
Refer to thetheme-modules/images for more information.

```
$selected_image_sizes = [
    'thumb-xs','thumb-sm','thumb-md','thumb-lg','thumb-xl',
    '169-sm','169-md','169-lg','169-xl',
    '43-sm','43-md','43-lg'
];
$custom_image_sizes = [
    //["your-custom-square", 500, 500, false, "Custom Square Medium"]
];
$theme_image_sizes = new The_Theme_Image_Sizes($selected_image_sizes, $custom_image_sizes);
```

### Theme Allow Custom Mimes

Allow uploading custom mimes to WP's Media Library.

```
$new_mimes = [
    'svg' => 'image/svg+xml',
    'zip' => 'application/zip',
    'gz'  => 'application/x-gzip',
];
new The_Theme_Allow_Custom_Mimes($new_mimes);
```

## TODO

* Introduce more configuration methods for Modules. 
    * Menu's
    * Card Scaffolder
    * thetheme-helper
    * ShouldWrapACF

## Changelog

Please refer to CHANGELOG.md.