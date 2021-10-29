<?php

namespace RTNatePHP\LaravelFileContentUpdates;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RTNatePHP\LaravelFileContentUpdates\Facades\ContentUpdates;
use Spatie\YamlFrontMatter\YamlFrontMatter;

class ParsedFile 
{
    protected $content;
    protected $attributes;
    protected $table;
    protected $timestamp;
    protected $identifier;

    protected function __construct(string $content, 
                                array $attributes = [],
                                string $table = '', 
                                string $identifier = '',
                                string $timestamp = '',
                                string $filename = ''
                                )
    {
        $this->content = $content;
        $this->attributes = $attributes;
        $this->table = $table;
        $this->identifier = $identifier;
        $this->timestamp = $timestamp ? $timestamp : Carbon::now('Ymd');
        $this->filename = $filename;
    }


    public function content()
    {
        return $this->content ? $this->content : '';
    }

    public function attributes()
    {
        return $this->attributes ? $this->attributes : [];
    }

    public function getAttribute($key)
    {
        return Arr::get($this->attributes(), $key);
    }

    public function hasAttribute($key)
    {
        return Arr::has($this->attributes(), $key);
    }

    public function table()
    {
        return $this->table ? $this->table : '';
    }

    public function timestamp()
    {
        return $this->timestamp ? $this->timestamp : '';
    }

    public function identifier()
    {
        return $this->identifier ? $this->identifier : '';
    }

    public function filename()
    {
        return $this->filename ? $this->filename : '';
    }

    public function getModelClass()
    {
        $table = $this->table();
        if (!$table) 
        {
            return null;
        }
        $models = ContentUpdates::getModelClasses([$table]);
        $model = head($models);
        return $model ? $model : null;
    }

    static public function load(string $path, bool $absolutePath = false)
    {
        $pattern = '/(\d{8})-(\w+)-(\S+)\.md/';
        $filename = $filename = pathinfo($path, PATHINFO_BASENAME);
        $matches = [];
        $date = '';
        $table = '';
        $identifier = '';
        if(preg_match($pattern, $filename, $matches))
        {
            $date = $matches[1];
            $table = $matches[2];
            $identifier = $matches[3];
        }   
        $file = YamlFrontMatter::parse(ContentUpdates::getFile($path, $absolutePath));
        return new self($file->body(), $file->matter(), $table, $identifier, $date, $path);
    }
}