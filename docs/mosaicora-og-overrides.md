# Mosaicora OG Overrides v3

Add a `mosaicora:og` property to existing Schema.org JSON-LD when a page needs
exact values for its Mosaicora image. The v3 shape is:

```php
new MosaicoraOgOverride(
    templateId: '6a36446a0021410e8044', // Optional.
    semanticValues: [
        'content.title' => 'Exact title',
        'product.features' => ['Fast setup', 'Consistent previews'],
    ],
)
```

`schemaVersion` defaults to `3`. `semanticValues` is required and may be empty;
an optional `templateId` selects a saved Mosaicora template.

## Value types

- Text roles accept strings.
- List roles accept lists of strings.
- `social.verified` accepts a boolean.
- `analytics.metrics` accepts a list of `{id, label, value}` string maps. Use
  `MosaicoraOgMetric::toArray()` when constructing these maps from PHP values.

## Semantic roles

### Text

`content.title`, `content.description`, `content.category`, `content.url`,
`organization.name`, `person.name`, `person.role`, `person.image`,
`person.honorificPrefix`, `article.datePublished`, `publication.frequency`,
`product.price`, `product.originalPrice`, `offer.discount`, `offer.couponCode`,
`offer.shippingDetails`, `offer.validThrough`, `aggregateRating.ratingValue`,
`aggregateRating.reviewCount`, `software.releaseStatus`, `software.status`,
`software.version`, `job.baseSalary`, `job.experienceRequirements`, `job.skills`,
`place.name`, `recipe.season`, `recipe.prepTime`, `recipe.cookTime`,
`recipe.recipeYield`, `creativeWork.collectionLabel`, `creativeWork.dateCreated`,
`creativeWork.collectionId`, `interaction.commentsCount`, `social.followersCount`,
`social.followingCount`, `social.postsCount`, `social.mutualFriends`,
`collection.productCount`, `image.primary`.

### Lists

`product.features`, `navigation.items`, `image.secondary`.

### Boolean and metrics

`social.verified` and `analytics.metrics`.
