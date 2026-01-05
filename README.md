# Geo Hierarchy Module

## Overview
`geo_hierarchy` provides a custom content entity (`geo_node`) for managing multi-country geographical hierarchies and a hierarchical field widget for selecting locations in entity reference fields.

## Requirements
- Drupal 10+
- Drush 11+ (optional, for the seed command)

## Installation
1. Place the module in your Drupal installation at `modules/custom/geo_hierarchy`.
2. Enable the module:
   ```bash
   drush en geo_hierarchy
   ```

## Create Geo Nodes
1. Navigate to **Content → Geo Nodes** (`/admin/content/geo-node`).
2. Add geo nodes in hierarchical order using the **Parent** field.
3. Supported types: `country`, `region`, `province`, `municipality`, `locality`, `area`.

## Use the Hierarchical Widget
1. Create or edit an **Entity Reference** field on your target entity (e.g., Property).
2. Set the **Target type** to **Geo Node**.
3. Under **Widget**, choose **Hierarchical Geo Select**.
4. When editing content, select the hierarchy from the dependent dropdowns.
   - The widget saves the lowest selected level as the reference.

## Seed Example Data (Optional)
Run the Drush command to seed a sample hierarchy:
```bash
drush geo-hierarchy:seed
```
This creates:
- Spain → Andalusia → Malaga → Nerja

## Services
The module provides `geo_hierarchy.manager` with:
- `getChildren(int $parentId)`
- `getAncestors(int $nodeId)`

## Permissions
Grant the **Administer geo hierarchy** permission to manage geo nodes.
