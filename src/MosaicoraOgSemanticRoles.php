<?php
declare(strict_types=1);

namespace Mosaicora\PluginCore;

/**
 * Runtime catalog for the public Mosaicora OG v3 semantic-value contract.
 */
final class MosaicoraOgSemanticRoles
{
    public const TYPE_TEXT = 'text';
    public const TYPE_LIST = 'list';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_METRICS = 'metrics';

    /** @var list<string> */
    private const TEXT_ROLES = [
        'content.title',
        'content.description',
        'content.category',
        'content.url',
        'organization.name',
        'person.name',
        'person.role',
        'person.image',
        'person.honorificPrefix',
        'article.datePublished',
        'publication.frequency',
        'product.price',
        'product.originalPrice',
        'offer.discount',
        'offer.couponCode',
        'offer.shippingDetails',
        'offer.validThrough',
        'aggregateRating.ratingValue',
        'aggregateRating.reviewCount',
        'software.releaseStatus',
        'software.status',
        'software.version',
        'job.baseSalary',
        'job.experienceRequirements',
        'job.skills',
        'place.name',
        'recipe.season',
        'recipe.prepTime',
        'recipe.cookTime',
        'recipe.recipeYield',
        'creativeWork.collectionLabel',
        'creativeWork.dateCreated',
        'creativeWork.collectionId',
        'interaction.commentsCount',
        'social.followersCount',
        'social.followingCount',
        'social.postsCount',
        'social.mutualFriends',
        'collection.productCount',
        'image.primary',
    ];

    /** @var list<string> */
    private const LIST_ROLES = [
        'product.features',
        'navigation.items',
        'image.secondary',
    ];

    /** @return array<string, self::TYPE_*> */
    public static function definitions(): array
    {
        $definitions = [];
        foreach (self::TEXT_ROLES as $role) {
            $definitions[$role] = self::TYPE_TEXT;
        }
        foreach (self::LIST_ROLES as $role) {
            $definitions[$role] = self::TYPE_LIST;
        }

        $definitions['social.verified'] = self::TYPE_BOOLEAN;
        $definitions['analytics.metrics'] = self::TYPE_METRICS;

        return $definitions;
    }

    public static function typeFor(string $role): ?string
    {
        return self::definitions()[$role] ?? null;
    }

    public static function supports(string $role): bool
    {
        return self::typeFor($role) !== null;
    }

    public static function acceptsValue(string $role, mixed $value): bool
    {
        return match (self::typeFor($role)) {
            self::TYPE_TEXT => is_string($value),
            self::TYPE_LIST => self::isStringList($value),
            self::TYPE_BOOLEAN => is_bool($value),
            self::TYPE_METRICS => self::isMetricsList($value),
            default => false,
        };
    }

    private static function isStringList(mixed $value): bool
    {
        if (!is_array($value) || !array_is_list($value)) {
            return false;
        }

        foreach ($value as $entry) {
            if (!is_string($entry)) {
                return false;
            }
        }

        return true;
    }

    private static function isMetricsList(mixed $value): bool
    {
        if (!is_array($value) || !array_is_list($value)) {
            return false;
        }

        foreach ($value as $metric) {
            if (
                !is_array($metric)
                || array_keys($metric) !== ['id', 'label', 'value']
                || !is_string($metric['id'])
                || !is_string($metric['label'])
                || !is_string($metric['value'])
            ) {
                return false;
            }
        }

        return true;
    }
}
