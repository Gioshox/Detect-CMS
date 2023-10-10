# CMS Detection Script

This PHP script is designed to detect Content Management Systems (CMS) and related information for a list of websites provided in a `list.csv` file. It utilizes the [DetectCMS](https://github.com/Krisseck/Detect-CMS) library to identify the CMS used on each website and extracts additional information such as CMS version, Generator Info, and JavaScript classes.

## Features

- Detects the CMS used on websites.
- Extracts CMS version (if available) and Generator Info.
- Collects JavaScript classes used on the websites.

## Prerequisites

Before running the script, ensure you have the following prerequisites:

- PHP installed on your system.
- Composer installed for managing PHP dependencies.

## Installation

1. Clone this repository to your local machine:

2. Navigate to the project directory:

3. Install PHP dependencies using Composer

## Usage

Create a `list.csv` file containing a list of website URLs that you want to analyze. Each URL should be in a separate row.

Example `list.csv`:
```
www.example1.com
www.example2.com
www.example3.com
https://example4.com
example5.com
```

2. Run the script by executing the following command:

   `php cms.php -f yourlist.csv`

3. To print out debug information with the results you can executing the following command:

   `php cms.php -d -f yourlist.csv`

4. The script will process each website URL from the `yourlist.csv` file, detect the CMS, and generate a `results_timestamp.csv` file with the detected information. The timestamp in the filename ensures that each run creates a unique output file.

5. Once the script has finished processing all websites, you will see a completion message in the terminal.

## Output

The script generates a CSV file named `results_timestamp.csv` containing the following columns:

- `Website`: The processed website's URL.
- `CMS Detected`: The detected CMS or an empty field if the CMS couldn't be identified.
- `CMS Info`: The version or details of the detected CMS.
- `Generator Info`: Information extracted from the website's meta tags (if available).
- `JavaScript Classes`: A list of JavaScript classes found on the website.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Third-Party Code

This project includes code from a third-party source (RED_HAWK) under the MIT License. You can find the full license text in the `THIRD-PARTY-LICENSE` file in the root directory of this project.