<?php

declare(strict_types=1);

namespace Pu239\Forum\Services;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\MarkdownConverter;

final class MarkdownService
{
    private readonly MarkdownConverter $converter;
    private readonly HtmlSanitizer $sanitizer;

    public function __construct(
        ?MarkdownConverter $converter = null,
        ?HtmlSanitizer $sanitizer = null,
    ) {
        $this->converter = $converter ?? $this->createConverter();
        $this->sanitizer = $sanitizer ?? new HtmlSanitizer();
    }

    public function render(string $markdown): string
    {
        $unsafeHtml = $this->converter->convert($markdown)->getContent();

        return $this->sanitizer->sanitize($unsafeHtml);
    }

    private function createConverter(): MarkdownConverter
    {
        $environment = new Environment([
            'renderer' => [
                'soft_break' => "\n",
            ],
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new AutolinkExtension());
        $environment->addExtension(new StrikethroughExtension());
        $environment->addExtension(new TaskListExtension());

        return new MarkdownConverter($environment);
    }
}
