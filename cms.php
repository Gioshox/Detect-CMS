<?php
// Include necessary libraries
include(__DIR__ . "/vendor/autoload.php");

// Set the script execution time to unlimited
set_time_limit(0);

// set the time 
$time = time();

// Indicate that the process has started.
echo "\nStarting process...\n";

// Array to store processed URLs
$processedUrls = [];

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
        return "Error: " . $e->getMessage();
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
        "WordPress2" => '/<meta\s+name="generator"\s+content="WordPress\s+([\d.]+)"/',
        "WordPress3" => '/<meta\s+name="generator"\s+content="WordPress\s+([\d.]+)"/i',
        "Joomla" => '/<meta name="generator" content="Joomla! (\d+\.\d+)/',
        "Liferay" => '/Powered by Liferay (\d+\.\d+\.\d+)/',
        "HubSpot" => '/<meta name="generator" content="HubSpot"/',
        "WPML" => '/<meta name="generator" content="WPML ver:4\.6\.5 stt:1,18;">/',
        "Odoo" => '/<meta name="generator" content="Odoo"/',
        "Webflow" => '/<meta\s+content="Webflow"\s+name="generator">/',
        "Wix.com Website Builder" => '/<meta\s+name="generator"\s+content="Wix\.com Website Builder">/',
        "concrete5" => '/<meta[^>]*name="generator"[^>]*content="concrete5\s*-\s*\d+\.\d+\.\d+\.\d+"/i',
        "Joomla2" => '/<meta\s+name="generator"\s+content="Joomla!?\s*-\s*([\w\s]+)">/',
        "Chilisystem" => '/<meta\s+name="Generator"\s+content="Chilisystem,\s+(https:\/\/www\.chilisystem\.fi\/)?([\w\s\.]+)">/',
        "Joomla3" => '/<meta\s+name="generator"\s+content="Helix Ultimate\s+-\s+The\s+Most\s+Popular\s+Joomla!\s+Template\s+Framework\.">/',
        "Gatsby" => '/<meta\s+name="generator"\s+content="Gatsby\s+(\d+\.\d+\.\d+)"/',
        "vBulletin" => '/<meta name="generator" content="vBulletin (\d+\.\d+\.\d+)/',
        "Magento" => '/Magento (\d+\.\d+\.\d+)/',
        "ExpressionEngine" => '/Powered by ExpressionEngine (\d+\.\d+)/',
        "Sitecore" => '/<meta name="generator" content="Sitecore (\d+\.\d+)/',
        // ... you can add more
    );

    // groups for cmsPatterns
    $cmsNameMapping = array(
        "WordPress" => array("WordPress", "WordPress2", "WordPress3"),
        "Joomla" => array("Joomla", "Joomla2", "Joomla3"),
        // ... you can add more as needed
    );

    // Ensure the URL starts with "https://"
    $url = preg_replace('~^(?:f|ht)tps?://~i', 'https://', $url);

    // Initialize cURL session to fetch website content
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $fileContents = curl_exec($ch); // Execute cURL request
    curl_close($ch); // Close cURL session

    $javascriptTags = array();
    $jsClasses = "";

    // Extract JavaScript classes from the HTML content
    preg_match_all('/<script.*?class=["\'](.*?)["\'].*?>/', $fileContents, $javascriptTags);
    if (!empty($javascriptTags[1])) {
        $jsClasses = implode(', ', $javascriptTags[1]);
    }

    $cmsInfo = null;
    $cmsVersion = null;

    foreach ($cmsPatterns as $cmsName => $pattern) {
        // Check if any HTML element matches CMS patterns
        if (preg_match($pattern, $fileContents, $matches)) {
            // Check if the array key 1 exists before accessing it
            if (isset($matches[1])) {
                $version = $matches[1];
    
                // Check if $cmsName should be grouped
                foreach ($cmsNameMapping as $groupedName => $namesToGroup) {
                    if (in_array($cmsName, $namesToGroup)) {
                        $cmsInfo = $groupedName . " " . $version;
                        break;
                    }
                }
    
                if (!isset($cmsInfo)) {
                    $cmsInfo = $cmsName . " " . $version;
                }
    
                $cmsVersion = $version; // For extracting the version only.
            } else {
                // Handle the case where array key 1 doesn't exist
                $version = null;
                $cmsName = null;
                $cmsInfo = null; // Format CMS info as "CMSName Version"
                $cmsVersion = $version; // For extracting the version only.
            }
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
        'CMS Name' => $cmsName,
        'CMS Version' => $cmsVersion,
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
    try {
        $cms = @get_meta_tags($url); // Use @ to suppress warnings
        if (!empty($cms['generator'])) {
            $generatorInfo = trim($cms['generator'], "'\"");
            
            // Extract version using a regular expression
            $versionPattern = '/\b(\d+\.\d+\.\d+\.\d+|5\.8\.7|6\.3\.1)\b/';
            if (preg_match($versionPattern, $generatorInfo, $matches)) {
                $version = $matches[0];
                return "Generator Info: " . $generatorInfo . ", Version: " . $version;
            } else {
                return "Generator Info: " . $generatorInfo . ", Version not found";
            }
        } else {
            return "Error: Generator Info not found for " . $url; // Return a message if Generator Info is not found
        }
    } catch (Exception $e) {
        return "Error: Generator Info extraction failed or encountered an error: " . $e->getMessage(); // Handle exceptions and return an error message
    }
}

$websites = [];
if (($handle = fopen('list.csv', 'r')) !== false) {
    
    while (($data = fgetcsv($handle)) !== false) {
        $websiteUrl = $data[0];
        // Check if the URL is not processed before processing it
        if (!in_array($websiteUrl, $processedUrls)) {
            $websites[] = $websiteUrl;
            $processedUrls[] = $websiteUrl; // Add the URL to the processed URLs array
        } else {
            // echo "\nURL is a duplicate: " . $websiteUrl;
        }
    }
    fclose($handle);
}

$outputFile = fopen('debug' . '_' . $time . '.csv', 'w');
$outputFile2 = fopen('results' . '_' . $time . '.csv', 'w');

// Add the header row
fputcsv($outputFile, ['WWW-osoite', 'CMS', 'Lisätiedot', 'Generaattori tiedot', 'JavaScript luokat'], ',');
fputcsv($outputFile2, ['WWW-osoite', 'CMS', 'Versio'], ',');

foreach ($websites as $link) {
    $link = trim($link, "; \t\n\r\0\x0B");
    if (!preg_match("~^(?:f|ht)tps?://~i", $link)) {
        $link = "https://" . $link;
    }

    $detectedCMS = detectCMSUsingLibrary($link);

    // Check if an error occurred while detecting CMS
    if (strpos($detectedCMS, "403") !== false) {
        $detectedCMS = "Connection forbidden and resulted in a 403 error.";
    }elseif (strpos($detectedCMS, "404") !== false) {
        $detectedCMS = "Connection resulted in a `404 Not Found` error.";
    }elseif (strpos($detectedCMS, "28") !== false) {
        $detectedCMS = "Connection timed out and resulted in a cURL error 23.";
    }elseif (strpos($detectedCMS, "429") !== false) {
        $detectedCMS = "GET website address resulted in a 429 error: Too Many Requests.";
    }

    $result = CMSdetectWithVersion($link);

    // Check if an error occurred while detecting CMS with version
    if (strpos($result['CMS Info'], "Error") !== false) {
        $result['CMS Info'] = "Error: CMS detection failed or encountered an error";
    }

    $generatorInfo = extractGeneratorInfo($link);

    // Check if an error occurred while extracting Generator Info
    if (strpos($generatorInfo, "Error") !== false) {
        $generatorInfo = "Error: Generator Info extraction failed or encountered an error";
    }

    // detectedCMS is null or an error was encountered but my method was successfull we can use it as the CMS name.
    if($detectedCMS === null && strpos($generatorInfo, "Error") === true  && $result['CMS Name'] !== null) {
        $detectedCMS = $result['CMS Name'];
    }

    // Add extra detections for versions that might go unnoticed.
    $drupalPattern = '/(\d+)\b/';
    if ($detectedCMS == "Concrete5" && preg_match('/\d+\.\d+\.\d+\.\d+/', $generatorInfo, $matches)) {
        $result['CMS Version'] = $matches[0];
    } elseif (($detectedCMS == "Wordpress" || $detectedCMS == "WordPress") && (preg_match('/\b5\.7\.9\b/', $generatorInfo, $matches) || (preg_match('/\b5\.9\.3\b/', $generatorInfo, $matches) || (preg_match('/\b5\.5\.1\b/', $generatorInfo, $matches) || (preg_match('/\b6\.0\.5\b/', $generatorInfo, $matches) || (preg_match('/\b4\.1\.38\b/', $generatorInfo, $matches) || (preg_match('/\b5\.5\.12\b/', $generatorInfo, $matches) || (preg_match('/\b5\.8\.2\b/', $generatorInfo, $matches) || (preg_match('/\b4\.9\.13\b/', $generatorInfo, $matches) || (preg_match('/\b5\.9\.7\b/', $generatorInfo, $matches) || (preg_match('/\b5\.8\.7\b/', $generatorInfo, $matches) || (preg_match('/\b6\.3\.1\b/', $generatorInfo, $matches) || (preg_match('/\b6\.2\.2\b/', $generatorInfo, $matches) || preg_match('/\b6\.1\.3\b/', $generatorInfo, $matches)))))))))))))) {
        $result['CMS Version'] = $matches[0];
    } elseif ($detectedCMS == "Drupal" && preg_match($drupalPattern, $generatorInfo, $matches)) {
        $result['CMS Version'] = $matches[0];
    } elseif($detectedCMS == "Typo3" && (preg_match('/\b4\.4\b/', $generatorInfo, $matches))) {
        $result['CMS Version'] = $matches[0];
    }

    // Extra checks for picking up CMS names from generator info.
    if (strpos($generatorInfo, "Gatsby")) {
        $detectedCMS = "Gatsby";
    }elseif (strpos($generatorInfo, "Wix.com")) {
        $detectedCMS = "Wix.com";
    }elseif (strpos($generatorInfo, "HubSpot")) {
        $detectedCMS = "HubSpot";
    }

    // Prevent showing a false CMS Version when Elementor is present in the generator tag.
    if(strpos($generatorInfo, "Elementor") && strpos($generatorInfo, $result['CMS Version'])) {
        $result['CMS Version'] = null;
    }
        $rowData = [
            'Website' => str_replace("https://", "", trim($link, "'\"")),
            'CMS Detected' => $detectedCMS,
            'CMS Info' => trim($result['CMS Version'], "'\""),
            'Generator Info' => $generatorInfo,
            'JavaScript Classes' => "JavaScript Classes: " . $result['JavaScript Classes'],
        ];
    
        $rowData2 = [
            'Website' => str_replace("https://", "", trim($link, "'\"")),
            'CMS Detected' => $detectedCMS,
            'CMS Version' => trim($result['CMS Version'], "'\""),
        ];

    fputcsv($outputFile, $rowData);
    fputcsv($outputFile2, $rowData2);
    echo "\nLink: " . $link . " Done";
}

fclose($outputFile);
fclose($outputFile2);
echo "\nProcessing completed. Results are saved in 'results_" . $time . ".csv' and Debug information can be found in 'debug_" . $time .".csv";
?>