<?php
declare(strict_types=1);
namespace stobbsm\FileBrowser;

/* Copyright (c) 2017 Matthew Stobbs <matthew@sproutingcommunications.com>

GNU GENERAL PUBLIC LICENSE
   Version 3, 29 June 2007

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
(at your option) any later version.
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>. */

/**
 * Search and parse a path as a series of arrays for directories and files as strings.
 *
 * Includes a small amount of metadata for files.
 *
 * TODO: Make class chainable.
 */
class FileBrowser {

  private $files = null; // Used during processing.
  private $_files = array(); // Stored as original
  private $basepath = "";

  const DIRECTORY = 'directory';
  const FILE = 'file';
  const LINK = 'link';

  /**
   * Constructor
   * @method __construct
   * @param  string      $path Base path to start searching
   */
  public function __construct(string $path) {
    printf("Constructing FileBrowser object\n");
    $this->basepath=$path;
    $this->_files=$this->buildFiles();
    $this->reset();
  }

  /**
   * Scans and builds the file index
   * @method buildFiles
   * @param  string     $path Path to start on. If not set, it uses basepath that is set in the constructor.
   * @return array           Array of files.
   */
  private function buildFiles(string $path = null) {
    $builtPath = [];
    if ($path === null) {
      $path = $this->basepath;
    }

    $path = rtrim($path, '/');

    // Scandir, then loop through it to find the contents of sub directories as well.
    // Remove single and double dot directories from Unix like system listings
    $contents = array_diff(scandir($path), ['.', '..']);
    foreach($contents as $key => $pathname) {
      $_basename = basename($pathname);
      $_current['name']=$_basename;
      $_current['fullpath']=$path . '/' . $pathname;
      if(is_dir($_current['fullpath'])) {
        // Is a directory, set some metadata then set the "contents" property
        // to the underlying directory structure.
        $_current['type']=FileBrowser::DIRECTORY;
        $_current['contents']=$this->buildFiles($_current['fullpath']);
      } elseif (is_link($_current['fullpath'])) {
        // Is a link, don't follow or descend, but include it in the map.
        $_current['type']=FileBrowser::LINK;
      } elseif (is_file($_current['fullpath'])) {
        // Assume it is a file. Get some metadata like size and mime-type.
        $_current['type']=FileBrowser::FILE;
        $_current['mimetype']=mime_content_type($_current['fullpath']);
        $_current['size']=filesize($_current['fullpath']);
        $_current['filetype']=filetype($_current['fullpath']);

      } else {
        $_current['type']='unknown';
      }
      array_push($builtPath, $_current);
      unset($_current);
    }
    return $builtPath;
  }

  /**
   * Dump the Map
   * @method dump
   * @param $rstring Boolean set to true to return a string.
   * @return string Returned as a string if rstring is true.
   */
  public function dump(bool $rstring = false) {
    if($rstring) {
      return print_r($this->getFiles(), true);
    } else {
      print_r($this->getFiles());
    }
  }

  /**
   * Search the file map for the specified key/value pair
   * @method Search
   * @param  string $key   Key to base search on
   * @param  string $value Value to find. Can be * as a wildcard.
   * @param  boolean $recursive Search recursively
   *
   * @return FileBrowser    The current filebrowser object
   */
  public function Search(string $key, string $value, bool $recursive = true) {
    $this->files = $this->dosearch($key, $value, $recursive, $this->getFiles());
    return $this;
  }

  public function dosearch(string $key, string $value, bool $recursive, array $searcharray) {
    $found = [];
    foreach($searcharray as $file) {
      $_current = $file;
      if($_current['type'] === FileBrowser::DIRECTORY && $recursive = true) {
        $_current['contents'] = $this->dosearch($key, $value, $recursive, $file['contents']);
        if(isset($_current['contents'])) {
          array_push($found, $_current);
        }
      } else {
        if(isset($_current[$key])) {
          if($_current[$key] === $value || $value === '*') {
            array_push($found, $_current);
          }
        }
      }
    }
    return $found;
  }

  /**
   * Search for many values based on a single key in the file array
   * @method SearchMany
   * @param  string     $key       The key to search for
   * @param  array      $values    An array of values to search
   * @param  boolean    $recursive Search recursively
   *
   * @return array The found files
   */
  public function SearchMany(string $key, array $values, bool $recursive = true) {
    $found = [];
    foreach($values as $value) {
      $_currentValue = $this->dosearch($key, $value, $recursive, $this->getFiles());
      foreach($_currentValue as $_current) {
        array_push($found, $_current);
      }
    }
    if(count($found) == 0) {
      return false;
    } else {
      $this->files = $found;
      return $this;
    }
  }

  /**
   * Flatten the given array by removing references to directories and nested arrays.
   * @method Flatten
   * @param  bool   $includedirs If true, include directories in the flattened array.
   * @param  array  $toflatten The array to flatten. If not set, uses the file map.
   * @return FileBrowser       The current FileBrowser object with modifications done.
   */
  public function Flatten(bool $includedirs = true, array $toflatten = null) {
    $this->files = $this->flattenit($includedirs, $this->getFiles());
    return $this;
  }

  /**
   * Private function that actually does the array flattening and state storage.
   * @method flattenit
   * @param  bool      $includedirs If true, include directories in the flattened array.
   * @param  array     $toflatten   Multi-dimensional array to flatten.
   * @return array                  The flattened array.
   */
  private function flattenit(bool $includedirs, array $toflatten) {
    $flattened = [];
    foreach($toflatten as $item) {
      if($item['type'] === FileBrowser::DIRECTORY) {
        $_flat = $this->flattenit($includedirs, $item['contents']);
        foreach($_flat as $_item) {
          array_push($flattened, $_item);
        }
        // Set contents to null to ensure a flattened array.
        $item['contents']=null;
        if($includedirs) {
          array_push($flattened, $item);
        }
      } else {
        array_push($flattened, $item);
      }
    }
    return $flattened;
  }

  /**
   * Return the file array. Resets the files array after use.
   * @method get
   *
   * @return array Array of files found.
   */
  public function get() {
    $toreturn = $this->getFiles();
    $this->reset();
    return $toreturn;
  }

  /**
   * Set keep the backup copy of files fresh when needed.
   * @method getFiles
   * @return array The files array.
   */
  private function getFiles() {
    if(!isset($this->files) && !is_array($this->files)) {
      $this->files = $this->_files;
    }
    return $this->files;
  }

  /**
   * Reset the files array to the initial value.
   * @method reset
   */
  private function reset() {
    $this->files = null;
  }
}
