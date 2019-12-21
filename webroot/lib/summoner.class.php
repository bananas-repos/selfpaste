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
    static function fileupload($file) {
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
    static function filetype($file) {
        $message = "Filetype not suported";
        $status = false;

        if(isset($file['tmp_name'])) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            var_dump($mime);
        }

        return array(
            'message' => $message,
            'status' => $status
        );
    }
}
