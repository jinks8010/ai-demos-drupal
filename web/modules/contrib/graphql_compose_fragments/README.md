# GraphQL Compose Fragments

## Automatically create fragments

This module can help you quickly get up and running with GraphQL by auto-generating fragments. These fragments are intended as a guide, not a solution. Use them to quickly get started building your application.

## Expose fragments to the schema.

Optionally you can enable <em>fragments</em> on the schema, which will be added to the <em>info</em> query.

<code>
query {
  info {
    fragments {
      type
      name
      class
      content
    }
  }
}
</code>

## Installation

1. Install the module using composer:

   ```
   composer require drupal/graphql_compose_fragments
   ```

2. Enable the module:

   ```
   drush en graphql_compose_fragments
   ```

3. Enable fragments on the query at: `/admin/config/graphql_compose/fragments`
