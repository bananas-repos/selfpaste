<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the COMMON DEVELOPMENT AND DISTRIBUTION LICENSE
 *
 * You should have received a copy of the
 * COMMON DEVELOPMENT AND DISTRIBUTION LICENSE (CDDL) Version 1.0
 * along with this program.  If not, see http://www.sun.com/cddl/cddl.html
 *
 * 2019 - 2020 https://://www.bananas-playground.net/projekt/selfpaste
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

    /**
     * After setting importing stuff process the upload
     * return status and message
     * @return array
     */
    public function process() {
        $ret = array(
            'message' => '',
            'status' => false
        );

        try {
            $ret = $this->_checkFlood();
            $ret = $this->_checkFileUploadStatus();
            $ret = $this->_checkAllowedFiletype();
            $ret = $this->_checkStorage();
            $ret = $this->_moveUploadedFile();

            $this->_checkLifetime();
        }
        catch (Exception $e) {
            $ret['message'] = $e->getMessage();
        }

        return $ret;
    }

    /**
     * Cleans lifetime and floodfiles.
     * @param boolean
     */
    public function cleanupCronjob($verbose=false) {
        $this->_cleanupFloodFiles($verbose);
        $this->_checkLifetime($verbose);
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

    /**
     * check if SELFPASTE_UPLOAD_DIR and _storagePath
     * is creatable. If so create _storagePath
     * @return array
     * @throws Exception
     */
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

    /**
     * check if the current paste request is within limits
     * for this check if the file exists. If so just return the shortURL
     * @return array
     * @throws Exception
     */
    private function _checkFlood() {
        $message = "Failing flood requirements";
        $status = false;

        $this->_cleanupFloodFiles();

        if(!empty($this->_uploadedData['name']) && !empty($this->_shortURL)) {
            $filename = md5($_SERVER['REMOTE_ADDR'].$this->_uploadedData['name']);
            $filepath = SELFPASTE_UPLOAD_DIR.'/'.$filename;
            if(!file_exists($filepath)) {
                if(file_put_contents($filepath,$this->_shortURL)) {
                    $status = true;
                    $message = $this->_shortURL;
                }
                else {
                    throw new Exception("Failed flood prevention requirements");
                }
            }
            else {
                $message = file_get_contents($filepath);
                throw new Exception($message);
            }
        }
        else {
            throw new Exception($message);
        }

        return array(
            'message' => $message,
            'status' => $status
        );
    }

    /**
     * clean up the flood tmp files. Everything older then 30 sec will be deleted.
     */
    private function _cleanupFloodFiles($verbose=false) {
        $iterator = new DirectoryIterator(SELFPASTE_UPLOAD_DIR);
        $now = time();
        foreach ($iterator as $file) {
            if($file->isDot() || $file->isDir() || Summoner::startsWith($file->getFilename(),'.')) continue;
            if ($now - $file->getCTime() >= SELFPASTE_FLOOD_LIFETIME) {
                if($verbose === true) echo "Delete ".$file->getFilename()."\n";
                unlink(SELFPASTE_UPLOAD_DIR.'/'.$file->getFilename());
            }
        }
    }

    /**
     * delete all pastes older than SELFPASTE_PASTE_LIFETIME
     */
    private function _checkLifetime($verbose=false) {
        $iterator = new RecursiveDirectoryIterator(SELFPASTE_UPLOAD_DIR);
        $datepointInThePastInSec = strtotime('-'.SELFPASTE_PASTE_LIFETIME.' days');

        foreach (new RecursiveIteratorIterator($iterator) as $file) {
            $fname = $file->getFilename();
            if($file->isDir()
                || Summoner::startsWith($file->getFilename(),'.')
                || isset($fname[4])
            ) continue;
            if ($file->getMTime() <= $datepointInThePastInSec) {
                if(is_writable($file->getPathname())) {
                    if($verbose === true) echo "Delete ".$file->getPathname()."\n";
                    unlink($file->getPathname());
                }
            }
        }
    }
}