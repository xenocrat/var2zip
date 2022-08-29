## What is this?

var2zip is a PHP class for creating simple Zip archives in RAM.

## Requirements

* PHP 8.0+
* ZLIB extension (optional)

## Limitations

* Maximum entry size is 4 GiB
* Directories are not supported

## Usage

Create a new instance:

    $var2zip = new \xenocrat\var2zip();

Add an entry read from disk:

    $file = file_get_contents("README.md");
    $var2zip->add("README.md", $file);

Add an entry with last-modified timestamp:

    $modified = strtotime("1982-09-09T20:19:11Z");
    $var2zip->add("hello.txt", "Hello, world!", $modified);

Set the compression level (0-9):

    $var2zip->compression_level = 9;

Export the Zip archive and write to disk:

    $zip = $var2zip->export();
    file_put_contents("archive.zip", $zip);
