<?php

namespace LaraSpells\Generator;

use InvalidArgumentException;
use LaraSpells\Generator\Exceptions\InvalidTemplateException;
use ReflectionClass;

abstract class Template extends Extension
{

    protected $directory;
    protected $folderPublic = 'public';
    protected $folderStub = 'stubs';
    protected $folderView = 'views';

    public function getDirectory()
    {
        if (!$this->directory) {
            // Get class file directory
            $ref = new ReflectionClass($this);
            $this->directory = dirname($ref->getFilename());
        }
        return $this->directory;
    }

    public function getSchemaResolver()
    {
        return new SchemaResolver;
    }

    public function getFolderView()
    {
        return $this->folderView;
    }

    public function getFolderPublic()
    {
        return $this->folderPublic;
    }

    public function getFolderStub()
    {
        return $this->folderStub;
    }

    public function getStubContent($stubFile)
    {
        return $this->getContent($this->folderStub.'/'.$stubFile);
    }

    public function getViewContent($viewFile)
    {
        return $this->getContent($this->folderView.'/'.$viewFile);
    }

    public function getPublicFiles($dir = null)
    {
        return $this->getFiles($this->folderPublic.($dir? '/'.$dir : ''));
    }

    public function getViewFiles()
    {
        return $this->getFiles($this->folderView);
    }

    public function getStubFiles()
    {
        return $this->getFiles($this->folderStub);
    }

    public function getContent($file)
    {
        return file_get_contents($this->getFilepath($file));
    }

    public function getFiles($dir)
    {
        $rootDir = $this->getDirectory();
        $filesAndDirs = array_diff(scandir($rootDir.'/'.$dir), ['.', '..']);
        $files = [];
        foreach($filesAndDirs as $fileOrDir) {
            $file = $rootDir.'/'.$dir.'/'.$fileOrDir;
            if (is_dir($file)) {
                $files = array_merge($files, $this->getFiles($dir.'/'.$fileOrDir));
            } else {
                $files[] = $file;
            }
        }
        return $files;
    }

    public function hasFile($file)
    {
        return file_exists($this->getFilepath($file));
    }

    public function getFilepath($file)
    {
        $rootDir = $this->getDirectory();
        return $rootDir.'/'.ltrim($file, '/');
    }

}
