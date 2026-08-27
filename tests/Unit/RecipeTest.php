<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Tests\Unit;

use hkyss\Fixtures\Plan\Recipe;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RecipeTest extends TestCase
{
    public function testItRefusesABatchOfNothing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Recipe(documents: 0);
    }

    public function testADocumentCannotCarryMoreValuesThanThereAreVariables(): void
    {
        $this->expectExceptionMessage('more values than there are template variables');

        new Recipe(documents: 10, tmplvars: 3, valuesPerDocument: 4);
    }

    public function testAMemberGroupWithoutUsersIsRefused(): void
    {
        $this->expectExceptionMessage('tests nothing');

        new Recipe(documents: 10, users: 0, memberGroups: 2);
    }

    public function testDocumentGroupsNeedMemberGroupsToBeReachedThrough(): void
    {
        $this->expectExceptionMessage('reached through member groups');

        new Recipe(documents: 10, users: 5, memberGroups: 0, documentGroups: 2);
    }

    public function testFoldersDefaultToAFractionOfTheDocuments(): void
    {
        $this->assertSame(20, (new Recipe(documents: 1000))->foldersOrDefault());
        $this->assertSame(1, (new Recipe(documents: 10))->foldersOrDefault());
        $this->assertSame(7, (new Recipe(documents: 1000, folders: 7))->foldersOrDefault());
    }

    public function testItCountsTheValuesItWillWrite(): void
    {
        $this->assertSame(4000, (new Recipe(documents: 1000, tmplvars: 10, valuesPerDocument: 4))->values());
    }

    public function testItSurvivesTheTripThroughTheLedger(): void
    {
        $recipe = new Recipe(documents: 500, folders: 9, templates: 3, tmplvars: 8, valuesPerDocument: 2, users: 4, memberGroups: 2, documentGroups: 1);

        $back = Recipe::fromArray($recipe->toArray());

        $this->assertSame($recipe->toArray(), $back->toArray());
    }

    public function testItDescribesOnlyWhatItWillActuallyMake(): void
    {
        $this->assertSame(
            '10 documents',
            (new Recipe(documents: 10, tmplvars: 0, valuesPerDocument: 0))->describe()
        );
        $this->assertStringContainsString(
            '4 users',
            (new Recipe(documents: 10, users: 4, memberGroups: 1, documentGroups: 1))->describe()
        );
    }
}
