<?php

class ws_import {

    public int $post_id;
    public string $error;

    /**
    * Read content from .docx Word document
    */
    public function read_docx($filename){

      $striped_content = '';
      $content = '';

      if(!$filename || !file_exists($filename)) return false;

      $zip = zip_open($filename);
      if (!$zip || is_numeric($zip)) return false;

      while ($zip_entry = zip_read($zip)) {

          if (zip_entry_open($zip, $zip_entry) == FALSE) continue;

          if (zip_entry_name($zip_entry) != "word/document.xml") continue;

          $content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

          zip_entry_close($zip_entry);
      }
      zip_close($zip);
      $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
      $content = str_replace('</w:r></w:p>', "\r\n", $content);
      $striped_content = strip_tags($content);

      return $striped_content;
    }

    /**
     * Read content from .doc Word document
     */
    public function read_doc($userDoc){
        $fileHandle = fopen($userDoc, "r");
        $line = @fread($fileHandle, filesize($userDoc));
        $lines = explode(chr(0x0D),$line);
        $outtext = "";
        foreach($lines as $thisline)
        {
            $pos = strpos($thisline, chr(0x00));
            if (($pos !== FALSE)||(strlen($thisline)==0))
            {
            } else {
                $outtext .= $thisline." ";
            }
        }
        $outtext = preg_replace("/[^a-zA-Z0-9\s\,\.\-\n\r\t@\/\_\(\)]/","",$outtext);
        return $outtext;
    }

    public function ws_insert($data){
    $my_post = array(
        'post_title'    => wp_strip_all_tags( $data['post_title'] ),
        'post_content'  => $data['post_content'],
        'post_status'   => 'publish',
        'post_author'   => 1
    );

    // Insert the post into the database
      $this->post_id = wp_insert_post( $my_post );
      if(!is_wp_error($this->post_id)){
          return $this->post_id;
      }else{
          //there was an error in the post insertion,
          return $this->error = $this->post_id->get_error_message();
      }
    }


    public function ws_update_meta($postid, $meta){
    update_post_meta( $postid, '_yoast_wpseo_title', $meta['meta_title'] );
    update_post_meta( $postid, '_yoast_wpseo_metadesc', $meta['meta_description'] );
    }



    /**
     * Empty the log file
     */
    public function emptyFileLog(){
        $file = static::$logfile;
        $f = fopen($file, "r+");
        if ($f !== false) {
            ftruncate($f, 0);
            fclose($f);
        }
    }

}