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
    public function handle(Vite $vite): void
    {
        $manifestPath = public_path('build/manifest.json');

        if (! File::exists($manifestPath)) {
            $this->fail('Vite manifest not found. Run `npm run build` first.');
        }

        /** @var array<string, array<string, mixed>> $manifest */
        $manifest = File::json($manifestPath, JSON_THROW_ON_ERROR);

        $entries = collect($manifest)->where('isEntry', true)->keys()->all();

        if ($entries === []) {
            $this->fail('Vite manifest has no entries. Run `npm run build` first.');
        }

        $vite->useHotFile('')->createAssetPathsUsing(fn (string $path): string => '/'.$path);

        $rootView = [
            'version' => hash_file('xxh128', $manifestPath),
            'fonts' => $vite->fonts()->toHtml(),
            'vite' => $vite($entries)->toHtml(),
            'pages' => (object) $this->pages($vite, $manifest),
        ];

        $json = json_encode($rootView, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        File::ensureDirectoryExists(resource_path('js/kagero'));
        File::replace(resource_path('js/kagero/root-view.json'), $json);

        $this->components->info('Kagero root view generated at [resources/js/kagero/root-view.json].');
    }

    /**
     * @param  array<string, array<string, mixed>>  $manifest
     * @return array<string, string>
     */
    private function pages(Vite $vite, array $manifest): array
    {
        /** @var list<string> $paths */
        $paths = config('inertia.pages.paths');

        /** @var list<string> $extensions */
        $extensions = config('inertia.pages.extensions');

        $pages = [];

        foreach (array_keys($manifest) as $key) {
            $file = base_path($key);

            foreach ($paths as $path) {
                if (Path::isBasePath($path, $file) && Path::hasExtension($file, $extensions)) {
                    $pages[Str::beforeLast(Path::makeRelative($file, $path), '.')] = $vite([$key])->toHtml();
                }
            }
        }

        return $pages;
    }
}
