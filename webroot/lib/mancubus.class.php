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
 * Handles the upload and the file itself
 */
class Mancubus {

    private $_uploadedData;
    private $_short;
    private $_saveFilename;
    private $_storagePath;
    private $_shortURL;

    /**
     * Mancubus constructor.
     */
    function __construct() {
    }

    /**
     * Requires a single upload from $_FILES
     * @see https://www.php.net/manual/en/features.file-upload.post-method.php
     * @param $file array
     * @return bool
     */
    public function load($file) {
        $ret = false;

        if(isset($file['name'])
            && isset($file['type'])
            && isset($file['size'])
            && isset($file['tmp_name'])
            && isset($file['error'])
        ) {
            $this->_uploadedData = $file;
            $ret = true;
        }

        return $ret;
    }

    /**
     * Either set short to given string
     * or create from _saveFilename. In this case _saveFilename is a number
     * @param string $short
     */
    public function setShort($short='') {
        if($short != '') {
            $this->_short = $short;
        }
        elseif(!empty($this->_saveFilename)) {
            $this->_short = Summoner::b64sl_pack_id($this->_saveFilename);
        }
    }

    /**
     * Either set _saveFilename to given string
     * or create from a random number. In this case _short needs this as a base
     * @param string $string
     * @throws Exception
     */
    public function setSaveFilename($string='') {
        if($string != '') {
            $this->_saveFilename = $string;
        }
        else {
            $r = random_int(1000, 9999);
            $this->_saveFilename = (string)$r;
        }
    }

    /**
     * Set _shortURL to given string
     * or create based on SELFPASTE_URL and _short
     * @param string $string
     */
    public function setShortURL($string='') {
        if($string != '') {
            $this->_shortURL = $string;
        }
        elseif(!empty($this->_short)) {
            $this->_shortURL = SELFPASTE_URL.'/'.$this->_short;
        }
    }

    /**
     * set the right storage path based on _saveFilename
     * and SELFPASTE_UPLOAD_DIR
     */
    public function setStoragePath() {
        $string = $this->_saveFilename;

        if(!empty($string)) {
            $p = SELFPASTE_UPLOAD_DIR.'/';
            $p .= Summoner::forwardslashStringToPath($string);
            $this->_storagePath = $p;
        }
    }

    public function process() {
        $ret = array(
            'message' => '',
            'status' => false
        );

        try {
            $ret = $this->_checkFileUploadStatus();
            $ret = $this->_checkAllowedFiletype();
            $ret = $this->_checkStorage();
            $ret = $this->_moveUploadedFile();
        }
        catch (Exception $e) {
            $ret['message'] = $e->getMessage();
        }
        
        return $ret;
    }

    /**
     * Check if the POST upload worked
     * @return array message,status
     * @throws Exception
     */
    private function _checkFileUploadStatus() {
        $check = Summoner::checkFileUploadStatus($this->_uploadedData['error']);

        if($check['status'] === true) {
            # check has the structure we want already
            return $check;
        }
        else {
            throw new Exception($check['message']);
        }
    }

    /**
     * Check if the uploaded file matches the allowed filetypes
     * @return array message,status
     * @throws Exception
     */
    private function _checkAllowedFiletype() {
        $message = "Filetype not supported";
        $status = false;

        $workWith = $this->_uploadedData['tmp_name'];
        if(!empty($workWith)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $workWith);
            finfo_close($finfo);
            if(strpos(SELFPASTE_ALLOWED_FILETYPES,$mime) !== false) {
                $status = true;
                $message = "Filetype allowed";
            }
            else {
                if(DEBUG) $message .= " $mime";
                throw new Exception($message);
            }
        } else {
            throw new Exception($message);
        }

        return array(
            'message' => $message,
            'status' => $status
        );
    }

    private function _checkStorage() {
        $message = "File storage failure";
        $status = false;

        $workwith = $this->_storagePath;
        if(is_writable(SELFPASTE_UPLOAD_DIR)) {
            if (mkdir($workwith,0777,true)) {
                $message = "File storage creation success";
                $status = true;
            }
            else {
                if(DEBUG) $message .= " ".$workwith;
                throw new Exception($message);
            }
        }
        else {
            throw new Exception('Storage location not writeable');
        }

        return array(
            'message' => $message,
            'status' => $status
        );
    }

    /**
     * Move the tmp_file from _uploadedData to the new location
     * provided by _storagePath and _saveFilename
     * @return array
     * @throws Exception
     */
    private function _moveUploadedFile() {
        $message = "File storage failure";
        $status = false;

        $workwithPath = $this->_storagePath;
        $workwithFilename = $this->_saveFilename;

        if(!empty($workwithPath) && !empty($workwithFilename)) {
            $_newFilename = Summoner::endsWith($workwithPath,'/') ? $workwithPath : $workwithPath.'/';
            $_newFilename .= $workwithFilename;
            if(move_uploaded_file($this->_uploadedData['tmp_name'], $_newFilename)) {
                $status = true;
                $message = $this->_shortURL;
            }
            else {
                if(DEBUG) $message .= " $_newFilename";
                throw new Exception($message);
            }
        }
        else {
            throw new Exception('Failing requirements for saving');
        }

        return array(
            'message' => $message,
            'status' => $status
        );
    }
}