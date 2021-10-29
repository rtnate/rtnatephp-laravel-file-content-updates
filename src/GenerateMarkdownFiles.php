<?php

namespace RTNatePHP\LaravelFileContentUpdates\Command;

use Illuminate\Console\Command;
use RTNatePHP\LaravelFileContentUpdates\Contracts\ContentUpdates;

class GenerateMarkdownFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    //protected $signature = 'file-content-updates:generate';
    protected $signature = '%1:generate {--table=* : The table(s) to generate} '. 
                           '{--model=* : The model class(es) to generate}'. 
                           '{--D|destination=current : The output destination directory.}'. 
                           '{--N|no-ids : Do not output database ids to files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Markdown Files';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ContentUpdates $contentUpdates)
    {
        $this->signature = (str_replace('%1', 
            env('file-content-updates.command', 'markdown'), 
            $this->signature));
        parent::__construct();
        $this->contentUpdates = $contentUpdates;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //Set the output directory
        $dir = '/current';
        $dest = $this->option('destination');
        if ($dest) 
        {
            $dir = "/".ltrim($dest, " \n\r\t\v\0\\/");
        }
        $this->info('Generating current database content files...');
        $this->info("Writing files to ".$this->contentUpdates->getContentPath().$dir);
        //Retrieve the model classes
        $requestedTables = $this->option('table');
        $requestedModels = $this->option('model');
        $models = $this->retrieveClasses($requestedTables, $requestedModels);
        //If no models are found, output an error message
        if (!$models)
        {
            $tablesError = $requestedTables ? " (Tables: '".
                            implode("','", $requestedTables)."')" : "";
            $modelsError = $requestedModels ? " (Models: '".
                            implode("','", $requestedModels)."')" : "";
            $this->error('No model classes found'.$tablesError.$modelsError.'.');
            return -1;
        }
        //Do the file generation for each class in $models
        collect($models)->map(function ($modelClass) use ($dir)
        {
            $this->contentUpdates->createAllFilesForModelClass($modelClass, $dir, $this->option('no-ids'));
            $this->info("Generated files for $modelClass");
        });
        return 0;
    }

    protected function retrieveClasses(array $tables, array $models)
    {
        if ($models)
        {
            $model_classes = [];
            if ($tables)
            {
                $found = $this->contentUpdates->getModelClasses($tables);
                array_push($model_classes, $found);
            }
            foreach($models as $model)
            {
                $class = $this->qualifyModelClass($model);
                if ($class) array_push($model_classes, $class);
            }
        }
        else 
        {
            return $this->contentUpdates->getModelClasses($tables);
        }
    }

    protected function qualifyModelClass(string $class)
    {
        if (class_exists($class)) return $class;
        if (class_exists("App\\Models\\".$class))
        {
            return "App\\Models\\".$class;
        }
        else return null;
    }

}
