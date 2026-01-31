# Migration Guide: media_album_light_table_style

## Overview

The `media_album_light_table_style` module has been extracted as a dedicated module from `media_album_av_common` to provide better modularity and separation of concerns.

## What Was Moved

### PHP Code
- `MediaAlbumLightTableStyle.php` - Views style plugin
- `MediaTrait.php` - Shared media utilities

### Templates (Twig)
- `views-view-media-album-light-table.html.twig` - Main light table template
- `media-light-table-content.html.twig` - Content-only template

### CSS Files
- `media-light-table.css`
- `media-album-light-table.css`
- `media-light-table-controls.css`
- `media-light-table-modal.css`
- `draggable-flexgrid-light-table-groups.css`
- `draggable-flexgrid-light-table-selection.css`

### JavaScript Files
- `media-light-table.js`
- `media-light-table-filters.js`
- `media-light-table-more-info.js`
- `media-light-table-modal.js`
- `draggable-flexgrid-light-table-selection.js`

### Libraries (YAML)
- All light table-specific libraries from `media_album_av_common.libraries.yml`

## Installation Steps

### 1. Enable the New Module

```bash
drush en media_album_light_table_style -y
```

### 2. Update Dependencies

If your custom modules depend on the light table style, update their `module.info.yml`:

```yaml
dependencies:
  - media_album_light_table_style
```

### 3. Clear Cache

```bash
drush cr
```

## Breaking Changes

None. The module is backward compatible because:
- The plugin ID remains `media_album_light_table`
- Template names are unchanged
- CSS/JS functionality is identical
- The module extends `media_album_av_common` as a dependency

## Views Configuration

Existing views using the `media_album_light_table` style will continue to work without modification:
- The plugin will be discovered from the new module
- Configuration is preserved
- Display output remains the same

## Namespace Changes

If you have custom code referencing the old namespace, update imports:

**Before:**
```php
use Drupal\media_album_av_common\Plugin\views\style\MediaAlbumLightTableStyle;
use Drupal\media_album_av_common\Traits\MediaTrait;
```

**After:**
```php
use Drupal\media_album_light_table_style\Plugin\views\style\MediaAlbumLightTableStyle;
use Drupal\media_album_light_table_style\Traits\MediaTrait;
```

## Library Dependencies

The new module references libraries from `media_album_av_common`:
```yaml
dragula:
  js:
    ../media_album_av_common/js/dragula/dragula.js: {}
```

This is intentional to avoid duplication. These libraries remain in the common module.

## Benefits of the Separation

1. **Modularity**: Light table functionality is now independent
2. **Reusability**: Can be enabled/disabled separately from other album functionality
3. **Maintainability**: Focused codebase for this specific feature
4. **Clarity**: Views style plugin purpose is immediately obvious
5. **Future Flexibility**: Can evolve independently from common module

## Troubleshooting

### Views Style Plugin Not Showing

1. Verify the module is enabled: `drush pml | grep media_album_light_table_style`
2. Clear Drupal cache: `drush cr`
3. Verify dependencies are met: `drush pml | grep media_album_av_common`

### CSS/JS Not Loading

1. Check if libraries are registered: `drush pml`
2. Verify template is applying libraries in render() method
3. Clear browser cache and Drupal cache
4. Check for console errors in browser developer tools

### Namespace Issues

If importing the plugin/trait fails:
1. Clear Drupal cache: `drush cr`
2. Verify class namespace matches new location
3. Verify autoloading is working: `composer dump-autoload`

## Rollback (If Needed)

To revert to the original integrated setup:

1. Disable the new module: `drush pmu media_album_light_table_style -y`
2. Copy code back to `media_album_av_common`
3. Clear cache: `drush cr`

## Support

For questions or issues with the migration, refer to:
- README.md in this module
- media_album_av_common module documentation
- Views documentation at drupal.org

## Version Compatibility

- **Drupal Core**: 10.0+, 11.0+
- **PHP**: 8.1+
- **Module Version**: 1.0 (initial extraction)
