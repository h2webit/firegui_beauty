<?php
    class Templates_model extends CI_Model
    {
        public function extract($file = null)
        {
            if (!$file) {
                throw new ApiException("Error: file not provided");
                exit;
            }

            $zip = new ZipArchive;
            $res = $zip->open(FCPATH."uploads/".$file);
            if ($res === true) {

               // Unzip path
                $extractpath = APPPATH.'views/';

                // Extract file
                $zip->extractTo($extractpath);
                $zip->close();
            } else {
                switch ($res) {
                    case ZipArchive::ER_NOZIP:
                        throw new ApiException('not a zip archive');
                    case ZipArchive::ER_INCONS :
                        throw new ApiException('consistency check failed');
                    case ZipArchive::ER_CRC :
                        throw new ApiException('checksum failed');
                    default:
                        throw new ApiException('error ' . $res);
                }
            }
        }
    }
