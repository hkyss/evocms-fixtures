<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Plan;

use InvalidArgumentException;

final class Recipe
{
    public function __construct(
        public readonly int $documents = 1000,
        public readonly int $folders = 0,
        public readonly int $maxDepth = 0,
        public readonly int $templates = 4,
        public readonly int $tmplvars = 10,
        public readonly int $valuesPerDocument = 4,
        public readonly int $users = 0,
        public readonly int $memberGroups = 0,
        public readonly int $documentGroups = 0,
    ) {
        $this->refuse($documents < 1, 'A batch of no documents is not a batch.');
        $this->refuse($folders < 0, 'Folders cannot be negative.');
        $this->refuse($folders > $documents, 'There cannot be more folders than documents.');
        $this->refuse($maxDepth < 0, 'A depth cannot be negative.');
        $this->refuse($maxDepth === 1 && $folders > 0, 'A tree one level deep holds no folders.');
        $this->refuse($templates < 1, 'A document needs a template to point at.');
        $this->refuse($tmplvars < 0, 'Template variables cannot be negative.');
        $this->refuse($valuesPerDocument < 0, 'Values per document cannot be negative.');
        $this->refuse(
            $valuesPerDocument > $tmplvars,
            'A document cannot carry more values than there are template variables to hold them.'
        );
        $this->refuse($users < 0, 'Users cannot be negative.');
        $this->refuse($memberGroups < 0, 'Member groups cannot be negative.');
        $this->refuse($documentGroups < 0, 'Document groups cannot be negative.');
        $this->refuse(
            $memberGroups > 0 && $users < 1,
            'A member group with nobody in it tests nothing; ask for users too.'
        );
        $this->refuse(
            $documentGroups > 0 && $memberGroups < 1,
            'Document groups are reached through member groups; ask for those too.'
        );
    }

    public function foldersOrDefault(): int
    {
        return $this->folders > 0 ? $this->folders : max(1, intdiv($this->documents, 50));
    }

    public function values(): int
    {
        return $this->documents * $this->valuesPerDocument;
    }

    public function describe(): string
    {
        $parts = [sprintf('%d documents', $this->documents)];

        if ($this->valuesPerDocument > 0) {
            $parts[] = sprintf('%d template variable values', $this->values());
        }

        if ($this->users > 0) {
            $parts[] = sprintf('%d users', $this->users);
        }

        if ($this->documentGroups > 0) {
            $parts[] = sprintf('%d document groups', $this->documentGroups);
        }

        return implode(', ', $parts);
    }

    /** @return array<string, int> */
    public function toArray(): array
    {
        return [
            'documents' => $this->documents,
            'folders' => $this->folders,
            'max_depth' => $this->maxDepth,
            'templates' => $this->templates,
            'tmplvars' => $this->tmplvars,
            'values_per_document' => $this->valuesPerDocument,
            'users' => $this->users,
            'member_groups' => $this->memberGroups,
            'document_groups' => $this->documentGroups,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['documents'] ?? 1),
            (int) ($data['folders'] ?? 0),
            (int) ($data['max_depth'] ?? 0),
            (int) ($data['templates'] ?? 1),
            (int) ($data['tmplvars'] ?? 0),
            (int) ($data['values_per_document'] ?? 0),
            (int) ($data['users'] ?? 0),
            (int) ($data['member_groups'] ?? 0),
            (int) ($data['document_groups'] ?? 0),
        );
    }

    private function refuse(bool $wrong, string $why): void
    {
        if ($wrong) {
            throw new InvalidArgumentException($why);
        }
    }
}
