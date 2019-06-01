# laminas-amazon

`Laminas\Amazon\Amazon` is a simple API for using Amazon web
services. `Laminas\Amazon\Amazon` has two APIs: a more traditional
one that follows Amazon's own API, and a simpler "Query API" for
constructing even complex search queries easily.

`Laminas\Amazon\Amazon` enables developers to retrieve information
appearing throughout Amazon.com web sites directly through the Amazon
Web Services API. Examples include:

- Store item information, such as images, descriptions, pricing, and more
- Customer and editorial reviews
- Similar products and accessories
- Amazon.com offers
- ListMania lists

In order to use `Laminas\Amazon\Amazon`, you should already have an
Amazon developer API key as well as a secret key. To get a key and for
more information, please visit the [Amazon Web Services](https://aws.amazon.com/)
web site. As of August 15th, 2009 you can only use the Amazon Product
Advertising API through `Laminas\Amazon\Amazon`, when specifying the
additional secret key.

> #### Attention
>
> Your Amazon developer API and secret keys are linked to your Amazon
> identity, so take appropriate measures to keep them private.

### Search Amazon Using the Traditional API

In this example, we search for PHP books at Amazon and loop through
the results, printing them.

```php
$amazon = new Laminas\Amazon\Amazon('AMAZON_API_KEY', 'US', 'AMAZON_SECRET_KEY');
$results = $amazon->itemSearch([
    'SearchIndex' => 'Books',
    'Keywords' => 'php',
]);

foreach ($results as $result) {
    echo $result->Title . '<br/>';
}
```

### Search Amazon Using the Query API

Here, we also search for PHP books at Amazon, but we instead use the
Query API, which resembles the Fluent Interface design pattern.

```php
$query = new Laminas\Amazon\Query('AMAZON_API_KEY', 'US', 'AMAZON_SECRET_KEY');
$query->category('Books')->Keywords('PHP');
$results = $query->search();

foreach ($results as $result) {
    echo $result->Title . '<br/>';
}
```

## Country Codes

By default, `Laminas\Amazon\Amazon` connects to the United States
(`US`) Amazon web service. To connect from a different country, simply
specify the appropriate country code string as the second parameter to
the constructor:

### Choosing an Amazon Web Service Country

```php
// Connect to Amazon in Japan
$amazon = new Laminas\Amazon\Amazon('AMAZON_API_KEY', 'JP', 'AMAZON_SECRET_KEY');
```

> #### Country codes
>
> Valid country codes are: `CA`, `DE`, `ES`, `FR`, `IN`, `IT`, `JP`, `UK`, and `US`.

## Looking up a Specific Amazon Item by ASIN

The `itemLookup()` method provides the ability to fetch a particular
Amazon item when the ASIN is known.

### Looking up a Specific Amazon Item by ASIN

```php
$amazon = new Laminas\Amazon\Amazon('AMAZON_API_KEY', 'US', 'AMAZON_SECRET_KEY');
$item = $amazon->itemLookup('B0000A432X');
```

The `itemLookup()` method also accepts an optional second parameter for
handling search options. For full details, including a list of available
options, please see the [relevant Amazon documentation](https://docs.aws.amazon.com/AWSECommerceService/latest/DG/ItemSearch.html).

> #### Image information
>
> To retrieve images information for your search results, you must set
> `ResponseGroup` option to `Medium` or `Large`.

## Performing Amazon Item Searches

Searching for items based on any of various available criteria are made
simple using the `itemSearch()` method, as in the following example:

### Performing Amazon Item Searches

```php
$amazon = new Laminas\Amazon\Amazon('AMAZON_API_KEY', 'US', 'AMAZON_SECRET_KEY');
$results = $amazon->itemSearch([
    'SearchIndex' => 'Books',
    'Keywords' => 'php',
]);

foreach ($results as $result) {
    echo $result->Title . '<br/>';
}
```

### Using the ResponseGroup Option

The `ResponseGroup` option is used to control the specific information
that will be returned in the response.

```php
$amazon = new Laminas\Amazon\Amazon('AMAZON_API_KEY', 'US', 'AMAZON_SECRET_KEY');
$results = $amazon->itemSearch([
    'SearchIndex'   => 'Books',
    'Keywords'      => 'php',
    'ResponseGroup' => 'Small,ItemAttributes,Images,SalesRank,Reviews,'
                       . 'EditorialReview,Similarities,ListmaniaLists'
]);

foreach ($results as $result) {
    echo $result->Title . '<br/>';
}
```

The `itemSearch()` method accepts a single array parameter for handling
search options. For full details, including a list of available options,
please see the [relevant Amazon documentation](https://docs.aws.amazon.com/AWSECommerceService/latest/DG/ItemSearch.html)

> #### Tip
>
> The `Laminas\Amazon\Query` class is an easy to use wrapper around this method.

## Using the Alternative Query API

### Introduction

`Laminas\Amazon\Query` provides an alternative API for using the
Amazon Web Service. The alternative API uses the Fluent Interface
pattern. That is, all calls can be made using chained method calls.
(e.g., `$obj->method()->method2($arg)`)

The `Laminas\Amazon\Query` API uses overloading to easily set up
an item search and then allows you to search based upon the criteria
specified. Each of the options is provided as a method call, and each
method's argument corresponds to the named option's value:

#### Search Amazon Using the Alternative Query API

In this example, the alternative query API is used as a fluent
interface to specify options and their respective values:

```php
$query = new Laminas\Amazon\Query('MY_API_KEY', 'US', 'AMAZON_SECRET_KEY');
$query->Category('Books')->Keywords('PHP');
$results = $query->search();

foreach ($results as $result) {
    echo $result->Title . '<br/>';
}
```

This sets the option `Category` to "Books" and `Keywords` to "PHP".

For more information on the available options, please refer to the
[relevant Amazon documentation](https://docs.aws.amazon.com/AWSECommerceService/latest/DG/ItemSearch.html).

## `Laminas\Amazon` Classes

The following classes are all returned by `Laminas\Amazon\Amazon::itemLookup()`
and `Laminas\Amazon\Amazon::itemSearch()`:

- `Laminas\Amazon\Item`
- `Laminas\Amazon\Image`
- `Laminas\Amazon\ResultSet`
- `Laminas\Amazon\OfferSet`
- `Laminas\Amazon\Offer`
- `Laminas\Amazon\SimilarProduct`
- `Laminas\Amazon\Accessories`
- `Laminas\Amazon\CustomerReview`
- `Laminas\Amazon\EditorialReview`
- `Laminas\Amazon\ListMania`

### `Laminas\Amazon\Item`

`Laminas\Amazon\Item` is the class type used to represent an Amazon
item returned by the web service. It encompasses all of the items
attributes, including title, description, reviews, etc.

`Laminas\Amazon\Item::asXML() : string`

Returns the original XML for the item

**Properties**

`Laminas\Amazon\Item` has a number of properties directly related to
their standard Amazon API counterparts.

Name               | Type                      | Description
------------------ | ------------------------- | -----------
`ASIN`             | `string`                  | Amazon Item ID
`DetailPageURL`    | `string`                  | URL to the Items Details Page
`SalesRank`        | `int`                     | Sales Rank for the Item
`SmallImage`       | `Laminas\Amazon\Image`    | Small Image of the Item
`MediumImage`      | `Laminas\Amazon\Image`    | Medium Image of the Item
`LargeImage`       | `Laminas\Amazon\Image`    | Large Image of the Item
`Subjects`         | `array`                   | Item Subjects
`Offers`           | `Laminas\Amazon\OfferSet` | Offer Summary and Offers for the Item
`CustomerReviews`  | `array`                   | Customer reviews represented as an array of `Laminas\Amazon\CustomerReview` objects
`EditorialReviews` | `array`                   | Editorial reviews represented as an array of `Laminas\Amazon\EditorialReview` objects
`SimilarProducts`  | `array`                   | Similar Products represented as an array of `Laminas\Amazon\SimilarProduct` objects
`Accessories`      | `array`                   | Accessories for the item represented as an array of `Laminas\Amazon\Accessories` objects
`Tracks`           | `array`                   | An array of track numbers and names for Music CDs and DVDs
`ListmaniaLists`   | `array`                   | Item related Listmania Lists as an array of `Laminas\Amazon\ListmaniaList` objects
`PromotionalTag`   | `string`                  | Item Promotional Tag

### `Laminas\Amazon\Image`

`Laminas\Amazon\Image` represents a remote Image for a product.

**Properties**

Name     | Type              | Description
-------- | ----------------- | -----------
`Url`    | `Laminas\Uri\Uri` | Remote URL for the Image
`Height` | `int`             | The Height of the image in pixels
`Width`  | `int`             | The Width of the image in pixels

### `Laminas\Amazon\ResultSet`

`Laminas\Amazon\ResultSet` objects are returned by `Laminas\Amazon\Amazon::itemSearch()`
and allow you to easily handle the multiple results returned.

> #### SeekableIterator
>
> Implements the `SeekableIterator` for easy iteration (e.g. using
> `foreach`), as well as direct access to a specific result using
> `seek()`.

`Laminas\\Amazon\\ResultSet::totalResults() : int`

Returns the total number of results returned by the search

### `Laminas\Amazon\OfferSet`

Each result returned by `Laminas\Amazon\Amazon::itemSearch()`
and `Laminas\Amazon\Amazon::itemLookup()` contains a `Laminas\Amazon\OfferSet`
object through which pricing information for the item can be retrieved.

**Properties**

Name                     | Type     | Description
------------------------ | -------- | -----------
`LowestNewPrice`         | `int`    | Lowest Price for the item in "New" condition
`LowestNewPriceCurrency` | `string` | The currency for the LowestNewPrice
`LowestOldPrice`         | `int`    | Lowest Price for the item in "Used" condition
`LowestOldPriceCurrency` | `string` | The currency for the LowestOldPrice
`TotalNew`               | `int`    | Total number of "new" condition available for the item
`TotalUsed`              | `int`    | Total number of "used' condition available for the item
`TotalCollectible`       | `int`    | Total number of "collectible" condition available for the item
`TotalRefurbished`       | `int`    | Total number of "refurbished" condition available for the item
`Offers`                 | `array`  | An array of `Laminas\Amazon\Offer` objects.

### `Laminas\Amazon\Offer`

Each offer for an item is returned as an `Laminas\Amazon\Offer` object.

**Properties**

Name                              | Type     | Description
--------------------------------- | -------- | -----------
`MerchantId`                      | `string` | Merchants Amazon ID
`MerchantName`                    | `string` | Merchants Amazon Name. Requires setting the `ResponseGroup` option to `OfferFull` to retrieve.
`GlancePage`                      | `string` | URL for a page with a summary of the Merchant
`Condition`                       | `string` | Condition of the item
`OfferListingId`                  | `string` | ID of the Offer Listing
`Price`                           | `int`    | Price for the item
`CurrencyCode`                    | `string` | Currency Code for the price of the item
`Availability`                    | `string` | Availability of the item
`IsEligibleForSuperSaverShipping` | `bool`   | Whether the item is eligible for Super Saver Shipping or not

### `Laminas\Amazon\SimilarProduct`

When searching for items, Amazon also returns a list of similar products
that the searcher may find to their liking. Each of these is returned as
a `Laminas\Amazon\SimilarProduct` object.

Each object contains the information to allow you to make sub-sequent
requests to get the full information on the item.

**Properties**

Name    | Type     | Description
------- | -------- | -----------
`ASIN`  | `string` | Products Amazon Unique ID (ASIN)
`Title` | `string` | Products Title

### `Laminas\Amazon\Accessories`

Accessories for the returned item are represented as `Laminas\Amazon\Accessories` objects.

**Properties**

Name    | Type     | Description
------- | -------- | -----------
`ASIN`  | `string` | Products Amazon Unique ID (ASIN)
`Title` | `string` | Products Title

### `Laminas\Amazon\CustomerReview`

Each Customer Review is returned as a `Laminas\Amazon\CustomerReview` object.

**Properties**

Name           | Type     | Description
-------------- | -------- | -----------
`Rating`       | `string` | Item Rating
`HelpfulVotes` | `string` | Votes on how helpful the review is
`CustomerId`   | `string` | Customer ID
`TotalVotes`   | `string` | Total Votes
`Date`         | `string` | Date of the Review
`Summary`      | `string` | Review Summary
`Content`      | `string` | Review Content

### `Laminas\Amazon\EditorialReview`

Each items Editorial Reviews are returned as a `Laminas\Amazon\EditorialReview` object.

**Properties**

Name      | Type     | Description
--------- | -------- | -----------
`Source`  | `string` | Source of the Editorial Review
`Content` | `string` | Review Content

### `Laminas\Amazon\Listmania`

Each results List Mania List items are returned as `Laminas\Amazon\Listmania` objects.

**Properties**

Name       | Type     | Description
---------- | -------- | -----------
`ListId`   | `string` | List ID
`ListName` | `string` | List Name
