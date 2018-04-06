<?php
declare(strict_types=1);
namespace stobbsm;

use stobbsm\File;

class FileIndexer extends \Thread
{
    private $path = '';
    private $file_list = [];
    private $file_index = [];

    /**
     * Set's the path to be indexed by this indexer.
     * @param   string  $path   The base path to start indexing from.
     */
    public function __construct(string $path)
    {
        $this->path = $path;
        return $this;
    }

    public function run()
    {
        $this->file_list = array_diff(glob('*'), ['.', '..']);
        return $this->indexDirectory();
    }

    /**
     * Index's a directory and returns the completed array of that directory.
     * Also dispatches other functions to index found directories.
     * @return  array           The completed array filled with the contents of the directory.
     */
    private function indexDirectory()
    {
        foreach ($this->files as $file) {
            $this->file_index[] = new File($file);
            if (id_dir($file)) {
                $sub_indexer = new FileIndexer($file);
                array_push($this->file_index, $sub_indexer->run());
            }
        }
        
        return $this->file_index;
    }
}
