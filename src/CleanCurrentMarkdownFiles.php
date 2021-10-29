<?php

namespace RTNatePHP\LaravelFileContentUpdates\Command;

use Illuminate\Console\Command;
use RTNatePHP\LaravelFileContentUpdates\Contracts\ContentUpdates;

class CleanCurrentMarkdownFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    //protected $signature = 'file-content-updates:generate';
    protected $signature = '%1:clean';

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
        $this->info('Cleaning current content files...');
        $path = "/current";
        $this->contentUpdates->cleanDirectory($path);
        $this->info("done.");
        return 0;
    }

}
