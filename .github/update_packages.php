<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Yaml\Yaml;

function getLatestVersion($packageName)
{
    $client = new Client();
    $url = "https://repo.packagist.org/p/{$packageName}.json";

    try {
        $response = $client->get($url);
        $data = json_decode($response->getBody(), true);

        $versions = array_keys($data['packages'][$packageName]);
        usort($versions, 'version_compare');

        $latestVersion = end($versions);

        // Extract major.minor version (e.g., "13.3.3" becomes "13.x")
        $versionParts = explode('.', $latestVersion);
        if (count($versionParts) > 1) {
            return $versionParts[0] . '.' . 'x';
        }

        return null;
    } catch (RequestException $e) {
        echo "Package {$packageName} not found on Packagist. Trying Drupal.org...\n";
        return getLatestVersionFromDrupalOrg($packageName);
    }
}

function getLatestVersionFromDrupalOrg($packageName)
{
    $client = new Client();
    $packageName = str_replace('drupal/', '', $packageName); // Remove "drupal/" prefix
    $drupalApiUrl = "https://www.drupal.org/api-d7/node.json?field_project_machine_name={$packageName}";

    try {
        $response = $client->get($drupalApiUrl);
        $data = json_decode($response->getBody(), true);

        if (!empty($data['list']) && isset($data['list'][0]['field_release_version'])) {
            $latestVersion = $data['list'][0]['field_release_version'];
            return $latestVersion;
        } else {
            echo "No releases found for {$packageName} on Drupal.org.\n";
            return null;
        }
    } catch (RequestException $e) {
        echo "Error fetching data for {$packageName} on Drupal.org: " . $e->getMessage() . PHP_EOL;
        return null;
    }
}

function isMajorUpdate($currentVersion, $latestVersion)
{
    if (!$currentVersion || !$latestVersion) {
        return false;
    }

    $currentMajor = explode('.', $currentVersion)[0];
    $latestMajor = explode('.', $latestVersion)[0];

    return $currentMajor !== $latestMajor;
}

function updatePackagesYaml($filePath)
{
    $fileLines = file($filePath);
    $comments = [];

    // Extract comments
    foreach ($fileLines as $line) {
        if (preg_match('/^\s*#/', $line)) {
            $comments[] = $line;
        }
    }

    $packages = Yaml::parseFile($filePath);

    foreach ($packages as $package => &$details) {
        if (isset($details['core_matrix'])) {
            // Update only '*' entry
            if (isset($details['core_matrix']['*'])) {
                $currentVersion = $details['core_matrix']['*']['version'] ?? null;
                $latestVersion = getLatestVersion($package);

                if ($latestVersion && isMajorUpdate($currentVersion, $latestVersion)) {
                    $details['core_matrix']['*']['version'] = $latestVersion;
                    echo "Updated $package for '*' to version $latestVersion.\n";
                }
            } else {
                echo "Skipping $package as '*' is not defined in core_matrix.\n";
            }
        } else {
            // Update non-core_matrix packages
            $currentVersion = $details['version'] ?? null;
            $latestVersion = getLatestVersion($package);

            if ($latestVersion && isMajorUpdate($currentVersion, $latestVersion)) {
                $details['version'] = $latestVersion;
                echo "Updated $package to version $latestVersion.\n";
            }
        }
    }

    // Write back the YAML, appending the comments
    file_put_contents($filePath, implode('', $comments) . "\n" . Yaml::dump($packages, 2));
}

$filePath = '../config/packages.yml';

updatePackagesYaml($filePath);
