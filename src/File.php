<?php
declare(strict_types=1);
namespace stobbsm;

/**
 * File holds information on a file.
 * Makes any indexed file easy to gather information on, while always being able to find the file path,
 * name and type.
 */
class File
{
    private $file_size;
    private $full_path;
    private $directory;
    private $file_name;
    private $mime_type;
    private $file_type;
    private $handle_type;
    private $atime;
    private $mtime;

    const DIRECTORY = 'directory';
    const FILE = 'file';
    const LINK = 'link';
    const EXEC = 'executable';
    const UNKNOWN = 'unknown';

    public function __construct(string $path)
    {
        $this->full_path = $path;
        $this->parseFile();
    }

    public function __get(string $property)
    {
        return $this->$property;
    }

    public function __set(string $property, $value)
    {
        $this->$property = $value;
        return $value;
    }

    /**
     * parseFile will assign metadata of the file to required parameters.
     * Should only be called on "Construction" of the file object.
     * @return void
     */
    public function parseFile()
    {
        $file_stat = \lstat($this->full_path);
        $path_parts = \pathinfo($this->full_path);

        if (is_link($this->full_path)) {
            $this->handle_type = File::LINK;
        } elseif (is_dir($this->full_path)) {
            $this->handle_type = File::DIRECTORY;
        } elseif (is_executable($this->full_path)) {
            $this->handle_type = File::EXEC;
        } elseif (is_file($this->full_path)) {
            $this->handle_type = File::FILE;
        } else {
            $this->handle_type = File::UNKNOWN;
        }

        $this->file_name = $path_parts['basename'];
        $this->directory = $path_parts['dirname'];
        if (isset($path_parts['extension'])) {
            $this->file_type = $path_parts['extension'];
        } else {
            $this->file_type = '';
        }

        $this->file_size = $file_stat['size'];
        $this->atime = $file_stat['atime'];
        $this->mtime = $file_stat['mtime'];
        $this->mime_type = \mime_content_type($this->full_path);
    }

    /**
     * getContents will return the file's contents.
     * @return string   The contents of the file being read.
     */
    public function getContents()
    {
        return file_get_contents($this->full_path);
    }
}
