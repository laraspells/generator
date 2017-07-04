<?php

namespace LaraSpell;

use InvalidArgumentException;
use LaraSpell\Exceptions\InvalidTemplateException;

class Template
{

    protected $directory;

    protected $publicDirectory = 'public';
    protected $stubDirectory = 'stubs';
    protected $viewDirectory = 'views';

    public function __construct($directory)
    {
        if (!is_dir($directory)) {
            throw new InvalidArgumentException("Template directory '{$this->directory}' doesn't exists");
        }
        $this->directory = realpath($directory);
        $this->validate();
    }

    public function getDirectory()
    {
        return $this->directory;
    }

    public function getViewDirectory()
    {
        return $this->viewDirectory;
    }

    public function getPublicDirectory()
    {
        return $this->publicDirectory;
    }

    public function getStubDirectory()
    {
        return $this->stubDirectory;
    }

    public function getStubContent($stubFile)
    {
        return $this->getContent($this->stubDirectory.'/'.$stubFile);
    }

    public function getViewContent($viewFile)
    {
        return $this->getContent($this->viewDirectory.'/'.$viewFile);
    }

    public function getPublicFiles()
    {
        return $this->getFiles($this->publicDirectory);
    }

    public function getViewFiles()
    {
        return $this->getFiles($this->viewDirectory);
    }

    public function getStubFiles()
    {
        return $this->getFiles($this->stubDirectory);
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

    protected function validate()
    {
        $requiredFiles = [
            $this->stubDirectory.'/page-list.stub',
            $this->stubDirectory.'/page-detail.stub',
            $this->stubDirectory.'/form-create.stub',
            $this->stubDirectory.'/form-edit.stub',
            $this->viewDirectory.'/partials/fields/text.blade.php',
            $this->viewDirectory.'/partials/fields/number.blade.php',
            $this->viewDirectory.'/partials/fields/email.blade.php',
            $this->viewDirectory.'/partials/fields/textarea.blade.php',
            $this->viewDirectory.'/partials/fields/select.blade.php',
            $this->viewDirectory.'/partials/fields/select-multiple.blade.php',
            $this->viewDirectory.'/partials/fields/file.blade.php',
            $this->viewDirectory.'/partials/fields/checkbox.blade.php',
            $this->viewDirectory.'/partials/fields/radio.blade.php',
            $this->viewDirectory.'/layout/master.blade.php',
        ];

        foreach($requiredFiles as $file) {
            if (!$this->hasFile($file)) {
                throw new InvalidTemplateException("Template must have file '{$file}'");
            }
        }
    }

}
