<?php

declare(strict_types=1);

namespace LaravelKagero\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Filesystem\Path;

#[Signature('kagero:generate')]
#[Description('Generate the root view manifest for Kagero')]
final class KageroGenerateCommand extends Command
{
    public function __construct(private readonly Vite $vite)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->vite->useHotFile('')->createAssetPathsUsing(fn (string $path) => '/'.$path);

        $manifestPath = public_path('build/manifest.json');

        if (! File::exists($manifestPath)) {
            $this->fail("Vite manifest not found at: $manifestPath");
        }

        /** @var array<string, array<string, mixed>> $manifest */
        $manifest = File::json($manifestPath, JSON_THROW_ON_ERROR);

        $rootViewPath = resource_path('js/kagero/root-view.json');

        File::ensureDirectoryExists(dirname($rootViewPath));

        File::replace($rootViewPath, json_encode([
            'version' => hash_file('xxh128', $manifestPath),
            'vite' => $this->vite->withEntryPoints($this->entryPoints($manifest))->toHtml(),
            'pages' => (object) $this->pages($manifest),
            'fonts' => $this->vite->fonts()->toHtml(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        $this->components->info("Root view manifest for Kagero [$rootViewPath] generated successfully.");
    }

    /**
     * @param  array<string, array<string, mixed>>  $manifest
     * @return array<int, string>
     */
    private function entryPoints(array $manifest): array
    {
        return collect($manifest)->where('isEntry')->keys()->all();
    }

    /**
     * @param  array<string, array<string, mixed>>  $manifest
     * @return array<array-key, string>
     */
    private function pages(array $manifest): array
    {
        /** @var list<string> $paths */
        $paths = config('inertia.pages.paths');

        /** @var list<string> $extensions */
        $extensions = config('inertia.pages.extensions');

        $pages = [];

        foreach (array_keys($manifest) as $key) {
            foreach ($paths as $path) {
                if (Path::isBasePath($path, $file = base_path($key)) && Path::hasExtension($file, $extensions)) {
                    $pages[Str::beforeLast(Path::makeRelative($file, $path), '.')] = $this->vite->withEntryPoints([$key])->toHtml();
                }
            }
        }

        return $pages;
    }
}
