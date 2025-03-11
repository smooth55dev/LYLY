Here is the translated text in English:  

---

# LYLY Printing Data Creation Command  

## Preparing the PHP Environment  

### [Installing PHP]  
- Go to: [https://www.php.net/downloads](https://www.php.net/downloads)  
- Click on **Windows downloads** under *Current Stable PHP 8.3.10*.  
- Download the **VS16 Non Thread Safe** Zip file.  
- Extract the downloaded folder, rename it to **php**, and move it to **C:\php**.  
- Then, add **C:\php** to the system PATH.  

For instructions on how to set the PATH, refer to:  
[https://www.javadrive.jp/php/install/index3.html](https://www.javadrive.jp/php/install/index3.html)  

To verify the installation:  
1. Open the Windows **Command Prompt**.  
2. Run `php -v`.  
3. If the PHP version is displayed, the PATH is set correctly.  

To open necessary system windows:  
- Search for **"Environment Variables"** in the Windows Start Menu.  
- Search for **"cmd"** to open the Command Prompt.  

## Resolving PHP Warnings (Missing VCRUNTIME140.dll, etc.)  
If you get an error about missing **VCRUNTIME140.dll**, install the latest Microsoft Visual C++ Redistributable from:  
[https://learn.microsoft.com/en-us/cpp/windows/latest-supported-vc-redist?view=msvc-170](https://learn.microsoft.com/en-us/cpp/windows/latest-supported-vc-redist?view=msvc-170)  

- For **32-bit PC**, download and install **x86**.  
- For **64-bit PC**, download and install **x64**.  
  (If your PC is not very old, it is likely 64-bit.)  

## Adding PHP Modules  

Copy the `php.ini` file located inside the **LYLY** folder and place it in **C:\php**.  

This configuration includes modules required for Japanese language support and image processing libraries needed for PDF generation.  
At this point, the PHP setup is complete.  

## Installation of This Program  

1. Extract the provided ZIP file.  
2. Move the **lyly** folder to **C:\lyly**.  
3. (Optional) Create a shortcut to the **lyly** folder on your desktop for easier access.  

## Usage Instructions  

### [Method 1]  
Drag and drop a CSV file onto **run.bat**.  

### [Method 2]  
Execute the following commands in Command Prompt:  

```sh
cd C:\lyly
php run.php [CSV_FILE_NAME].csv
```

## Output: Printing PDF Files  

After executing method **① or ②**, the following files will be created:  
- **download/** → Downloaded image files.  
- **temp/** → Individual design PDFs.  
- **draft/** → Final printing PDFs.  

## Adjusting the Design  

Modify `C:\lyly\config.php`.  

You can customize the design by editing `config.php`.  
Refer to the comments inside the file for details.  
*Note: Any modifications are at your own risk.*  

## Naming Rules  

For consistency, file names related to print PDFs follow **lowercase English characters**.  

Some key naming conventions:  
- **White border, white text → `text`**  
- **White lid → `futa`**  

Other names generally follow their original meaning (e.g., *fullcolor* for full-color).  

## Folder & File Descriptions  

| Folder/File | Description |
|-------------|-------------|
| `download`  | Folder for downloaded image files. |
| `draft`     | Folder for final print PDFs. |
| `include`   | PHP libraries. |
| `parts`     | Various design parts. |
| `temp`      | Folder for individual design PDFs. |
| `config.php` | Configuration file for PHP commands, including settings & design layout adjustments. |
| `end_order.txt` | List of completed orders. |
| `log.txt` | Error logs (for development purposes). |
| `orders.csv` | Sample order CSV file (contains test data). |
| `説明書.txt` | User manual. |
| `php.ini` | PHP configuration file. |
| `run.bat` | Drag-and-drop execution script. |
| `run.php` | PDF generation command script. |

---

This is the full English version. Let me know if you need any changes! 🚀