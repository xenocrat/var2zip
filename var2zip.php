<?php
    class var2zip {
        const VAR2ZIP_VERSION_MAJOR = 1;
        const VAR2ZIP_VERSION_MINOR = 0;
        const MSDOS_EPOCH           = 315532800;

        private $entries            = array();

        public function add($name, $contents, $modified = null) {
            $name = str_replace(array("\\", "/"), "", $name);

            if (count($this->entries) = 0xffff)
                throw new Exception("Zip archive cannot contain more than 65535 entries.");

            if (strlen($name) > 0xffff)
                throw new Exception("Zip archive entry names cannot exceed 65535 bytes.");

            if (strlen($contents) > 0xffffffff)
                throw new Exception("Zip archive entries cannot exceed 4294967295 bytes.");

            if (!isset($modified) or !is_int($modified))
                $modified = time();

            $time = $this->msdos_time($modified);
            $date = $this->msdos_date($modified);

            $this->entries[] = array("name" => $name,
                                     "orig" => $contents,
                                     "time" => $time,
                                     "date" => $date);

            return count($this->entries);
        }

        private function msdos_time($timestamp) {
            if ($timestamp < self::MSDOS_EPOCH)
                $timestamp = self::MSDOS_EPOCH;

            $when = getdate($timestamp);

            # Generate MS-DOS time format.
            $time = 0 | $when["seconds"] >> 1 | $when["minutes"] << 5 | $when["hours"] << 11;
            return $time;
        }

        private function msdos_date($timestamp) {
            if ($timestamp < self::MSDOS_EPOCH)
                $timestamp = self::MSDOS_EPOCH;

            $when = getdate($timestamp);

            # Generate MS-DOS date format.
            $date = 0 | $when["mday"] | $when["mon"] << 5 | ($when["year"] - 1980) << 9;
            return $date;
        }

        public function export() {
            $file = "";
            $cdir = "";
            $eocd = "";

            foreach ($this->entries as $entry) {
                $name = $entry["name"];
                $orig = $entry["orig"];
                $time = $entry["time"];
                $date = $entry["date"];

                $comp = $orig;
                $method = "\x00\x00";

                if (function_exists("gzcompress")) {
                    $zlib = gzcompress($orig, 6, ZLIB_ENCODING_DEFLATE);

                    if ($zlib === false)
                        throw new Exception("ZLIB compression failed.");

                    # Trim ZLIB header and checksum from the deflated data.
                    $zlib = substr(substr($zlib, 0, strlen($zlib) - 4), 2);

                    if (strlen($zlib) < strlen($orig)) {
                        $comp = $zlib;
                        $method = "\x08\x00";
                    }
                }

                $head = "\x50\x4b\x03\x04";           # Local file header signature.
                $head.= "\x14\x00";                   # Version needed to extract.
                $head.= "\x00\x00";                   # General purpose bit flag.
                $head.= $method;                      # Compression method.
                $head.= pack("v", $time);             # Last mod file time.
                $head.= pack("v", $date);             # Last mod file date.

                $nlen = strlen($name);
                $olen = strlen($orig);
                $clen = strlen($comp);
                $crc  = crc32($orig);

                $head.= pack("V", $crc);              # CRC-32.
                $head.= pack("V", $clen);             # Compressed size.
                $head.= pack("V", $olen);             # Uncompressed size.
                $head.= pack("v", $nlen);             # File name length.
                $head.= pack("v", 0);                 # Extra field length.

                $cdir.= "\x50\x4b\x01\x02";           # Central file header signature.
                $cdir.= "\x00\x00";                   # Version made by.
                $cdir.= "\x14\x00";                   # Version needed to extract.
                $cdir.= "\x00\x00";                   # General purpose bit flag.
                $cdir.= $method;                      # Compression method.
                $cdir.= pack("v", $time);             # Last mod file time.
                $cdir.= pack("v", $date);             # Last mod file date.
                $cdir.= pack("V", $crc);              # CRC-32.
                $cdir.= pack("V", $clen);             # Compressed size.
                $cdir.= pack("V", $olen);             # Uncompressed size.
                $cdir.= pack("v", $nlen);             # File name length.
                $cdir.= pack("v", 0);                 # Extra field length.
                $cdir.= pack("v", 0);                 # File comment length.
                $cdir.= pack("v", 0);                 # Disk number start.
                $cdir.= pack("v", 0);                 # Internal file attributes.
                $cdir.= pack("V", 32);                # External file attributes.
                $cdir.= pack("V", strlen($file));     # Relative offset of local header.
                $cdir.= $name;

                $file.= $head.$name.$comp;
            }

            $eocd.= "\x50\x4b\x05\x06";               # End of central directory signature.
            $eocd.= "\x00\x00";                       # Number of this disk.
            $eocd.= "\x00\x00";                       # Disk with start of central directory.
            $eocd.= pack("v", count($this->entries)); # Entries on this disk.
            $eocd.= pack("v", count($this->entries)); # Total number of entries.
            $eocd.= pack("V", strlen($cdir));         # Size of the central directory.
            $eocd.= pack("V", strlen($file));         # Offset of start of central directory.
            $eocd.= "\x00\x00";                       # ZIP file comment length.

            return $file.$cdir.$eocd;
        }
    }
