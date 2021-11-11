<?php 

namespace RTNatePHP\LaravelFileContentUpdates;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;
use RTNatePHP\LaravelFileContentUpdates\Contracts\ContentUpdates;
use RTNatePHP\LaravelFileContentUpdates\Database\FileSeeder;
use RTNatePHP\LaravelFileContentUpdates\Model as ContentModel;
use Spatie\YamlFrontMatter\YamlFrontMatter;

class ContentUpdateService implements ContentUpdates
{
    public function __construct()
    {
        $contentPath = $this->getContentPath();
        $this->disk = Storage::build([
            'driver' => 'local',
            'root' => $contentPath
        ]);
    }

    public function createFile(string $path, string $content, array $data = [])
    {
        $file_contents = $this->createFileString($content, $data);
        $this->disk->put($path, $file_contents);
    }

    public function generateFrontMatter(array $data = []): string
    {
        return Yaml::dump($data);
    }

    public function createFileString(string $content, array $data = []): string
    {
        if (!empty($data)) 
        {
            $matter = $this->generateFrontMatter($data);
            $matter = rtrim($matter);
        }
        if ($matter)
        {
            return '---' . PHP_EOL . $matter . PHP_EOL . '---' . PHP_EOL . $content;
        }
        else return $content;
    }

    protected function getAppModelClasses(): array 
    {
        $appFiles = collect(File::allFiles(base_path('app')));

        $appClasses = $appFiles->map( function ($item) 
            {
                $path = $item->getRelativePathName();
                $class = sprintf('\%s%s',
                    Container::getInstance()->getNamespace(),
                    strtr(substr($path, 0, strrpos($path, '.')), '/', '\\'));
                return $class;
            });

        $fileteredModelClasses = $appClasses->filter(function ($class)
        {
            return $this->isValidModelClass($class);
            // $valid = false;

            // if (class_exists($class)) {
            //     $reflection = new \ReflectionClass($class);
            //     $valid = $reflection->isSubclassOf(ContentModel::class) &&
            //         !$reflection->isAbstract();
            // }
            // return $valid;
        });
        return ($fileteredModelClasses->values()->toArray());
    }

    protected function filterModelsForTables(array $models, array $tables): array
    {
        $tableCollection = collect($tables);
        $filtered = (collect($models)->filter(function($model, $key) use (&$tableCollection){
            $table = (new $model)->getTable();
            return $tableCollection->contains($table);
        }));
        return $filtered->toArray();
    }

    /**
     * @inheritDoc
     *
     */
    public function getModelClasses($tables = []): array
    {
        $appModels = $this->getAppModelClasses();
        $additional = config('file-content-updates.models', []);
        $modelClasses = array_merge($appModels, $additional);
        if ($tables)
        {
            return $this->filterModelsForTables($modelClasses, $tables);
        }
        else 
        {
            return $modelClasses;
        }
    }

    /**
     * @inheritDoc
     */
    public function isValidModelClass(string $modelClass): bool
    {
        if (class_exists($modelClass)) {
            $reflection = new \ReflectionClass($modelClass);
            $valid = $reflection->isSubclassOf(ContentModel::class) &&
                !$reflection->isAbstract();
            return $valid;
        }
        return false;
    }

    public function getContentPath(): string
    {
        return config('file-content-updates.content_path', base_path('content'));
    }

    public function getFilenameForModel(ContentModel $model): string
    {
        $date = Carbon::now()->format('Ymd');
        $table = $model->getTable();
        $id = $model->getContentFileIdentifier();
        return $date . '-' . $table . '-' . $id . '.md';
    }

    public function createFileForModel(ContentModel $model, string $path = '',  bool $no_ids = false)
    {
        if (!$path) $path = "/model-data";
        $frontMatterData = $model->getFrontMatterData($no_ids);
        $content = $model->getContent();
        $filename = $this->getFilenameForModel($model);
        $this->createFile("$path/$filename", $content, $frontMatterData);
    }

    public function createAllFilesForModelClass(string $modelClass, string $path = '', bool $no_ids = false)
    {
        $models = $modelClass::all();
        $models->map(function($model) use ($path, $no_ids)
        {
            $table = $model->getTable();
            $output_path = rtrim($path, " \n\r\t\v\0\\/")."/".$table."/";
            $this->createFileForModel($model, $output_path, $no_ids);
        });
    }


    public function cleanDirectory(string $path)
    {
        if (!$path) 
        {
            throw new InvalidArgumentException(
                "Must provide a path to clean directory.  ".
                "To clean all files in the content directory, "."
                provide either '/' or '*' ");
        }
        if ($path === "*" or $path === "/")
        {
            $files = $this->disk->allFiles();
            $this->disk->delete($files);
        }
        $files = $this->disk->allFiles($path);
        $this->disk->delete($files);
    }

    /**
     * @inheritDoc
     */
    public function parseFile(string $path, bool $absolutePath = false): ParsedFile
    {
        return ParsedFile::load($path, $absolutePath);
    }

    public function seed(string $modelClass, $files): FileSeeder
    {
        $seeder = new FileSeeder($this, $modelClass);
        return $seeder->seedFiles($files);
    }

    /**
     * @inheritDoc
     */
    public function findFiles(string $searchDirectory, bool $absolutePath = false): array
    {
        $files = [];
        if ($absolutePath)
        {
            $files = File::allFileS($searchDirectory);
        }
        else 
        {
            $files = $this->disk->allFiles($searchDirectory);
        }
        $filtered = $this->filterFilesByFilename($files);
        return $filtered;
    }

    /**
     * @inheritDoc
     */
    public function filterFilesByFilename(array $files): array 
    {
        $pattern = '/\d{8}-\w+-\S+\.md/';
        $filtered = (collect($files)->filter(function($file, $key) use ($pattern)
            {
                $filename = pathinfo($file, PATHINFO_BASENAME);
                return preg_match($pattern, $filename);
            }));
        return $filtered->toArray();
    }

    /**
     * @inheritDoc
     */
    public function performFileUpdate(ParsedFile $file, string &$status): ContentModel
    {
        //Get the model class for the file
        $status = 'failed';
        $model = $file->getModelClass();
        if (!$model)
        {
            throw new ModelNotFoundException("Unable to find model class for file '".
                $file->filename()."'.");
        }
        $newInstance = new $model;
        $keyField = $newInstance->getKeyName();
        //If the primary key is set on the update file, 
        //attempt to find the model and update it 
        if ($file->hasAttribute($keyField))
        {
            $existing = $model::find($file->getAttribute($keyField));
            if ($existing)
            {
                $existing->loadModelContent($file->content(), $file->attributes());
                $existing->save();
                $status = 'updated';
                return $existing;
            }
        }
        $newInstance->loadModelContent($file->content(), $file->attributes());
        $newInstance->save();
        $status = 'created';
        return $newInstance;
    }

    public function getFile(string $path, bool $absolutePath = false)
    {
        if ($absolutePath)
        {
            return File::get($path);
        }
        else
        {
            return $this->disk->get($path);
        }
    }

    public function moveFile(string $from, string $to, bool $absolutePath = false)
    {
        if ($absolutePath)
        {
            return File::move($from, $to);
        }
        else
        {
            return $this->disk->move($from, $to);
        }
    }
}