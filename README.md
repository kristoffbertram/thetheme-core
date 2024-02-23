# The Theme (thetheme)

_Version 1.0.0_

This is my noble attempt at a as-versatile-as-possible Best Practices Boilerplate WordPress Theme using Tailwind. It offers a build-process, sets up certain "enhancements" to WordPress, and using Advanced Custom Fields; offers a bullet-proof technique of adding custom blocks.  

This is a "developer-first" Theme and may not be suited for beginners to WordPress.  
It assumes you understand WP's Theme Hierarchy.

## Developing

### WordPress' functions.php

Is delegated to thetheme-functions.php. Use this file to extend your theme.

### Theme Images

There is no support for further image processing at this time. Any image outside of WP's Media Library is used (and can be stored) as is.

Thetheme itself does offer support for a multitude of formats and sizes.  
Refer to thetheme-modules/images for more information.

### Theme CSS & JS

Thetheme is set up to enqueue app.css and app.js.  
Edit /src/css and /src/js/ and run the command below.  
Your built files will appear in ~/css/ and ~/js/.

```
npm run watch
```

### ACF

Create a new block by defining it in ~/blocks/.  
Optionally: add its output your build process in webpack.mix.js.  
ACF will sync your blocks to ~/acf-json/ allow you to programmatically or manually modify them.

```
mix.postCss("blocks/your-block/your-block.css", "css");
```

See thetheme-modules/acf for more information.

### The Theme Helper

Sets up Tailwind for use with WordPress' theme.json. 

## Upgrading

Upgrade thetheme by replacing the following files;

* thetheme_modules
* functions.php (unless modified)
* header.php (unless modified)
* footer.php (unless modified)
* safelist.txt
* style.css (unless modified)
* package.json (unless modified)
* README.md