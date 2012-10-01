<?php
/*
Plugin Name: SVD Backup
Plugin URI: http://test.com
Description: Home grown backup plugin
Version: 0.1
Author: Adam Chamberlin
Author URI: http://twitter.com/funkylarma
License: 
*/

class SVD_Backup {

  /**
   * local_folder
   * 
   * @var mixed
   * @access private
   */
  private $local_folder;
  
  /**
   * web_folder
   * 
   * @var mixed
   * @access private
   */
  private $web_folder;
  
  /**
   * backup_folder
   * 
   * @var mixed
   * @access private
   */
  private $backup_folder;
  
  /**
   * hookname
   * 
   * @var mixed
   * @access private
   */
  private $hookname;
  
  /**
   * version
   * 
   * @var mixed
   * @access private
   */
  private $version;
  
  /**
   * id
   * 
   * @var mixed
   * @access private
   */
  private $id;
  
  /**
   * __construct function.
   * 
   * @access public
   * @return void
   */
  function __construct() {
    // The constructor method
    $this->version = "0.1";
    $this->id = time();
    $this->local_folder = WP_CONTENT_DIR . '/backup';
    
    add_action( 'admin_menu', array( $this, 'create_menu' ) );
    register_activation_hook( __FILE__, array( $this, 'activate' ) );
    register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
    register_uninstall_hook( __FILE__, array( $this, 'uninstall' ) );
  }
  
  /**
   * activate function.
   * 
   * @access public
   * @return void
   */
  function activate() {
    // Run when plugin activates
    $this->create_directory( $this->local_folder );
  }
  
  /**
   * deactivate function.
   * 
   * @access public
   * @return void
   */
  function deactivate() {
    // Run when plugin deactivates
    $this->delete_directory( $this->local_folder );
    echo '<div class="error"><p>All backup data has been deleted!</p></div>';
  }
  
  /**
   * uninstall function.
   * 
   * @access public
   * @return void
   */
  function uninstall() {
    // Run when the plugin is deleted
    //$this->delete_directory( $this->local_folder );
  }
  
  /**
   * create_menu function.
   * 
   * @access public
   * @return void
   */
  function create_menu() {
    $this->hookname = add_menu_page( 'Backup Option Page', 'Backup', 'activate_plugins', 'svd-backup-options', array( $this, 'options_page' ), null, 76 );
    add_action( 'load-' . $this->hookname, array( $this, 'options_update' ) );
  }
  
  /**
   * options_page function.
   * 
   * @access public
   * @return void
   */
  function options_page() {
    if ( !current_user_can( 'manage_options' ) )  {
      wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
    require_once( 'page-options.php' );
  }
  
  /**
   * options_update function.
   * 
   * @access public
   * @return void
   */
  function options_update() {
    // Update the options
    $message = "";
    $error ="";
    
    if( $_POST['create_backup'] ) {
      if( check_admin_referer( 'create_backup' ) ) {
        $this->id = time();
        if( $this->backup_folder = $this->local_folder . '/' . $this->id ) {
          $message .= "Backup started.<br />";
        } else {
          $error .= "The backup process failed to start.<br />";
        }
        if( $this->create_directory( $this->backup_folder ) ) {
          $message .= "Backup folder created.<br />";
        } else {
          $error .= "Failed to create the backup folder.<br />";
        }
        if( $this->backup_sql( $this->backup_folder ) ) {
          $message .= "SQL dump file created.<br />";
        } else {
          $error .= "Failed to create the SQL dump.<br />";
        }
        if( $this->backup_uploads( $this->backup_folder ) ) {
          $message .= "Archived the uploads directory.<br />";
        } else {
          $error .= "Failed to archive the uploads directory.";
        }
        if( $this->backup_themes( $this->backup_folder ) ) {
          $message .= "Archived the themes directory.<br />";
        } else {
          $error .= "Failed to archive the themes directory.<br />";
        }
        if( $this->backup_plugins( $this->backup_folder ) ) {
          $message .= "Archived the plugins directory.<br />";
        } else {
          $error .= "Failed to archive the plugins directory.<br />";
        }
        if( $message != "" ) { 
          echo '<div class="updated"><p>' . $message . '</p></div>';
        }
        if( $error != "" ) {
          echo '<div class="error"><p>A problem occured</p></div>';
        }
      }
      //require_once( 'page-options.php' );
    }
  }
  
  /**
   * create_directory function.
   * 
   * @access public
   * @param mixed $directory
   * @return void
   */
  function create_directory( $directory ) {
    if( !is_dir( $directory ) ) {
      wp_mkdir_p( $directory );
      return true;
    } else {
      return false;
    }
  }
  
  /**
   * delete_directory function.
   * 
   * @access public
   * @param mixed $directory
   * @return void
   */
  function delete_directory( $directory ) {
      if( is_dir( $directory ) ) {
        $contents = scandir( $directory );
        unset( $contents[0], $contents[1] );
        foreach( $contents as $object ) {
          $current_object = $directory . '/' . $object;
          if( filetype( $current_object ) == 'dir' ) {
            $this->delete_directory( $current_object );
          } else {
            unlink( $current_object );
          }
        }
        rmdir( $directory ); 
      }
  }
  
  /**
   * backup_sql function.
   * 
   * @access public
   * @param mixed $directory
   * @return void
   */
  function backup_sql( $directory ) {
    $backup  = $this->create_sql();
    $handle = fopen( $directory . "/backup.sql", 'w+');
    fwrite($handle, $backup);
    fclose($handle);
    return true;
  }
  
  /**
   * backup_uploads function.
   * 
   * @access public
   * @param mixed $directory
   * @return void
   */
  function backup_uploads( $directory ) {
    if ($this->create_zip( $directory . '/uploads.zip', 'uploads' ) ) {
      return true;
    }
  }
  
  /**
   * backup_plugins function.
   * 
   * @access public
   * @param mixed $directory
   * @return void
   */
  function backup_plugins( $directory ) {
    if( $this->create_zip( $directory . '/plugins.zip', 'plugins' ) ) {
      return true;
    }
  }
  
  /**
   * backup_themes function.
   * 
   * @access public
   * @param mixed $directory
   * @return void
   */
  function backup_themes( $directory ) {
    if( $this->create_zip( $directory . '/themes.zip', 'themes' ) ) {
      return true;
    }
  }
  
  /**
   * create_sql function.
   * 
   * @access public
   * @return void
   */
  function create_sql() {  
    $body  = "-- WordPress database backup \n";
    $body .= "-- Version: {$this->version} \n";
    $body .= "-- Host: " . mysql_get_host_info() . " \n";
    $body .= "-- Generation Time: " . date('l dS \of F Y h:i A', time() + (get_option( 'gmt_offset' ) * 3600)) . " \n";
    $body .= "-- MySQL Version: " . mysql_get_server_info() . " \n";
    $body .= "-- PHP Version: " . phpversion() . " \n";
    $body .= "\n\n";
    $sql = mysql_query("SHOW TABLE STATUS FROM " . DB_NAME);
      while ( $row = mysql_fetch_array( $sql ) ) {
        $tables[] = $row[0];
      }
      foreach( $tables as $table ) {
        $result = mysql_query( 'SELECT * FROM '.$table );
        $num_fields = mysql_num_fields( $result );
        $body .= 'DROP TABLE ' . $table . ';';
        $row2 = mysql_fetch_row( mysql_query( 'SHOW CREATE TABLE ' . $table ) );
        $body .= "\n\n" . $row2[1] . ";\n\n";
        for( $i = 0; $i < $num_fields; $i++ ) {
          while( $row = mysql_fetch_row( $result ) ) {
            $body .= 'INSERT INTO ' . $table . ' VALUES(';
            for( $j = 0; $j < $num_fields; $j++ ) {
              $row[$j] = addslashes( $row[$j] );
              $row[$j] = ereg_replace( "\n", "\\n", $row[$j] );
              if( isset($row[$j]) ) {
                $body .= '"' . $row[$j] . '"' ;
              } else {
                $body .= '""';
              }
              if( $j < ($num_fields-1) ) {
                $body .= ',';
              }
            }
            $body .= ");\n";
          }
        }
        $body .="\n\n\n";
     }
     return $body;
  }
  
  /**
   * create_zip function.
   * 
   * @access public
   * @param mixed $archive
   * @param mixed $contents
   * @return void
   */
  function create_zip( $archive, $contents ) {
    $zip = new ZipArchive();
    if( !$zip->open( $archive, ZipArchive::CREATE ) ) {
        return false;
    }
    $source = str_replace('\\', '/', realpath( WP_CONTENT_DIR . '/' . $contents ));
    if (is_dir($source) === true):
      $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
      foreach ($files as $file)
      {
        $file = str_replace('\\', '/', $file);
        // Ignore "." and ".." folders
        if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) ) {
          continue;
        }
        $file = realpath($file);
  
        if (is_dir($file) === true):
          $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
        elseif (is_file($file) === true):
          $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
        endif;
      }
    elseif (is_file($source) === true):
      $zip->addFromString(basename($source), file_get_contents($source));
    endif;
    $zip->close();
    return true;
  }
  
}

if( is_admin() ) {
  new SVD_Backup();
} 