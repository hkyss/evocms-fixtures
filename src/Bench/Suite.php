<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Bench;

final class Suite
{
    /** @return list<Probe> */
    public static function evolution(string $prefix): array
    {
        $content = sprintf('`%ssite_content`', $prefix);
        $closure = sprintf('`%ssite_content_closure`', $prefix);
        $links = sprintf('`%ssite_tmplvar_templates`', $prefix);
        $values = sprintf('`%ssite_tmplvar_contentvalues`', $prefix);

        return [
            new Probe(
                'children of a folder',
                sprintf('SELECT `id` FROM %s WHERE `parent` = ? AND `deleted` = 0 AND `published` = 1 ORDER BY `menuindex`', $content),
                ['folder'],
                'site_content'
            ),
            new Probe(
                'alias inside a parent',
                sprintf('SELECT `id` FROM %s WHERE `alias` = ? AND `parent` = ?', $content),
                ['alias', 'parent'],
                'site_content'
            ),
            new Probe(
                'subtree by ancestor',
                sprintf('SELECT `descendant` FROM %s WHERE `ancestor` = ?', $closure),
                ['folder'],
                'site_content_closure'
            ),
            new Probe(
                'breadcrumbs by descendant',
                sprintf('SELECT `ancestor` FROM %s WHERE `descendant` = ? ORDER BY `depth`', $closure),
                ['document'],
                'site_content_closure'
            ),
            new Probe(
                'template variables of a template',
                sprintf('SELECT `tmplvarid` FROM %s WHERE `templateid` = ? ORDER BY `rank`', $links),
                ['template'],
                'site_tmplvar_templates'
            ),
            new Probe(
                'documents of a template',
                sprintf('SELECT `id` FROM %s WHERE `template` = ?', $content),
                ['template'],
                'site_content'
            ),
            new Probe(
                'values of one document',
                sprintf('SELECT `tmplvarid`, `value` FROM %s WHERE `contentid` = ?', $values),
                ['document'],
                'site_tmplvar_contentvalues'
            ),
        ];
    }
}
