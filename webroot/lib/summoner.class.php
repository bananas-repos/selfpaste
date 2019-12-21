<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the COMMON DEVELOPMENT AND DISTRIBUTION LICENSE
 *
 * You should have received a copy of the
 * COMMON DEVELOPMENT AND DISTRIBUTION LICENSE (CDDL) Version 1.0
 * along with this program.  If not, see http://www.sun.com/cddl/cddl.html
 *
 * 2019 https://www.bananas-playground.net/projekt/selfpaste
 */

/**
 * a static helper class
 */
class Summoner {
    /**
     * validate the given string with the given type. Optional check the string
     * length
     *
     * @param string $input The string to check
     * @param string $mode How the string should be checked
     * @param mixed $limit If int given the string is checked for length
     *
     * @return bool
     *
     * @see http://de.php.net/manual/en/regexp.reference.unicode.php
     * http://www.sql-und-xml.de/unicode-database/#pc
     *
     * the pattern replaces all that is allowed. the correct result after
     * the replace should be empty, otherwise are there chars which are not
     * allowed
     */
    static function validate($input,$mode='text',$limit=false) {
        // check if we have input
        $input = trim($input);

        if($input == "") return false;

        $ret = false;

        switch ($mode) {
            case 'mail':
                if(filter_var($input,FILTER_VALIDATE_EMAIL) === $input) {
                    return true;
                }
                else {
                    return false;
                }
                break;

            case 'url':
                if(filter_var($input,FILTER_VALIDATE_URL) === $input) {
                    return true;
                }
                else {
                    return false;
                }
                break;

            case 'nospace':
                // text without any whitespace and special chars
                $pattern = '/[\p{L}\p{N}]/u';
                break;

            case 'nospaceP':
                // text without any whitespace and special chars
                // but with Punctuation other
                # http://www.sql-und-xml.de/unicode-database/po.html
                $pattern = '/[\p{L}\p{N}\p{Po}\-]/u';
                break;

            case 'digit':
                // only numbers and digit
                // warning with negative numbers...
                $pattern = '/[\p{N}\-]/';
                break;

            case 'pageTitle':
                // text with whitespace and without special chars
                // but with Punctuation
                $pattern = '/[\p{L}\p{N}\p{Po}\p{Z}\s-]/u';
                break;

            # strange. the \p{M} is needed.. don't know why..
            case 'filename':
                $pattern = '/[\p{L}\p{N}\p{M}\-_\.\p{Zs}]/u';
                break;

            case 'text':
            default:
                $pattern = '/[\p{L}\p{N}\p{P}\p{S}\p{Z}\p{M}\s]/u';
        }

        $value = preg_replace($pattern, '', $input);

        if($value === "") {
            $ret = true;
        }

        if(!empty($limit)) {
            # isset starts with 0
            if(isset($input[$limit])) {
                # too long
                $ret = false;
            }
        }

        return $ret;
    }

    /**
     * check if a string starts with a given string
     *
     * @param string $haystack
     * @param string $needle
     * @return boolean
     */
    static function startsWith($haystack, $needle) {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    /**
     * check if a string ends with a given string
     *
     * @param string $haystack
     * @param string $needle
     * @return boolean
     */
    static function endsWith($haystack, $needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }


    /**
     * Simple helper to detect the $_FILE upload status
     * Expects an array from $_FILE
     * @param $file
     * @return array
     */
    static function checkFileUploadStatus($file) {
        $message = "Unknown upload error";
        $status = false;

        if(isset($file['error'])) {
            switch ($file['error']) {
                case UPLOAD_ERR_OK:
                    $message = "There is no error, the file uploaded with success.";
                    $status = true;
                break;
                case UPLOAD_ERR_INI_SIZE:
                    $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                break;
                case UPLOAD_ERR_FORM_SIZE:
                    $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                break;
                case UPLOAD_ERR_PARTIAL:
                    $message = "The uploaded file was only partially uploaded";
                break;
                case UPLOAD_ERR_NO_FILE:
                    $message = "No file was uploaded";
                break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $message = "Missing a temporary folder";
                break;
                case UPLOAD_ERR_CANT_WRITE:
                    $message = "Failed to write file to disk";
                break;
                case UPLOAD_ERR_EXTENSION:
                    $message = "File upload stopped by extension";
                break;
            }
        }

        return array(
            'message' => $message,
            'status' => $status
        );
    }

    /**
     * Simple helper to detect the $_FILE type
     * Expects an array from $_FILE
     *
     * @see https://www.php.net/manual/en/intro.fileinfo.php
     *
     * @param $file
     * @return array
     */
    static function checkAllowedFiletype($file) {
        $message = "Filetype not supported";
        $status = false;

        if(isset($file['tmp_name'])) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if(strpos(SELFPASTE_ALLOWED_FILETYPES,$mime) !== false) {
                $status = true;
                $message = "Filetype allowed";
            }
            if(DEBUG) $message .= " $mime";
        }

        return array(
            'message' => $message,
            'status' => $status
        );
    }

    /**
     * Simple helper to create and make sure the storage
     * location is available
     * Expects an array from $_FILE
     * with an extra key = storagepath
     *
     * @param $file
     * @return array
     */
    static function checkStorage($file) {
        $message = "File storage failure";
        $status = false;

        if(isset($file['storagepath']) && !empty($file['storagepath'])
            && is_writable(SELFPASTE_UPLOAD_DIR)) {
            if (mkdir($file['storagepath'],0777,true)) {
                $message = "File storage creation success";
                $status = true;
            }
        }

        if(DEBUG) $message .= " ".$file['storagepath'];

        return array(
            'message' => $message,
            'status' => $status
        );
    }

    /**
     * move the uploaded file.
     * Depends on the _FILES info and the keys
     * storagepath, short, shortUrl
     * @param $file
     * @return array
     */
    static function moveUploadedPasteFile($file) {
        $message = "File storage failure";
        $status = false;
        //shortUrl

        if(isset($file['storagepath']) && !empty($file['storagepath'])
            && isset($file['short']) && !empty($file['short'])) {
            $_newFilename = self::endsWith($file['storagepath'],'/') ? $file['storagepath'] : $file['storagepath'].'/';
            $_newFilename .= $file['short'];
            if(move_uploaded_file($file['tmp_name'], $_newFilename)) {
                $status = true;
                $message = $file['shortUrl'];
            }

            if(DEBUG) $message .= " $_newFilename";
        }

        return array(
            'message' => $message,
            'status' => $status
        );
    }

    /**
     * Simple helper to create a new name
     *
     * @return string
     * @throws Exception
     */
    static function createShort() {
        $idstring = random_int(1000, 9999);
        return self::b64sl_pack_id($idstring);
    }

    /**
     * create a short string based on a integer
     *
     * @see https://www.jwz.org/base64-shortlinks/
     *
     * @return string
     */
    static function b64sl_pack_id($id) {
        $id = intval($id);
        $ida = ($id > 0xFFFFFFFF ? $id >> 32 : 0);	// 32 bit big endian, top
        $idb = ($id & 0xFFFFFFFF);			// 32 bit big endian, bottom
        $id = pack ('N', $ida) . pack ('N', $idb);
        $id = preg_replace('/^\000+/', '', "$id");	// omit high-order NUL bytes
        $id = base64_encode ($id);
        $id = str_replace ('+', '-', $id);		// encode URL-unsafe "+" "/"
        $id = str_replace ('/', '_', $id);
        $id = preg_replace ('/=+$/', '', $id);	// omit trailing padding bytes
        return $id;
    }

    /**
     * create based on the given string a path
     * each char in string is a dir
     * and add SELFPASTE_UPLOAD_DIR
     * asd -> SELFPASTE_UPLOAD_DIR/a/s/d
     * @param $string
     * @return bool|string
     */
    static function createStoragePath($string) {
        $p = false;

        if(!empty($string) && is_writable(SELFPASTE_UPLOAD_DIR)) {
            $p = SELFPASTE_UPLOAD_DIR.'/';
            for($i=0;$i<strlen($string);$i++) {
                $p .= $string[$i]."/";
            }
        }

        return $p;
    }
}
