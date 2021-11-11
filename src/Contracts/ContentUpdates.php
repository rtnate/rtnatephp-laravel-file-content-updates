<?php 

namespace RTNatePHP\LaravelFileContentUpdates\Contracts;

use RTNatePHP\LaravelFileContentUpdates\Database\FileSeeder;
use RTNatePHP\LaravelFileContentUpdates\Model as ContentModel;
use RTNatePHP\LaravelFileContentUpdates\ParsedFile;

interface ContentUpdates
{
    public function createFile(string $path, string $content, array $data = []);

    public function generateFrontMatter(array $data = []): string;

    public function createFileString(string $content, array $data = []): string;

    /**
     * Retrieves the App's model classes that inherit from 
     * RTNatePHP\LaravelFileContentUpdates\Model.
     * 
     * Takes an optional array of tables to reteive models for.  If not
     * provided, will return all appropriate App model classes.
     *
     * @param array $tables (optional) An array of table names to retrieve models 
     *                                 for.  Will return an empty array if no 
     *                                 model classes matching the provided tables 
     *                                 are found.
     * 
     * @return array An array of fully-qualified model class names
     */
    public function getModelClasses($tables = []): array;

    public function getContentPath(): string;

    public function getFilenameForModel(ContentModel $model): string;

    public function createFileForModel(ContentModel $model, string $path = '', bool $no_ids = false);

    public function createAllFilesForModelClass(string $modelClass, string $path = '', bool $no_ids = false);

    public function cleanDirectory(string $path);

    public function seed(string $modelClass, $files): FileSeeder;

    /**
     * Finds filers with the correct filename format int he provided search directory
     *
     * @param string $searchDirectory The directory to search
     * @param bool $absolutePath (optional) If true, will treat $searchDirectory 
     *                                     as an absolute file path. 
     *                                     If false, it will search a subdirectory 
     *                                     of the configured content location.
     * @return array    An array of file paths.
     */
    public function findFiles(string $searchDirectory, bool $absolutePath = false): array;

    /**
     * Filters an array of file names/paths to only include one's that fit the 
     * markdown file format.
     * 
     * The proper format is 'YYYYMMDD-table_name-identifier.md'
     *
     * @param array $files An array of file names/paths to search
     * @return array An array of file names/paths that are of valid format
     */
    public function filterFilesByFilename(array $files): array;

    /**
     * Determine if the class name provided is a valid class.
     * A valid class exists and inherits from RTNatePHP\LaravelFileContentUpdates\Model
     *
     * @param string $modelClass The fully-qualified class name to test
     * @return boolean True if valid, false if not
     */
    public function isValidModelClass(string $modelClass): bool;

    /**
     * Performs a database update given the provided ParsedFile.
     * 
     * If a primary key value is set on the file, and such a record exists in 
     * the database, an update will be performed.  Otherwise, a new row will be created.
     * 
     *
     * @param ParsedFile $file The parsed file to use to perform an update
     * @param string $status [OUT] The provided string will be populated with 
     *      the status of the update (either 'created', 'updated', or 'failed');
     * 
     * 
     * @return ContentModel The model representing the file after updates.
     */
    public function performFileUpdate(ParsedFile $file, string &$status): ContentModel;

    public function getFile(string $path, bool $absolutePath = false);

    /**
     * Parses the provided file, decoding content and front matter
     *
     * @param string $path  The path of the file to load.

     * @return ParsedFile   The parsed file
     */
    public function parseFile(string $path, bool $absolutePath = false): ParsedFile;
}