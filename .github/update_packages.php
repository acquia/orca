<?php

/**
 * @file
 * Script to update package versions in a YAML configuration file.
 */

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Yaml\Yaml;

/**
 * Fetches the latest version of a package from Packagist or Drupal.org.
 *
 * @param string $packageName
 *   The name of the package.
 *
 * @return string|null
 *   The latest version string, or NULL if not found.
 */
function get_latest_version(string $packageName) {
  return get_latest_version_from_packagist($packageName)
      ?? get_latest_version_from_drupal_org($packageName);
}

/**
 * Fetches the latest version of a package from Packagist.
 *
 * @param string $packageName
 *   The name of the package.
 *
 * @return string|null
 *   The latest version string, or NULL if not found.
 */
function get_latest_version_from_packagist(string $packageName) {
  $client = new Client();
  $url = "https://repo.packagist.org/p/{$packageName}.json";

  try {
    $response = $client->get($url);
    $data = json_decode($response->getBody(), TRUE);

    $versions = array_keys($data['packages'][$packageName]);
    usort($versions, 'version_compare');

    $latestVersion = end($versions);

    // Extract major.minor version (e.g., "13.3.3" becomes "13.x")
    $versionParts = explode('.', $latestVersion);
    if (count($versionParts) > 1) {
      return $versionParts[0] . '.x';
    }

    return NULL;
  }
  catch (RequestException $e) {
    return NULL;
  }
}

/**
 * Fetches the latest version of a package from Drupal.org.
 *
 * @param string $packageName
 *   The name of the package.
 *
 * @return string|null
 *   The latest version string, or NULL if not found.
 */
function get_latest_version_from_drupal_org(string $packageName) {
  $client = new Client();
  // Remove "drupal/" prefix.
  $packageName = str_replace('drupal/', '', $packageName);
  $drupalApiUrl = "https://www.drupal.org/api-d7/node.json?field_project_machine_name={$packageName}";

  try {
    $response = $client->get($drupalApiUrl);
    $data = json_decode($response->getBody(), TRUE);

    if (!empty($data['list']) && isset($data['list'][0]['field_release_version'])) {
      return $data['list'][0]['field_release_version'];
    }

    echo "No new releases found for {$packageName} on Drupal.org.\n";
    return NULL;
  }
  catch (RequestException $e) {
    echo "Error fetching data for {$packageName} on Drupal.org: " . $e->getMessage() . PHP_EOL;
    return NULL;
  }
}

/**
 * Determines if latest version is a major update compared to current version.
 *
 * @param string|null $currentVersion
 *   The current version.
 * @param string|null $latestVersion
 *   The latest version.
 *
 * @return bool
 *   TRUE if it is a major update, FALSE otherwise.
 */
function is_major_update(?string $currentVersion, ?string $latestVersion) {
  if (!$currentVersion || !$latestVersion) {
    return FALSE;
  }

  $currentMajor = explode('.', $currentVersion)[0];
  $latestMajor = explode('.', $latestVersion)[0];

  return $currentMajor !== $latestMajor;
}

/**
 * Updates package versions in a YAML file.
 *
 * @param string $filePath
 *   The path to the YAML file.
 */
function update_packages_yaml(string $filePath) {
  $fileLines = file($filePath);
  $comments = [];

  // Extract comments.
  foreach ($fileLines as $line) {
    if (preg_match('/^\s*#/', $line)) {
      $comments[] = $line;
    }
  }

  $packages = Yaml::parseFile($filePath);

  foreach ($packages as $package => &$details) {
    if (isset($details['core_matrix'])) {
      // Update only '*' entry.
      if (isset($details['core_matrix']['*'])) {
        $currentVersion = $details['core_matrix']['*']['version'] ?? NULL;
        $latestVersion = get_latest_version($package);

        if ($latestVersion && is_major_update($currentVersion, $latestVersion)) {
          $details['core_matrix']['*']['version'] = $latestVersion;
          echo "Updated $package for '*' to version $latestVersion.\n";
        }
      }
      else {
        echo "Skipping $package as '*' is not defined in core_matrix.\n";
      }
    }
    else {
      // Update non-core_matrix packages.
      $currentVersion = $details['version'] ?? NULL;
      $latestVersion = get_latest_version($package);

      if ($latestVersion && is_major_update($currentVersion, $latestVersion)) {
        $details['version'] = $latestVersion;
        echo "Updated $package to version $latestVersion.\n";
      }
    }
  }

  // Write back the YAML, appending the comments.
  file_put_contents($filePath, implode('', $comments) . "\n" . Yaml::dump($packages, 2));
}

// File path to the YAML configuration.
$filePath = '../config/packages.yml';

update_packages_yaml($filePath);
