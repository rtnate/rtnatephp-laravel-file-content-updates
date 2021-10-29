<?php

namespace RTNatePHP\LaravelFileContentUpdates\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use RTNatePHP\LaravelFileContentUpdates\Contracts\ContentUpdates;
use RTNatePHP\LaravelFileContentUpdates\ParsedFile;

class UpdateFromMarkdownFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    //protected $signature = 'file-content-updates:generate';
    protected $signature = '%1:update {--d|directory=updates : The directory containing file updates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Database Content From Markdown Files';


    protected $update_count = 0;
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
        //Set the search directory
        $dir = '/updates';
        $dirOption = ltrim($this->option('directory'), " \n\r\t\v\0\\/");
        if ($dirOption) $dir = $dirOption;
        $this->info('Updating database content...');
        //Find and parse update files
        $files = $this->findAndParseFiles($dir);
        foreach($files as $file)
        {
            try 
            {
                $this->performUpdate($file);
                $this->update_count++;
            }
            catch(\Throwable $e)
            {
                $this->error("Unable to update file '".$file->filename()."': ". $e->getMessage());
                if ($this->getOutput()->isVeryVerbose())
                {
                    $trace = debug_backtrace();
                    foreach ($trace as $t) 
                    {
                        $this->line("Trace : " . $t['file'] . " on line " . $t['line'] . " function " . $t['function']);
                    }
                }
            }
        }
        $this->info("Updates complete. ".$this->update_count." updates performed");
        return 0;
    }

    protected function performUpdate(ParsedFile $file)
    {
        $this->info('Performing update '.$file->filename()."'.", 'v');
        $status = '';
        $model = $this->contentUpdates->performFileUpdate($file, $status);
        if ($status == 'created')
        {
            $this->info('A new record was created in table '.
                $file->table()."'.", 'vv');
        }
        else if ($status == 'updated')
        {
            $this->info("Updated record (". $model->getKeyName().": '".
                $model->getKey()."') in table '".$file->table()."'.", 'vv');
        }
        else 
        {
            $this->warn('Failed to perform update.', 'v');
        }
        if ($status == 'created' || $status =='updated')
        {
            $this->moveFile($file);
        }
    }


    protected function moveFile(ParsedFile $file)
    {
        $dir = env('file-content-updates.completed-updates-directory', '/completed-updates');
        $dir = rtrim($dir, ' \t\n\r\0\x0B\\/');
        $baseName = pathinfo($file->filename(), PATHINFO_BASENAME);
        $newName = $dir."/"."updated-".Carbon::now()->format('Ymd_Hi-').
            $baseName;
        $this->contentUpdates->moveFile($file->filename(), $newName);
    }

    protected function findAndParseFiles(string $directory, bool $absolutePath = false): array
    {
        $files = $this->contentUpdates->findFiles($directory, $absolutePath);
        $parsed = [];
        foreach($files as $file)
        {
            $parsedFile = $this->contentUpdates->parseFile($file, $absolutePath);
            array_push($parsed, $parsedFile);
        }
        return $parsed;
    }

}
