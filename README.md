## What is this?

var2zip is a PHP class for creating simple Zip archives in RAM.

## Requirements

* PHP 5.4+
* ZLIB extension (optional)

## Limitations

* Maximum entry size is 4 GiB
* Directories are not supported

## Usage

Create a Zip archive:

    $var2zip = new var2zip();
    $file = file_get_contents("README.md");
    $var2zip->add("README.md", $file);
    $modified = strtotime("1982-09-09T20:19:11Z");
    $var2zip->add("hello.txt", "Hello, world!", $modified);
    $zip = $var2zip->export();
    file_put_contents("archive.zip", $zip);
