<?php

declare(strict_types=1);

namespace Canopy\Asset;

use Canopy\Editorial\CapabilityObservation;

final class AssetCapabilityEvaluator
{
    /** @param list<string> $values */
    public function evaluate(string $detector, array $values, AssetSnapshot $snapshot): CapabilityObservation
    {
        return match ($detector) {
            'reusable_media' => $this->reusableMedia($snapshot, $values),
            'media_source_wiring' => $this->mediaSourceWiring($snapshot),
            'upload_allowlists' => $this->uploadAllowlists($snapshot),
            'image_derivatives' => $this->configuredItems('image style', $snapshot->imageStyles),
            'responsive_images' => $this->responsiveImages($snapshot),
            'private_assets' => $this->privateAssets($snapshot),
            default => throw new \InvalidArgumentException(sprintf('Unknown asset capability detector: %s', $detector)),
        };
    }

    /** @param list<string> $requiredTypes */
    private function reusableMedia(AssetSnapshot $snapshot, array $requiredTypes): CapabilityObservation
    {
        $missingModules = array_values(array_diff(['media', 'media_library'], $snapshot->modules));
        $missingTypes = array_values(array_diff($requiredTypes, array_keys($snapshot->mediaTypes)));
        $satisfied = $missingModules === [] && $missingTypes === [];
        return new CapabilityObservation(
            $satisfied,
            $satisfied ? 'Reusable image and document media are configured.' : 'Required media modules or media types are missing.',
            ['media_types' => array_keys($snapshot->mediaTypes), 'missing_modules' => $missingModules, 'missing_media_types' => $missingTypes],
        );
    }

    private function mediaSourceWiring(AssetSnapshot $snapshot): CapabilityObservation
    {
        $unwired = [];
        foreach ($snapshot->mediaTypes as $id => $definition) {
            $storageId = 'media.' . $definition['source_field'];
            if ($definition['source'] === '' || $definition['source_field'] === '' || !isset($snapshot->fieldStorages[$storageId])) {
                $unwired[] = $id;
            }
        }
        $satisfied = $snapshot->mediaTypes !== [] && $unwired === [];
        return new CapabilityObservation(
            $satisfied,
            $satisfied ? sprintf('All %d media type source fields have matching file/image storage.', count($snapshot->mediaTypes)) : 'One or more media source fields are absent or lack matching file/image storage.',
            ['media_types' => $snapshot->mediaTypes, 'unwired_media_types' => $unwired],
        );
    }

    private function uploadAllowlists(AssetSnapshot $snapshot): CapabilityObservation
    {
        $unconstrained = [];
        foreach ($snapshot->uploadExtensions as $fieldConfigId => $extensions) {
            if ($extensions === []) {
                $unconstrained[] = $fieldConfigId;
            }
        }
        $unconstrained = array_values(array_unique($unconstrained));
        sort($unconstrained);
        $satisfied = $snapshot->uploadExtensions !== [] && $unconstrained === [];
        return new CapabilityObservation(
            $satisfied,
            $satisfied ? 'All discovered file/image fields have explicit extension allowlists.' : 'Some file/image fields have no explicit extension allowlist.',
            ['upload_extension_policies' => $snapshot->uploadExtensions, 'fields_without_allowlists' => $unconstrained],
        );
    }

    /** @param list<string> $items */
    private function configuredItems(string $kind, array $items): CapabilityObservation
    {
        return new CapabilityObservation(
            $items !== [],
            $items !== [] ? sprintf('Discovered %d configured %s(s).', count($items), $kind) : sprintf('No configured %ss were discovered.', $kind),
            ['items' => $items],
        );
    }

    private function responsiveImages(AssetSnapshot $snapshot): CapabilityObservation
    {
        $modulePresent = in_array('responsive_image', $snapshot->modules, true);
        $satisfied = $modulePresent && $snapshot->responsiveImageStyles !== [];
        return new CapabilityObservation(
            $satisfied,
            $satisfied ? sprintf('Responsive Image is enabled with %d configured style(s).', count($snapshot->responsiveImageStyles)) : 'Responsive Image is absent or has no configured styles.',
            ['module_present' => $modulePresent, 'responsive_image_styles' => $snapshot->responsiveImageStyles],
        );
    }

    private function privateAssets(AssetSnapshot $snapshot): CapabilityObservation
    {
        $privateFields = [];
        foreach ($snapshot->fieldStorages as $id => $storage) {
            if (in_array($storage['type'], ['file', 'image'], true) && $storage['uri_scheme'] === 'private') {
                $privateFields[] = $id;
            }
        }
        return new CapabilityObservation(
            $privateFields !== [],
            $privateFields !== [] ? sprintf('Discovered %d private file/image storage field(s).', count($privateFields)) : 'No private file/image storage fields were discovered.',
            ['private_storage_fields' => $privateFields],
        );
    }
}
