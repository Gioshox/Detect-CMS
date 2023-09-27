<?php
// Include necessary libraries
include(__DIR__ . "/vendor/autoload.php");

// Set the script execution time to unlimited
set_time_limit(0);

/**
 * Function to detect CMS using DetectCMS library.
 *
 * @param string $url The URL of the website to detect CMS for.
 * @return string|null The detected CMS or null if not detected.
 */
function detectCMSUsingLibrary($url) {
    try {
        $cms = new \DetectCMS\DetectCMS($url);
        if ($cms->getResult()) {
            return $cms->getResult(); // return results
        } else {
            return null; // return null
        }
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage(); // for some reason the whole process stops when running into an error so we need to handle it.
    }
}

/**
 * Function to detect CMS with version and JavaScript classes.
 * 
 * @param string $url The URL of the website to detect CMS for.
 * @return array An associative array containing CMS Info and JavaScript Classes.
 */
function CMSdetectWithVersion($url) {
    $jsClasses = "";
    $cmsPatterns = array(
        // CMS patterns
        "Drupal" => '/<meta name="generator" content="Drupal (\d+(\.\d+)*)/',
        "WordPress" => '/<meta name="generator" content="WordPress (\d+\.\d+\.\d+)/',
        "WordPress" => '/<meta\s+name="generator"\s+content="WordPress\s+([\d.]+)"/',
        "Joomla" => '/<meta name="generator" content="Joomla! (\d+\.\d+)/',
        "Liferay" => '/Powered by Liferay (\d+\.\d+\.\d+)/',
        "Elementor" => '/<meta name="generator" content="Elementor (\d+(\.\d+)*)/',
        "HubSpot" => '/<meta name="generator" content="HubSpot"/',
        "WPML" => '/<meta name="generator" content="WPML ver:4\.6\.5 stt:1,18;">/',
        "Odoo" => '/<meta name="generator" content="Odoo"/',
        "Webflow" => '/<meta\s+content="Webflow"\s+name="generator">/',
        "Wix.com Website Builder" => '/<meta\s+name="generator"\s+content="Wix\.com Website Builder">/',
        "concrete5" => '/<meta\s+name="generator"\s+content="concrete5\s+-\s+(\d+\.\d+\.\d+(\.\d+)?)">/',
        "Joomla" => '/<meta\s+name="generator"\s+content="Joomla!?\s*-\s*([\w\s]+)">/',
        "Chilisystem" => '/<meta\s+name="Generator"\s+content="Chilisystem,\s+(https:\/\/www\.chilisystem\.fi\/)?([\w\s\.]+)">/',
        "Joomla" => '/<meta\s+name="generator"\s+content="Helix Ultimate\s+-\s+The\s+Most\s+Popular\s+Joomla!\s+Template\s+Framework\.">/',
        "Gatsby" => '/<meta\s+name="generator"\s+content="Gatsby\s+(\d+\.\d+\.\d+)"/',
        "vBulletin" => '/<meta name="generator" content="vBulletin (\d+\.\d+\.\d+)/',
        "Magento" => '/Magento (\d+\.\d+\.\d+)/',
        "ExpressionEngine" => '/Powered by ExpressionEngine (\d+\.\d+)/',
        "Sitecore" => '/<meta name="generator" content="Sitecore (\d+\.\d+)/',
        // ... you can add more
    );

    // Ensure the URL starts with "https://"
    $url = preg_replace('~^(?:f|ht)tps?://~i', 'https://', $url);

    // Initialize cURL session to fetch website content
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $fileContents = curl_exec($ch); // Execute cURL request
    curl_close($ch); // Close cURL session

    $javascriptTags = array();

    // Extract JavaScript classes from the HTML content
    preg_match_all('/<script.*?class=["\'](.*?)["\'].*?>/', $fileContents, $javascriptTags);
    if (!empty($javascriptTags[1])) {
        $jsClasses = implode(', ', $javascriptTags[1]);
    }

    $cmsInfo = null;

    foreach ($cmsPatterns as $cmsName => $pattern) {
        // Check if any HTML element matches CMS patterns
        if (preg_match($pattern, $fileContents, $matches)) {
            $version = $matches[1];
            $cmsInfo = $cmsName . " " . $version; // Format CMS info as "CMSName Version"
            break;
        }
    }

    /* Uncomment this if you wan't to set a value for null like Unknown for example.
    if ($cmsInfo == null) {
        $cmsInfo = "Unknown"; // Set as "Unknown" if no CMS is detected
    }
    */

    // Return CMS information and JavaScript classes as an associative array
    return [
        'CMS Info' => $cmsInfo,
        'JavaScript Classes' => $jsClasses,
    ];
}

/**
 * Function to extract Generator Info from website meta tags.
 *
 * @param string $url The URL of the website to extract Generator Info from.
 * @return string The Generator Info or a message if not found.
 */
function extractGeneratorInfo($url) {
    $cms = get_meta_tags($url); // Get meta tags from the website

    if (!empty($cms['generator'])) {
        return "Generator Info: " . trim($cms['generator'], "'\""); // Format and return Generator Info
    } else {
        return "Generator Info not found for " . $url; // Return a message if Generator Info is not found
    }
}

$websites = [];
if (($handle = fopen('list.csv', 'r')) !== false) {
    while (($data = fgetcsv($handle)) !== false) {
        $websiteUrl = $data[0];
        $websites[] = $websiteUrl;
    }
    fclose($handle);
}

$outputFile = fopen('results' . '_' . time() . '.csv', 'w');

foreach ($websites as $link) {
    $link = trim($link, "; \t\n\r\0\x0B");
    if (!preg_match("~^(?:f|ht)tps?://~i", $link)) {
        $link = "https://" . $link;
    }

    $detectedCMS = detectCMSUsingLibrary($link); // Detect CMS using the library

    if (strpos($detectedCMS, "Error") === false) {
        // CMS detection was successful, and it's not an error message
        // echo "Detected CMS: " . $detectedCMS . "\n";
    } else {
        // CMS detection failed or encountered an error
        // Handle the error (e.g., log it or display a message)
        // echo "CMS couldn't be detected\n";
        // $detectedCMS = "Unknown";
    }

    $result = CMSdetectWithVersion($link); // Detect CMS with version and JavaScript classes
    $generatorInfo = extractGeneratorInfo($link); // Extract Generator Info

    $rowData = [
        'Website' => str_replace("https://", "", trim($link, "'\"")),
        'CMS Detected' => $detectedCMS, // Add detected CMS to row data
        'CMS Info' => trim($result['CMS Info'], "'\""),
        'Generator Info' => $generatorInfo,
        'JavaScript Classes' => "JavaScript Classes: " . $result['JavaScript Classes'],
    ];

    fputcsv($outputFile, $rowData); // Write row data to the output CSV file
}

fclose($outputFile); // Close the output CSV file
echo "Processing completed. Results are saved in 'results_" . time() . ".csv'."; // Provide processing completion message
?>