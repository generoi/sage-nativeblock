<?php

namespace Genero\Sage\NativeBlock;

use Illuminate\Support\Str;
use Roots\Acorn\Application;

class NativeBlock
{
    protected $app;
    protected $attributeDefinitions = [];

    public $name;
    public $attributes;
    public $classes;
    public $className;
    public $content;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function compose()
    {
        add_action('init', [$this, 'register']);
    }

    public function register(): void
    {
        $blockType = $this->name;

        if ($jsonPath = $this->blockJsonPath()) {
            $blockType = $jsonPath;
            $json = json_decode(file_get_contents($jsonPath));

            if ($json->attributes ?? false) {
                $this->attributeDefinitions = $json->attributes;
            }
        }

        register_block_type($blockType, $this->build());
    }

    public function build()
    {
        return [
            'render_callback' => function ($attributes, $content, $block) {
                return $this->render($attributes, $content, $block);
            }
        ];
    }

    public function blockJsonPath(): string
    {
        $slug = Str::after($this->name, '/');
        $prefixedSlug = str_replace('/', '-', $this->name);

        return locate_template([
            "resources/scripts/editor/blocks/$prefixedSlug/block.json",
            "resources/scripts/editor/blocks/$slug/block.json",
            "resources/assets/scripts/editor/blocks/$prefixedSlug/block.json",
            "resources/assets/scripts/editor/blocks/$slug/block.json",
        ], false, false);
    }

    public function render($attributes, $content, $block)
    {
        $this->attributes = (object) array_merge($this->metaAttributes(), $attributes);
        $this->content = $content;
        $this->className = Str::start(Str::slug(Str::replaceFirst('/', '-', $this->name), '-'), 'wp-block-');
        $this->classes = collect([
            'slug' => $this->className,
            'align' => !empty($this->attributes->align) ? Str::start($this->attributes->align, 'align') : false,
            'color' => !empty($this->attributes->textColor) ? "has-{$this->attributes->textColor}-color has-text-color" : false,
            'background' => !empty($this->attributes->backgroundColor) ? "has-{$this->attributes->backgroundColor}-background-color has-background" : false,
            'classes' => $this->attributes->className ?? false,
        ])->filter()->implode(' ');

        return $this->view(
            Str::finish('views.blocks.', Str::after($this->name, '/')),
            [
                'block' => $this,
                'content' => $this->content,
                'context' => $block->context,
            ]
        );
    }

    public function metaAttributes()
    {
        $attributes = [];
        foreach ($this->attributeDefinitions as $name => $definition) {
            if (!isset($definition->source) || $definition->source !== 'meta') {
                continue;
            }

            $attributes[$name] = get_registered_metadata('post', get_the_ID(), $name);
        }

        return $attributes;
    }

    public function with()
    {
        return [];
    }

    public function view($view, $with = [])
    {
        $view = get_template_directory() . "/resources/" .
                Str::finish(
                    str_replace( '.', '/', basename( $view, '.blade.php' ) ),
                    '.blade.php'
                );

        if (!file_exists($view)) {
            return;
        }

        return $this->app->make('view')->file(
            $view,
            array_merge($with, $this->with())
        )->render();
    }
}
