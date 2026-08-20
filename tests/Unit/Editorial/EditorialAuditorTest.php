<?php

declare(strict_types=1);

namespace Canopy\Tests\Unit\Editorial;

use Canopy\Editorial\EditorialAuditor;
use Canopy\Editorial\EditorialProfileLoader;
use Canopy\Inventory\ProjectTarget;
use Canopy\Result\Status;
use PHPUnit\Framework\TestCase;

final class EditorialAuditorTest extends TestCase
{
    public function testEvaluatesRequiredPreferredAndOptionalCapabilities(): void
    {
        $root = dirname(__DIR__, 2) . '/Fixtures/editorial';
        $profile = (new EditorialProfileLoader())->load(dirname(__DIR__, 3) . '/config/editorial/nics.yml');
        $results = (new EditorialAuditor())->audit([new ProjectTarget('example', $root)], $profile);

        self::assertSame(Status::Pass, $this->statusFor($results, 'editorial.capability.revision_history'));
        self::assertSame(Status::Pass, $this->statusFor($results, 'editorial.capability.role_separation'));
        self::assertSame(Status::Pass, $this->statusFor($results, 'editorial.capability.review_scheduling'));
        self::assertSame(Status::Pass, $this->statusFor($results, 'editorial.capability.scheduled_publishing'));
        self::assertSame(Status::Pass, $this->statusFor($results, 'editorial.capability.concurrent_editing_protection'));
        self::assertSame(Status::Pass, $this->statusFor($results, 'editorial.capability.basic_html_text_format'));
        self::assertSame(Status::Pass, $this->statusFor($results, 'editorial.capability.revision_comparison_configuration'));
        $textFormat = $this->resultFor($results, 'editorial.capability.basic_html_text_format');
        self::assertSame(
            'NIDirect enables Drupal image lazy-loading for inline content.',
            $textFormat->evidence['optional_filters']['registered_variations']['filter_image_lazy_load'],
        );
    }

    public function testClassifiesCoreOptionalAndUnexpectedToolbarItems(): void
    {
        $root = dirname(__DIR__, 2) . '/Fixtures/editorial';
        $profilePath = tempnam(sys_get_temp_dir(), 'canopy-editorial-profile-');
        self::assertIsString($profilePath);
        file_put_contents($profilePath, <<<'YAML'
id: toolbar_test
capabilities:
  revisions:
    label: Revisions
    expectation: required
    detector: moderated_bundles_revisioned
    exclude: [webform]
ckeditor5_toolbars:
  basic_html:
    label: Basic HTML toolbar
    expectation: required
    core: [heading, link, insertTable, missingButton]
    optional:
      - blockQuote
      - superscript
      - removeFormat
      - style
      - bulletedList
      - numberedList
      - drupalMedia
      - location
      - undo
      - redo
      - importWord
      - specialCharacters
      - textPartLanguage
      - fullScreen
    unexpected: fail
YAML);

        try {
            $profile = (new EditorialProfileLoader())->load($profilePath);
            $results = (new EditorialAuditor())->audit([new ProjectTarget('example', $root)], $profile);
        } finally {
            unlink($profilePath);
        }

        $missingCore = $this->resultFor($results, 'editorial.capability.basic_html_toolbar');
        self::assertSame(Status::Fail, $missingCore->status);
        self::assertSame(['missingButton'], $missingCore->evidence['core']['missing']);
        self::assertContains('fullScreen', $missingCore->evidence['optional']['present']);
        self::assertSame([], $missingCore->evidence['unexpected']['items']);
    }

    public function testFailsAnUnexpectedToolbarItem(): void
    {
        $root = dirname(__DIR__, 2) . '/Fixtures/editorial';
        $profilePath = tempnam(sys_get_temp_dir(), 'canopy-editorial-profile-');
        self::assertIsString($profilePath);
        file_put_contents($profilePath, <<<'YAML'
id: toolbar_test
capabilities: {  }
ckeditor5_toolbars:
  basic_html:
    core: [heading, link]
    optional:
      - blockQuote
      - superscript
      - removeFormat
      - style
      - insertTable
      - bulletedList
      - numberedList
      - drupalMedia
      - location
      - undo
      - redo
      - importWord
      - specialCharacters
      - textPartLanguage
    unexpected: fail
YAML);

        try {
            $profile = (new EditorialProfileLoader())->load($profilePath);
            $results = (new EditorialAuditor())->audit([new ProjectTarget('example', $root)], $profile);
        } finally {
            unlink($profilePath);
        }

        $unexpected = $this->resultFor($results, 'editorial.capability.basic_html_toolbar');
        self::assertSame(Status::Fail, $unexpected->status);
        self::assertSame([], $unexpected->evidence['core']['missing']);
        self::assertSame(['fullScreen'], $unexpected->evidence['unexpected']['items']);
    }

    /** @param list<\Canopy\Result\Result> $results */
    private function statusFor(array $results, string $checkId): Status
    {
        foreach ($results as $result) {
            if ($result->checkId === $checkId) {
                return $result->status;
            }
        }
        self::fail(sprintf('Missing result %s.', $checkId));
    }

    /** @param list<\Canopy\Result\Result> $results */
    private function resultFor(array $results, string $checkId): \Canopy\Result\Result
    {
        foreach ($results as $result) {
            if ($result->checkId === $checkId) {
                return $result;
            }
        }
        self::fail(sprintf('Missing result %s.', $checkId));
    }
}
