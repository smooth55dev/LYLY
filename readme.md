### LYLY Printing Data Creation Command

#### Preparing the PHP Environment

[Installing PHP]

- Go to: https://www.php.net/downloads
- Click on "Windows downloads" for the Current Stable PHP 8.3.10 version
- Download the "VS16 Non Thread Safe" Zip file
- Extract the file, rename the extracted `php` folder to a shorter name, and move it to `C:\php`. Then, add the PHP path to your environment variables.

For guidance on how to add the path, refer to the following:
https://www.javadrive.jp/php/install/index3.html

Open the standard Windows Command Prompt, and run `php -v` to check if the PHP version is displayed. If the version appears, PHP has been successfully added to the system path.

To open:
- Type "Environment Variables" in the Windows Start menu to open the Environment Variables window.
- Type "cmd" to open the Command Prompt.

#### PHP Warning: Missing VCRUNTIME140.dll or Other Errors
If you encounter an error related to `VCRUNTIME140.dll` or similar, visit:
https://learn.microsoft.com/en-us/cpp/windows/latest-supported-vc-redist?view=msvc-170
Download the appropriate version of the "Latest Microsoft Visual C++ Redistributable Version" (x86 for 32-bit PCs, x64 for 64-bit PCs). Most modern PCs will likely be 64-bit.

#### Adding PHP Modules

Copy the `php.ini` file from the LYLY folder into the `C:\php` directory.

The `php.ini` file is already configured with necessary modules for handling Japanese text and PDF generation libraries, as well as image processing modules.

At this point, PHP setup is complete.

#### Program Installation

Extract the provided zip file and place the `lyly` folder in `C:\lyly`. 
*It might be helpful to create a shortcut to the `lyly` folder on your desktop for easy access.*

#### Usage

[Method 1]  
Drag and drop a CSV file onto `run.bat`.

[Method 2]  
Execute the following in Command Prompt:  
```bash
cd C:\lyly
php run.php CSVFileName.csv
```

#### Generating Print PDFs
After running one of the above methods:
- Image files will be placed in the `download` folder.
- Individual design PDFs will be created in the `temp` folder.
- Printable PDFs will be generated in the `draft` folder.

#### Design Adjustment
To modify designs, you can edit the `config.php` file in `C:\lyly\config.php`.  
Refer to the comments inside `config.php` for guidance on editing. Any changes are made at your own risk.

#### Naming Conventions and Others
For the program's operation, files such as the print-ready PDF follow naming conventions using only lowercase English letters. Below are some clarifications on specific naming conventions. Other terms are named in a straightforward manner, e.g., "fullcolor" for full-color files.

- White Border, White Text → text
- White Lid → futa

#### Explanation of Folders and Files

- `[download]`  - Folder for downloaded image files
- `[draft]` - Folder for print-ready PDFs
- `[include]` - Folder containing PHP-related libraries
- `[parts]` - Folder for design parts
- `[temp]` - Folder for individual design PDFs
- `config.php` - Configuration file for PHP command and design details (design adjustments and placements can be made here)
- `end_order.txt` - List of completed orders
- `log.txt` - Error logs (for development use)
- `orders.csv` - Sample CSV file for order information (modified version of a provided sample for testing)
- `説明書.txt` (Instruction Manual)
- `php.ini` - PHP configuration file
- `run.bat` - Batch file for drag-and-drop use
- `run.php` - PHP command for PDF creation

--- 

