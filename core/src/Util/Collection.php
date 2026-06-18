<?php

declare(strict_types=1);

namespace App\Util;

class Collection
{
    public static function removeEmptyElements(mixed $arr, array $values = [null, []]): mixed
    {
        if (!is_array($arr)) {
            return $arr;
        }

        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $arr[$key] = self::removeEmptyElements($value, $values);
            }

            if (in_array($arr[$key], $values, true)) {
                unset($arr[$key]);
            }
        }

        return $arr;
    }

    /**
     * Recursively remove members whose value is an empty string.
     *
     * Unlike {@see removeEmptyElements} (which operates on data decoded as
     * associative arrays), this method operates on JSON decoded with objects
     * preserved as stdClass. This is required for CASE import normalization:
     * an empty JSON object such as "extensions": {} must round-trip back to {}
     * and must not be converted into an empty array [] (which would fail schema
     * validation for object-typed fields like Extensions).
     */
    public static function removeEmptyStrings(mixed $data): mixed
    {
        if ($data instanceof \stdClass) {
            $data = get_object_vars($data);
            foreach ($data as $key => $value) {
                $value = self::removeEmptyStrings($value);
                if ('' === $value) {
                    unset($data[$key]);
                } else {
                    $data[$key] = $value;
                }
            }

            return (object) $data;
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $value = self::removeEmptyStrings($value);
                if ('' === $value) {
                    unset($data[$key]);
                } else {
                    $data[$key] = $value;
                }
            }

            return $data;
        }

        return $data;
    }

    /**
     * Recursively remove "extensions" from objects where the CASE schema does not
     * define it as a valid property.
     *
     * The CASE v1.1 schema defines "extensions" on all CF object types
     * (CFPackageDocument, CFPackageItem, CFPackageAssociation, etc.) but NOT on
     * LinkURI, LinkGenURI, or the top-level package object (all of which have
     * additionalProperties: false). Source data may include "extensions" on these
     * objects; this method strips them so schema validation passes.
     *
     * An object is treated as a LinkURI/LinkGenURI (and has extensions removed)
     * when it has "extensions" as a property and its other properties are a
     * subset of {title, identifier, uri, targetType}.
     */
    public static function stripUnsupportedExtensions(mixed $data): mixed
    {
        if ($data instanceof \stdClass) {
            $data = get_object_vars($data);
            // Always strip extensions from the top-level package object (it has
            // additionalProperties: false and does not define extensions).
            unset($data['extensions']);

            foreach ($data as $key => $value) {
                $data[$key] = self::stripExtensionsFromLinkUris($value);
            }

            return (object) $data;
        }

        if (is_array($data)) {
            unset($data['extensions']);

            foreach ($data as $key => $value) {
                $data[$key] = self::stripExtensionsFromLinkUris($value);
            }

            return $data;
        }

        return $data;
    }

    /**
     * Recursively strip "extensions" from LinkURI/LinkGenURI objects where the
     * CASE schema does not define it. These objects have additionalProperties: false
     * and their properties are a subset of {title, identifier, uri, targetType}.
     */
    private static function stripExtensionsFromLinkUris(mixed $data): mixed
    {
        if ($data instanceof \stdClass) {
            $data = get_object_vars($data);
            if (array_key_exists('extensions', $data)) {
                $linkUriKeys = ['title', 'identifier', 'uri', 'targetType'];
                if (array_key_exists('identifier', $data) && array_key_exists('uri', $data)
                    && [] === array_diff(array_keys($data), $linkUriKeys, ['extensions'])
                ) {
                    unset($data['extensions']);
                }
            }

            foreach ($data as $key => $value) {
                $data[$key] = self::stripExtensionsFromLinkUris($value);
            }

            return (object) $data;
        }

        if (is_array($data)) {
            if (array_key_exists('extensions', $data)) {
                $linkUriKeys = ['title', 'identifier', 'uri', 'targetType'];
                if (array_key_exists('identifier', $data) && array_key_exists('uri', $data)
                    && [] === array_diff(array_keys($data), $linkUriKeys, ['extensions'])
                ) {
                    unset($data['extensions']);
                }
            }

            foreach ($data as $key => $value) {
                $data[$key] = self::stripExtensionsFromLinkUris($value);
            }

            return $data;
        }

        return $data;
    }

    /**
     * Convert an empty string to null, leaving all other values (including null) unchanged.
     *
     * Used in CASE export normalizers to treat nullable fields that contain an empty
     * string as if they were null, so removeEmptyElements() strips them from the output.
     * Non-nullable fields with empty strings are not affected since they are not wrapped
     * with this helper.
     */
    public static function emptyToNull(?string $value): ?string
    {
        return '' === $value ? null : $value;
    }
}
