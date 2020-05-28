<?php

namespace Msvdev\Bitrix\Component;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Templating\Loader\FilesystemLoader;
use Symfony\Component\Templating\PhpEngine;

abstract class Component extends \CBitrixComponent
{

    protected $autoloadDirs = ['helpers','entity','forms'];

    /**
    * Template file extension
    * @var string
    */
    protected $ext = '.html.php';

    /**
     * @var PhpEngine
     */
    protected $view;

    public function getView(){
        return $this->view;
    }

    /**
     * @return string
     */
    public function getComponentNamespace()
    {
        $componentName = $this->getName();
        list($vendor,$name) = explode(':', $componentName);
        $namespace =  'Components\\'.ucfirst($vendor);
        $patchItems = explode(".", $name);
        foreach ($patchItems as $item){
            $namespace .= '\\'.ucfirst($item);
        }
        return $namespace;
    }

    /**
     * Init component
     */
    protected function init()
    {

    }

    /**
     * @param null $component
     */
    public function __construct($component = null)
    {
        parent::__construct($component);
        $this->init();
        $this->registerAutoLoadClasses();
        $this->initView();
    }

    /**
     * Register autoload component classes
     */
    protected function registerAutoLoadClasses()
    {
        $filesystem = new Filesystem();
        $searchPath = [];

        foreach ($this->autoloadDirs as $dir){
            $dirPath = $this->absolutePath().'/'.$dir;
            if($filesystem->exists($dirPath)){
                $searchPath[] = $dirPath;
            }
        }

        if(sizeof($searchPath)){
            $finder = new Finder();
            $finder->in($searchPath)->files()->name('*.php');
            $classes = [];
            foreach ($finder as $file) {
                $fileName = $file->getBasename('.php');
                $dirName = basename($file->getPath());
                $className = $this->getComponentNamespace() . '\\' . $dirName.'\\' . $fileName;
                $relativePath = substr($file->getRealPath(), strlen($_SERVER['DOCUMENT_ROOT']));
                $classes[$className] = $relativePath;
            }
            if (sizeof($classes)) {
                \Bitrix\Main\Loader::registerAutoLoadClasses(null, $classes);
            }
        }
    }

    /**
     * Init php engine templating
     * Кастомный TemplateNameParser нужен для работы view форм
     */
    protected function initView(){
        $this->initComponentTemplate('template');
        $template = & $this->GetTemplate();
        $componentTemplateDir = $_SERVER['DOCUMENT_ROOT'].$template->GetFolder();
        $loaderPath = $componentTemplateDir.DIRECTORY_SEPARATOR.'%name%'.$this->ext;
        $loader = new FilesystemLoader($loaderPath);
        $this->view = new PhpEngine(new TemplateNameParser(), $loader);
    }

    /**
     * @return string
     */
    public function absolutePath(){
        return $_SERVER["DOCUMENT_ROOT"].$this->getPath();
    }



}