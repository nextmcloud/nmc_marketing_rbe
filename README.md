# MagentaCLOUD marketing integration

This app externalizes the integration of Telekom marketing tools into a Nextcloud app.

The app is configurable for different test and production environments
(also for reuse over multiple NextCloud customized tenants).

## Configuration

In `<nextcloud_install_dir>/config/config.php` or in a dedicated 
`<nextcloud_install_dir>/config/nmc_marketing_config.php`:
```
<?php

$CONFIG = [
  'nmc_marketing' => array(
    // the marketing script to load
    'script_url' => 'https://my.marketing.source.site/environment/utag.js',
    // Telekom font source 
    'font_url' => 'https://telekom.fonts.source.site'
  ),

];
```
