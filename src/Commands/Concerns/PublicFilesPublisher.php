<?php

namespace LaraSpells\Generator\Commands\Concerns;

trait PublicFilesPublisher
{

    protected $publicFiles = [];
    protected $templatePublicFiles = [];

    public function addTemplatePublicFiles($path)
    {
        $publicFiles = $this->getTemplate()->getPublicFiles($path);
        $this->templatePublicFiles = array_unique(array_merge($this->templatePublicFiles, $publicFiles));
    }

    public function addPublicFile($from, $to)
    {
        $this->publicFiles[$to] = $from;
    }

    public function getAddedTemplatePublicFiles()
    {
        $template = $this->getTemplate();
        $templatePublicPath = $template->getDirectory().'/'.$template->getFolderPublic();
        $templatePublicFiles = [];
        foreach($this->templatePublicFiles as $publicFile) {
            $to = trim(str_replace($templatePublicPath, "", $publicFile), '/');
            $templatePublicFiles[$to] = $publicFile;
        }
        return $templatePublicFiles;
    }

    public function getAddedPublicFiles()
    {
        return array_merge($this->getAddedTemplatePublicFiles(), $this->publicFiles);
    }

}
