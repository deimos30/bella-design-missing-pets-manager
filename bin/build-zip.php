<?php
/**
 * Build the distributable plugin ZIP from a git revision.
 *
 * Usage: php bin/build-zip.php [revision] [output-dir]
 *
 * The files come from `git archive <revision>`, so the ZIP always matches a
 * commit rather than the working tree. Everything matched by .distignore is
 * left out, and all files are placed under a single deimos-lost-found-animals/
 * directory.
 *
 * @package Deimos_Lost_Found_Animals
 */

// phpcs:disable -- Command line build tool, not part of the plugin.

$slug     = 'deimos-lost-found-animals';
$root     = dirname( __DIR__ );
$revision = isset( $argv[1] ) ? $argv[1] : 'HEAD';
$out_dir  = isset( $argv[2] ) ? rtrim( $argv[2], '/' ) : $root . '/build';

chdir( $root );

$commit = trim( shell_exec( 'git rev-parse ' . escapeshellarg( $revision . '^{commit}' ) ) );
if ( ! preg_match( '/^[0-9a-f]{40}$/', $commit ) ) {
	fwrite( STDERR, "Unknown revision: $revision\n" );
	exit( 1 );
}

$main = shell_exec( 'git show ' . escapeshellarg( $commit . ':' . $slug . '.php' ) );
if ( ! preg_match( '/^\s*\*\s*Version:\s*(\S+)/m', $main, $m ) ) {
	fwrite( STDERR, "No Version header found.\n" );
	exit( 1 );
}
$version = $m[1];

// Patterns from .distignore at that revision.
$patterns = array();
foreach ( explode( "\n", (string) shell_exec( 'git show ' . escapeshellarg( $commit . ':.distignore' ) ) ) as $line ) {
	$line = trim( $line );
	if ( '' !== $line && '#' !== $line[0] ) {
		$patterns[] = trim( $line, '/' );
	}
}

$is_ignored = static function ( $path ) use ( $patterns ) {
	$parts = explode( '/', $path );
	foreach ( $patterns as $pattern ) {
		// Match the full path or any single path segment.
		if ( fnmatch( $pattern, $path ) ) {
			return true;
		}
		foreach ( $parts as $part ) {
			if ( fnmatch( $pattern, $part ) ) {
				return true;
			}
		}
	}
	return false;
};

$files = array_filter( explode( "\0", (string) shell_exec( 'git ls-tree -r -z --name-only ' . escapeshellarg( $commit ) ) ) );
$files = array_values( array_filter( $files, static function ( $file ) use ( $is_ignored ) {
	return ! $is_ignored( $file );
} ) );
sort( $files );

if ( ! is_dir( $out_dir ) && ! mkdir( $out_dir, 0755, true ) ) {
	fwrite( STDERR, "Cannot create $out_dir\n" );
	exit( 1 );
}

$zip_path = $out_dir . '/' . $slug . '-v' . $version . '.zip';
if ( file_exists( $zip_path ) ) {
	unlink( $zip_path );
}

$zip = new ZipArchive();
if ( true !== $zip->open( $zip_path, ZipArchive::CREATE ) ) {
	fwrite( STDERR, "Cannot create $zip_path\n" );
	exit( 1 );
}

$zip->addEmptyDir( $slug );
$dirs = array();
foreach ( $files as $file ) {
	$dir = dirname( $file );
	while ( '.' !== $dir && ! isset( $dirs[ $dir ] ) ) {
		$dirs[ $dir ] = true;
		$dir          = dirname( $dir );
	}
}
ksort( $dirs );
foreach ( array_keys( $dirs ) as $dir ) {
	$zip->addEmptyDir( $slug . '/' . $dir );
}

// Fixed timestamp (the commit time) keeps the archive reproducible.
$mtime = (int) trim( shell_exec( 'git show -s --format=%ct ' . escapeshellarg( $commit ) ) );

foreach ( $files as $file ) {
	$contents = shell_exec( 'git show ' . escapeshellarg( $commit . ':' . $file ) );
	$name     = $slug . '/' . $file;
	$zip->addFromString( $name, (string) $contents );
	$zip->setMtimeName( $name, $mtime );
}

$zip->close();

echo "Commit:  $commit\n";
echo "Version: $version\n";
echo "Files:   " . count( $files ) . "\n";
echo "ZIP:     $zip_path\n";
echo 'SHA-256: ' . hash_file( 'sha256', $zip_path ) . "\n";
