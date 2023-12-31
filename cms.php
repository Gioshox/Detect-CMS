<?php
// Include necessary libraries
include(__DIR__ . "/vendor/autoload.php");

// Determines wheter to also print out a debug.csv which contains more information for debugging purposes.
$debug = false;
$fIndex = "";
$filename = "";

foreach ($argv as $commands => $value) {
    if ($value == "-d") {
        echo "\nSet Debug to true. \n";
        $debug = true;
    }

    if ($value == "-f") {
        if ($value === null || empty($value)) {
            echo "Filename not set!";
        } else {
            $fIndex = $commands;
        }
        
    }

    if ($commands - 1 == $fIndex) {
        if ($value === null || empty($value)) {
            echo "Filename not set!";
        } else {
            $filename = $value;
        }   
    }
}

// Set the script execution time to unlimited
set_time_limit(0);

// set the time 
$time = time();

// Indicate that the process has started.
echo "\nStarting process...\n";

// Array to store processed URLs
$processedUrls = [];

// Import the GuzzleHttp namespace
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

// Create a GuzzleHttp client instance
$client = new Client();

/**
 * Function to detect CMS using DetectCMS library.
 *
 * @param string $url The URL of the website to detect CMS for.
 * @return string|null The detected CMS or Unknown if not detected.
 */
function detectCMSUsingLibrary($url, $debug) {
    try {
        $cms = new \DetectCMS\DetectCMS($url);
        if ($cms->getResult()) {
            return $cms->getResult(); // return results
        } else {
            return "Unknown"; // return Unknown
        }
    } catch (\Exception $e) {
        if($debug === true) {
            return "Error: " . $e->getMessage();
        } else {
            return "Error site not found.";
        }
        
    }
}

/**
 * Function to detect CMS with version and JavaScript classes.
 * 
 * @param string $url The URL of the website to detect CMS for.
 * @return array An associative array containing CMS Info and JavaScript Classes.
 */
function CMSdetectWithVersion($url, $client, $debug) {
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
        // ... (other mappings)
    );

    // Ensure the URL starts with "https://"
    $url = preg_replace('~^(?:f|ht)tps?://~i', 'https://', $url);

    try {
        $response = $client->get($url);
        $fileContents = $response->getBody()->getContents();
        
        // Extract JavaScript classes from the HTML content
        $javascriptTags = array();
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

        if ($cmsInfo == null) {
            $cmsInfo = "Unknown"; // Set as "Unknown" if no CMS is detected
        }

        // Return CMS information and JavaScript classes as an associative array
        return [
            'CMS Info' => $cmsInfo,
            'CMS Name' => $cmsName,
            'CMS Version' => $cmsVersion,
            'JavaScript Classes' => $jsClasses,
        ];
    } catch (RequestException $e) {
        // Handle request exceptions (e.g., 404, 403, timeouts) here
        // You can access the response code like this: $e->getResponse()->getStatusCode()
        if ($debug === true) {
            return [
                'CMS Info' => "Error: " . $e->getMessage(),
                'CMS Name' => null,
                'CMS Version' => null,
                'JavaScript Classes' => null,
            ];
        } else {
            return [
                'CMS Info' => "Error site not found.",
                'CMS Name' => null,
                'CMS Version' => null,
                'JavaScript Classes' => null,
            ];
        }
    } catch (Exception $e) {
        if ($debug === true) {
            return [
                'CMS Info' => "Error: " . $e->getMessage(),
                'CMS Name' => null,
                'CMS Version' => null,
                'JavaScript Classes' => null,
            ];
        } else {
            return [
                'CMS Info' => "Error site not found.",
                'CMS Name' => null,
                'CMS Version' => null,
                'JavaScript Classes' => null,
            ];
        }
    }
}

/**
 * Function to extract Generator Info from website meta tags.
 *
 * @param string $url The URL of the website to extract Generator Info from.
 * @return string The Generator Info or a message if not found.
 */
function extractGeneratorInfo($url, $debug) {
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
            return "Generator Info not found."; // Return a message if Generator Info is not found
        }
    } catch (Exception $e) {
        if ($debug === true) {
            return "Error: Generator Info extraction failed or encountered an error: " . $e->getMessage();
        } else {
            return "Error site not found."; // Handle exceptions and return an error message
        }
    }
}

if (!empty($filename)) {
    try {
        $websites = [];
        if (($handle = fopen($filename, 'r')) !== false) {
        
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
        
        $outputFile = fopen('results' . '_' . $time . '.csv', 'w');
        
        // Add the header row
        if($debug === true) {
            fputcsv($outputFile, ['WWW-osoite', 'CMS', 'Versio', 'Generaattori tiedot', 'JavaScript luokat'], ',');
        } else {
            fputcsv($outputFile, ['WWW-osoite', 'CMS', 'Versio'], ',');
        }
    } catch (Exception $e) {
        if ($debug === true) {
            return "Error: " . $e->getMessage();
        } else {
            return "Error. Check your arguments."; // Handle exceptions and return an error message
        }
    }

    foreach ($websites as $link) {
        $link = trim($link, "; \t\n\r\0\x0B");
        if (!preg_match("~^(?:f|ht)tps?://~i", $link)) {
            $link = "https://" . $link;
        }

        $detectedCMS = detectCMSUsingLibrary($link, $debug);

        $result = CMSdetectWithVersion($link, $client, $debug);

        // Check if an error occurred while detecting CMS with version
        if (strpos($result['CMS Info'], "Error") !== false) {
            $result['CMS Info'] = "Error: CMS detection failed or encountered an error";
        }

        $generatorInfo = extractGeneratorInfo($link, $debug);

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

        if($debug === true) {
            $rowData = [
                'Website' => str_replace("https://", "", trim($link, "'\"")),
                'CMS Detected' => $detectedCMS,
                'CMS Info' => trim($result['CMS Version'], "'\""),
                'Generator Info' => $generatorInfo,
                'JavaScript Classes' => "JavaScript Classes: " . $result['JavaScript Classes'],
            ];
        } else {
            $rowData = [
                'Website' => str_replace("https://", "", trim($link, "'\"")),
                'CMS Detected' => $detectedCMS,
                'CMS Version' => trim($result['CMS Version'], "'\""),
            ];
        }
        
        fputcsv($outputFile, $rowData);
        echo "\nLink: " . $link . " Done";
    }

    fclose($outputFile);
    echo "\nProcessing completed. Results are saved in 'results_" . $time . ".csv";
} else {
    echo "Filename not set! Check your arguments.";
}
?>