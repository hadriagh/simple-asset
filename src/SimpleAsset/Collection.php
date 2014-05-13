<?php
namespace SimpleAsset;

class Collection
{
    private $name;
    private $definition;
    private $manager;
    private $styleAssets = array();
    private $scriptAssets = array();
    private $embdeddedScriptAssets = array();
    private $embeddedStyleAssets = array();
    private $assetsLoaded = false;

    public function __construct($name, \Closure $definition = null, Manager $manager = null)
    {
        $this->name = $name;
        $this->definition = $definition;
        $this->manager = $manager;
    }

    public function import()
    {
        $imports = func_get_args();
        foreach ($imports as $import) {
            $collection = $this->manager->getCollection($import);
            $assets = $collection->getAssets();
            foreach ($assets as $type => $objects) {
                $property = "{$type}Assets";
                foreach ($objects as $object) {
                    $this->{$property}[] = $object;
                }
            }
        }
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function script($src)
    {
        $script = new Script($src);
        if ($this->manager) {
            $script->setBaseUrl($this->manager->getBaseUrl());
        }
        $this->scriptAssets[] = $script;
        return $this;
    }

    public function style($src, $media = null)
    {
        $asset = new Style($src, $media);
        if ($asset->isLess()) {
            $asset->setPublicRoot($this->manager->getPublicRoot());
        }
        if ($this->manager) {
            $asset->setBaseUrl($this->manager->getBaseUrl());
        }
        $this->styleAssets[] = $asset;
        return $this;
    }

    public function embeddedScript($script)
    {
        $this->embdeddedScriptAssets[] = new EmbeddedScript($script);
        return $this;
    }

    public function embeddedStyle($style)
    {
        $this->embeddedStyleAssets[] = new EmbeddedStyle($style);
        return $this;
    }

    public function getAssets($kind = null)
    {
        $this->loadAssets();
        $assets = array(
            "style" => $this->styleAssets,
            "script" => $this->scriptAssets,
            "embeddedStyle" => $this->embeddedStyleAssets,
            "embeddedScript" => $this->embdeddedScriptAssets
        );
        if ($kind) {
            if (isset($assets[$kind])) {
                return $assets[$kind];
            } else {
                throw new \InvalidArgumentException("Asset kind is invalid: $kind");
            }
        }
        return $assets;
    }

    private function loadAssets()
    {
        if (!$this->assetsLoaded && $this->definition) {
            $definition = $this->definition->bindTo($this);
            $definition();
            $this->assetsLoaded = true;
        }
    }
}