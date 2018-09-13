<?php

/**
 * @file
 * Stores the list of Acquia product packages.
 */

// List the composer package strings exactly as recommended in each product's
// public documentation.
// @codingStandardsIgnoreStart
return [
  "acquia/acsf-tools", // https://github.com/acquia/acsf-tools
  "drupal/acquia_commercemanager", // https://www.drupal.org/project/acquia_commercemanager
  "drupal/acquia_connector", // https://www.drupal.org/project/acquia_connector
  "drupal/acquia_contenthub:~1.0", // https://www.drupal.org/project/acquia_contenthub
  "drupal/acquia_lift", // https://www.drupal.org/project/acquia_lift
  "drupal/acquia_purge", // https://www.drupal.org/project/acquia_purge
  "drupal/acsf", // https://www.drupal.org/project/acsf
  // @todo Can't install Lightning modules yet:
  //
  // Your requirements could not be resolved to an installable set of packages.
  //
  //  Problem 1
  //    - Installation request for drupal/core (locked at 8.5.7, required as 8.5.*) -> satisfiable by drupal/core[8.5.7].
  //    - drupal/lightning_core 3.x-dev requires drupal/core 8.6.x-dev -> satisfiable by drupal/core[8.6.x-dev].
  //    - drupal/lightning_core 3.1.0 requires drupal/core ~8.6.1 -> satisfiable by drupal/core[8.6.x-dev].
  //    - Conclusion: don't install drupal/core 8.6.x-dev
  //    - Installation request for drupal/lightning_core ^3.1 -> satisfiable by drupal/lightning_core[3.x-dev, 3.1.0].
  //
  //  "drupal/lightning_api", // https://www.drupal.org/project/lightning_api
  //  "drupal/lightning_core", // https://www.drupal.org/project/lightning_core
  //  "drupal/lightning_layout", // https://www.drupal.org/project/lightning_layout
  //  "drupal/lightning_media", // https://www.drupal.org/project/lightning_media
  //  "drupal/lightning_workflow", // https://www.drupal.org/project/lightning_workflow
  "drupal/media_acquiadam", // https://www.drupal.org/project/media_acquiadam
];
// @codingStandardsIgnoreEnd
