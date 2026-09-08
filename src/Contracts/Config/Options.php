<?php

namespace SameOldNick\LaravelSuitcase\Contracts\Config;

/**
 * Interface Options
 *
 * Provides accessors for all configuration options required by the package.
 * Each method returns a specific configuration value, such as paths, includes, excludes, and database options.
 */
interface Options
{
    // === Export & Deployment Paths ===

    /**
     * Gets the absolute export directory path.
     */
    public function getExportPath(): string;

    /**
     * Gets the absolute path to the exported public_html directory.
     */
    public function getPublicPath(): string;

    /**
     * Gets the absolute path to the exported Laravel directory.
     */
    public function getLaravelPath(): string;

    /**
     * Gets the absolute path to the generated zip file.
     */
    public function getZipPath(): string;

    /**
     * Gets the absolute path to the .env file to be used for export.
     */
    public function getEnvFilePath(): string;

    // === Database Export Options ===

    /**
     * Checks if database dump is enabled.
     */
    public function getDbDumpEnabled(): bool;

    /**
     * Gets the databases to dump.
     *
     * @array<string, {dump_path: string, extra_options: array<string>}> The database connections to dump, with their respective dump paths and extra options.
     */
    public function getDbConnections(): array;

    // === File Inclusion/Exclusion ===

    /**
     * Gets a list of files to include at the root of the export.
     *
     * @return array<int, string>
     */
    public function getRootFiles(): array;

    /**
     * Gets a list of files/directories to include in the Laravel export.
     *
     * @return array<int, string>
     */
    public function getLaravelIncludes(): array;

    /**
     * Gets a list of files/directories to include in the public_html export.
     *
     * @return array<int, string>
     */
    public function getPublicIncludes(): array;

    /**
     * Gets a list of files/directories to exclude from the Laravel export.
     *
     * @return array<int, string>
     */
    public function getLaravelExcludes(): array;

    /**
     * Gets a list of files/directories to exclude from the public_html export.
     *
     * @return array<int, string>
     */
    public function getPublicExcludes(): array;

    // === Stub Paths ===

    /**
     * Gets the absolute path to the index.php stub file.
     */
    public function getIndexStubPath(): string;

    /**
     * Gets the absolute path to the constants.php stub file.
     */
    public function getConstantsStubPath(): string;

    // === Remote Deployment Paths ===

    /**
     * Gets the path to the remote Laravel directory on the shared hosting server.
     */
    public function getRemoteLaravelPath(): string;

    /**
     * Gets the path to the remote public_html directory on the shared hosting server.
     */
    public function getRemotePublicPath(): string;
}
