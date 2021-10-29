<?php

namespace RTNatePHP\LaravelFileContentUpdates;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RTNatePHP\LaravelFileContentUpdates\Facades\ContentUpdates;
use Symfony\Component\Yaml\Yaml;

class Model extends EloquentModel
{

    protected $content_field = 'content';

    protected $file_identifier = 'id';

    public function getFrontMatterData(bool $no_id = false): array
    {
        if ($this->exists)
        {
            $attributes = $this->getAttributes();
            $exclude_attributes = [$this->content_field];
            if ($no_id) array_push($exclude_attributes, $this->getKeyName());
            $data = Arr::except($attributes, $exclude_attributes);
            foreach ($data as $key => $value) {
                # code...
                if ($this->hasCast($key))
                {
                    $data[$key] = $this->castAttribute($key, $value);
                }
            }
            return $data;
        }
    }

    public function getContent(): string
    {
        $content = ($this->getAttribute($this->content_field));
        return $content;
    }

    public function setContent(string $content)
    {
        $this->setAttribute($this->content_field, $content);
    }

    public function getContentFileIdentifier(): string 
    {
        $identifier =  $this->getAttribute($this->file_identifier);
        if (!$identifier)
        {
            throw new InvalidArgumentException(
                "File identifier `$this->file_identifier` does not exist on model ".
                static::class
            );
        }
        //Ensure uniqueness
        $count = static::where($this->file_identifier, '=', $identifier)->count();
        if ($count > 1)
        {
            $id = $this->getKey();
            $identifier = $identifier.'_'.$id;
        }
        return $identifier;
    }

    public function loadModelContent(string $content, array $attributes)
    {
        $this->setContent($content);
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    public function loadFromFile(ParsedFile $file)
    {
        $this->loadModelContent($file->content(), $file->attributes());
        $timestamp = $file->timestamp();
        if ($timestamp)
        {
            $this->created_at = Carbon::createFromFormat('Ymd', $timestamp);
        }
    }

    // public function createMarkdownFileString(): string
    // {
    //     $matter = $this->generateFrontMatter();
    //     $matter = rtrim($matter);
    //     $content = $this->getContent();
    //     return '---' . PHP_EOL . $matter . PHP_EOL . '---' . PHP_EOL . $content;
    // }

    // public function createMarkdownFile() 
    // {
    //     $dir = base_path('content/current');
    //     $id = $this->getAttribute($this->getKeyName());
    //     $table = $this->getTable();
    //     $filename = $table . '-' . $id . ".md";
    //     $file_contents = $this->createMarkdownFileString();
    //     $disk = Storage::build([
    //         'driver' => 'local',
    //         'root' => base_path('content')
    //     ]);
    //     $disk->put('current/' . $filename, $file_contents);
    // }
}