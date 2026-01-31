# Media Album Light Table Style Module

## Description

Dedicated Drupal module that extracts and isolates the **Media Album Light Table Views Style** plugin from the `media_album_av_common` module. This module provides a specialized Views style plugin for rendering and managing media items in a responsive, drag-and-drop enabled light table layout.

## Features

- **Views Style Plugin**: Custom `media_album_light_table` style for Views
- **Drag-and-Drop Support**: Reorder media items with Dragula or SortableJS
- **Responsive Grid**: Configurable columns and gaps for flexible layouts
- **Field Mapping**: Zone-based configuration for media album items
  - Thumbnail zone
  - VBO Actions zone
  - Name zone
  - Media Details zone
  - Action zone
  - Preview zone
- **Media Details Popup**: Display detailed information about media files
- **Multi-level Grouping**: Support for hierarchical grouping structures
- **MIME Type Detection**: Automatic detection for different media types (images, videos, documents)

## Structure

```
media_album_light_table_style/
├── src/
│   ├── Plugin/
│   │   └── views/style/
│   │       └── MediaAlbumLightTableStyle.php      # Main style plugin
│   └── Traits/
│       └── MediaTrait.php                         # Media utility trait
├── templates/
│   ├── views-view-media-album-light-table.html.twig  # Main template
│   └── media-light-table-content.html.twig        # Content template
├── css/
│   ├── media-light-table.css
│   ├── media-album-light-table.css
│   ├── media-light-table-controls.css
│   ├── media-light-table-modal.css
│   ├── draggable-flexgrid-light-table-groups.css
│   └── draggable-flexgrid-light-table-selection.css
├── js/
│   ├── media-light-table.js
│   ├── media-light-table-filters.js
│   ├── media-light-table-more-info.js
│   ├── media-light-table-modal.js
│   └── draggable-flexgrid-light-table-selection.js
├── media_album_light_table_style.info.yml   # Module metadata
├── media_album_light_table_style.module     # Module hooks
└── media_album_light_table_style.libraries.yml  # Asset definitions
```

## Dependencies

- **drupal:media** - Core media entity module
- **drupal:field** - Core field system
- **drupal:taxonomy** - Core taxonomy system
- **drupal:views** - Core Views module
- **media_album_av_common** - Provides shared utilities and dependencies

## Installation

1. Copy this module to your custom modules directory: `/web/modules/custom/`
2. Enable the module:
   ```bash
   drush en media_album_light_table_style -y
   ```
3. Clear the Drupal cache:
   ```bash
   drush cr
   ```

## Usage

### Adding to a View

1. Create or edit a View that displays media entities
2. In the view settings, add a Relationship to the media reference field
3. Go to **Format** and select **Media Album Light Table**
4. Configure the style plugin options:
   - Number of columns (1-12)
   - Gap between items (CSS value)
   - Thumbnail style
   - Justify content (flex alignment)
   - Align items (flex alignment)
   - Responsive grid toggle
   - Field/zone mapping

### Field/Zone Mapping Configuration

The plugin allows mapping different fields to specific zones in the light table:

- **Thumbnail Zone**: Displays the media thumbnail image
- **VBO Actions Zone**: Shows Views Bulk Operations checkboxes and actions
- **Name Zone**: Displays a text field for the item name
- **Media Details Zone**: Popup with file information (ID, path, size, MIME type, dimensions)
- **Action Zone**: Custom action links or buttons
- **Preview Zone**: Zoom button to view full-size media

## JavaScript Libraries

The module uses the following JavaScript libraries:

- **Dragula** (3.7.3): Drag-and-drop functionality
- **SortableJS** (1.15.0): Alternative sorting mechanism
- **Custom Scripts**:
  - `media-light-table.js`: Core light table functionality
  - `media-light-table-filters.js`: Filtering capabilities
  - `media-light-table-more-info.js`: Details popup handling
  - `media-light-table-modal.js`: Modal preview functionality
  - `draggable-flexgrid-light-table-selection.js`: Multi-item selection

## CSS Styling

Customizable CSS classes for styling:

- `.media-light-table` - Main container
- `.media-light-table-media-item` - Individual media item
- `.media-light-table-thumbnail` - Thumbnail area
- `.media-light-table-group-*` - Group containers
- `.draggable-flexgrid__*` - Flexgrid layout classes

## API Reference

### MediaAlbumLightTableStyle Class

Main Views style plugin class providing:

- `buildOptionsForm()` - Configure style options
- `validateOptionsForm()` - Validate user input
- `render()` - Generate the render array
- `getMediaFullInfo($index)` - Get complete media information
- `getMediaImageSize($index, $field_id)` - Get image dimensions
- `getMediaThumbnail($media, $style_name)` - Get thumbnail URL

### MediaTrait Class

Utility trait providing media-related functions:

- `getMediaThumbnail()` - Generate thumbnail data
- `getMediaEntity()` - Retrieve media entity from row
- `getReferencedMediaEntity()` - Get media from relationships
- `getMediaRowFullInfo()` - Retrieve complete media metadata
- `getThumbnailSize()` - Get image style dimensions

## Configuration Example

```yaml
# In Views UI, configure the Media Album Light Table style:
Columns: 4
Gap: 20px
Image Thumbnail Style: medium
Justify Content: space-around
Align Items: center
Responsive Grid: true
Field/Zone Mapping:
  Thumbnail: Enabled
  VBO Actions: Enabled (select field)
  Name: Enabled (select field)
  Media Details: Enabled
  Action: Disabled
  Preview: Enabled
```

## Performance Notes

- Media information is cached in the template to avoid re-computation
- Lazy loading of additional details on demand (popup)
- Efficient DOM manipulation using Dragula/SortableJS
- CSS Grid/Flexbox for responsive layout

## Browser Support

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile browsers with Flexbox support

## License

This module is part of the Drupal Media Album ecosystem and follows the same license as the parent project.

## Contributing

For bugs, feature requests, or questions about this module, please refer to the main `media_album_av_common` module documentation.

## Related Modules

- **media_album_av_common**: Base module with shared functionality
- **media_album_av**: Main media album module
- **media_drop**: Media drop and management features
