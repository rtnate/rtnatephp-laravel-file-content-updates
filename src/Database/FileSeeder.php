<?php 

namespace RTNatePHP\LaravelFileContentUpdates\Database;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RTNatePHP\LaravelFileContentUpdates\Contracts\ContentUpdates;
use RTNatePHP\LaravelFileContentUpdates\Model;

class FileSeeder 
{

    public function __construct(ContentUpdates $updateService, string $modelClass)
    {
        if (!$updateService->isValidModelClass($modelClass))
        {
            throw new InvalidArgumentException(
                "The Model Class ($modelClass) provided to ".
                "FileSeeder does not exist or is not a subclass of ". 
                ContentModel::class);
        }
        $this->modelClass = $modelClass;
        $this->updateService = $updateService;
    }

    /**
     * Seed the model's table using the provided files
     *
     * @param string|array $files - The files to seed.  
     *      If files is a string, it will treat $files as a directory and 
     *      search recursively for all .md or .txt files in the provided
     *      directory and use them to seed the model.
     *      
     *      If files is an array, it will only seed the files explicitly 
     *      provided
     *          
     * @return self
     */
    public function seedFiles($files): self
    {
        if (is_array($files))
        {
            $filesFiltered = $this->updateService->filterFilesByFilename($files);
            $this->doSeeding($filesFiltered);
        }
        else if(is_string($files))
        {
            $filesFiltered = $this->updateService->findFiles($files, true);
            $this->doSeeding($filesFiltered);
        }
        else 
        {
            throw new InvalidArgumentException(
                "Argument $files must be an array or string");
        }
        return $this;
    }

    protected function doSeeding(array $files)
    {
        foreach ($files as $file) {
            $fileParsed = $this->updateService->parseFile($file);
            $model = new $this->modelClass;
            $model->loadFromFile($fileParsed);
            $model->save();
        }
    }

    // protected function filterFiles(array $files): array
    // {
    //     $pattern = '/\d{8}-\w+-\S+\.md/';
    //     $filtered = (collect($files)->filter(function($file, $key) use ($pattern)
    //         {
    //             $filename = pathinfo($file, PATHINFO_BASENAME);
    //             return preg_match($pattern, $filename);
    //         }));
    //     return $filtered->toArray();
    // }

    // static public function isValidModelClass(string $modelClass)
    // {
    //     if (class_exists($modelClass)) {
    //         $reflection = new \ReflectionClass($modelClass);
    //         $valid = $reflection->isSubclassOf(Model::class) &&
    //             !$reflection->isAbstract();
    //         return $valid;
    //     }
    //     return false;
    // }
}