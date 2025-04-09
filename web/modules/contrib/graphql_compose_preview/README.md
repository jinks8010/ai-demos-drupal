# GraphQL Compose: Preview

A module for extending the permissions of Drupal's preview functionality, to be used with GraphQL Compose.

## Usage

### TL;DR

- Enable module.
- Add permissions.
- Query data with tokens.

### Permissions

- Go to `/admin/people/permissions` and search for `GraphQL Compose: Preview`
- Enable the `View preview entities` permission for each role that can use tokenized preview links.

### GraphQL Query

This module introduces the `preview` query, which takes an optional `token` argument,
which returns a `NodeUnion` type.

```graphql
{
  preview(id: "da02328a-b605-421e-8077-f2ff344c5d49", token: "ABC123FB9dsOKU") {
    ... on NodePage {
      title
      status
    }
  }
}
```

### Route Query

This module allows tokenized preview URLs.

Example:

- Edit a node, click `Preview`
- URL is a uuid path: `/node/preview/da02328a-b605-421e-8077-f2ff344c5d49/full`
- Add the unique token to that URL: `/node/preview/da02328a-b605-421e-8077-f2ff344c5d49/full?token=ABC123FB9dsOKU`

You'll not have full access to that preview.

This can be used in conjunction with GraphQL Compose's `route` query:

```graphql
  route(path: "/node/preview/da02328a-b605-421e-8077-f2ff344c5d49/full?token=ABC123FB9dsOKU") {
    ... on RouteInternal {
      entity {
        ... on NodePage {
          title
          status
        }
      }
    }
  }
```

### Embedded field on preview display

This module also introduces a `preview_token` field formatter to the Drupal node UI, which can used to add a preview display.

This can render a tokenized link to the preview route, and an iframe formatter to embed your frontend app.
How you ultimately implement this is up to your frontend application.

Example:

- Go to `/admin/structure/types/manage/page/display`
- Drag `Preview with Token` to the top of the list.
- Configure the formatter to your liking.

Optionally set the ENV `GRAPHQL_COMPOSE_PREVIEW_URL` to set the URL for the preview links.

## Extending the Drupal UI field formatter

You can overwrite the Drupal field template in your admin/editor theme. See:

- `templates/token-preview-iframe.html.twig`
- `templates/token-preview-link.html.twig`

## Creating your own Drupal UI formatter

Try creating your own Drupal `FieldFormatter` plugin for the `preview_token` field type.

```php
<?php

declare(strict_types=1);

namespace Drupal\your_module_here\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'preview_token_whatever' formatter.
 *
 * @FieldFormatter(
 *   id = "preview_token_whatever",
 *   label = @Translation("Token preview whatever"),
 *   field_types = {
 *     "preview_token",
 *   },
 * )
 */
class PreviewTokenLinkFormatter extends FormatterBase {
  // ... Whatever you want.
}
```
