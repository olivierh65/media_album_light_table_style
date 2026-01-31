# Quick Start: Media Album Light Table Style

## Installation (2 minutes)

### 1. Enable the Module
```bash
drush en media_album_light_table_style
drush cr
```

### 2. Verify Installation
Visit: `/admin/structure/views`

The "Media Album Light Table" style should now appear as an option.

---

## Basic Usage (5 minutes)

### Create a Test View

1. Go to **Structure** → **Views** → **Add view**
2. Configure:
   - **Name**: "Media Test"
   - **Show**: Media
   - **Create View**: checked
   - Click **Create**

### Configure the View Display

1. Under **Format**, select **Media Album Light Table**
2. Click **Settings** gear icon next to it
3. Configure options:
   - **Columns**: 3
   - **Gap**: 15px
   - **Image Thumbnail Style**: medium
   - **Justify Content**: space-around
   - **Align Items**: stretch
   - **Responsive Grid**: ✓ checked

### Add Fields

1. Click **Add** under Fields
2. Add: "Media → Thumbnail" or "Media → Name"
3. Configure field options if needed
4. Click **Update**

### Set Up Relationship (For Media Reference)

If you're displaying nodes with media references:

1. Click **Add** under Relationships
2. Select "Content → Media" (the reference field)
3. Check "Require this relationship"
4. Click **Add**

### Configure Field Mapping

In Style Settings → Field/zone mapping:
- **Thumbnail Zone**: Enabled (automatic)
- **Name Zone**: Select your name field
- **Media Details Zone**: Enabled (automatic)
- **Preview Zone**: Enabled (zoom button)

### Save and View

1. Click **Save**
2. Click **View**
3. Your media items should display in a responsive grid!

---

## Configuration Options

### Layout Options
- **Columns** (1-12): Number of columns in the grid
- **Gap** (CSS value): Space between items
  - Examples: "10px", "1rem", "2em", "5%"
- **Justify** (flex-start, center, space-between, etc): Horizontal alignment
- **Align** (stretch, center, flex-start, etc): Vertical alignment
- **Responsive**: Auto-adjust for screen size

### Thumbnail Options
- **Image Thumbnail Style**: Choose from available image styles
  - Commonly available: thumbnail, small, medium, large
  - Or create custom styles at `/admin/config/media/image-styles`

### Field Mapping Zones

#### 1. Thumbnail Zone
- **What**: Media thumbnail image
- **Configuration**: Auto-detected from media
- **Default**: Enabled

#### 2. VBO Actions Zone
- **What**: Views Bulk Operations checkboxes
- **Requires**: Views Bulk Operations module
- **How to enable**: 
  1. Select the VBO field from the dropdown
  2. Check "Enable VBO actions zone"

#### 3. Name Zone
- **What**: Text label for each item
- **How to enable**:
  1. Select field from "Name field (text field)" dropdown
  2. Check "Enable name zone"
- **Tip**: Use a text field or computed field

#### 4. Media Details Zone
- **What**: Popup with file information
  - File ID, path, size, MIME type, dimensions
- **How to enable**: Check "Enable media details zone"
- **Interaction**: Click "More..." button to view

#### 5. Action Zone
- **What**: Custom action links
- **How to enable**:
  1. Select field from "Action field" dropdown
  2. Check "Enable action zone"

#### 6. Preview Zone
- **What**: Zoom button for full-size preview
- **How to enable**: Check "Enable preview zone"
- **Interaction**: Click zoom icon to preview

---

## Common Recipes

### Recipe 1: Simple Media Gallery
```yaml
Columns: 4
Gap: 20px
Thumbnail Style: medium
Field Mapping:
  Thumbnail: Enabled
  Name: Enabled (use "Media → Name")
  Media Details: Enabled
  Preview: Enabled
```

### Recipe 2: Admin Media Manager
```yaml
Columns: 3
Gap: 15px
Thumbnail Style: thumbnail
Field Mapping:
  Thumbnail: Enabled
  VBO: Enabled (select "Views Bulk Operations")
  Name: Enabled
  Media Details: Enabled
  Action: Enabled (if using actions)
```

### Recipe 3: Album Browser
```yaml
Columns: 5
Gap: 10px
Thumbnail Style: small
Justify: space-evenly
Responsive: Enabled
Field Mapping:
  Thumbnail: Enabled
  Preview: Enabled
  Details: Disabled (keep it clean)
```

---

## Styling & Customization

### CSS Classes for Theming

```css
/* Main container */
.media-light-table { }
.draggable-flexgrid { }

/* Individual items */
.media-light-table-media-item { }
.draggable-flexgrid__item { }

/* Zones */
.media-light-table-thumbnail-field { }
.media-light-table-name-field { }
.media-light-table-vbo-field { }
.media-light-table-details-field { }
.media-light-table-action-field { }

/* Groups */
.media-light-table-group { }
.media-light-table-group-wrapper { }
.media-light-table-group-header { }
.media-light-table-group-title { }

/* Interactive elements */
.media-light-table-info-button { }
.media-light-table-zoom-trigger { }
.draggable-flexgrid__handle { }

/* States */
.empty-state { }
.sortable-ghost { }
.sortable-chosen { }
```

### Custom CSS Override

Create `themes/your_theme/css/media-light-table-custom.css`:

```css
/* Increase thumbnail size */
.media-light-table-thumbnail {
  width: 200px;
  height: 200px;
}

/* Custom name styling */
.media-light-table-name-field {
  font-weight: bold;
  color: #333;
  font-size: 14px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .draggable-flexgrid--4cols {
    display: grid !important;
    grid-template-columns: repeat(2, 1fr);
  }
}
```

Then declare in theme's `.libraries.yml`:
```yaml
custom-media-light-table:
  css:
    theme:
      css/media-light-table-custom.css: {}
  dependencies:
    - media_album_light_table_style/media-light-table
```

And attach in your template or view.

---

## Troubleshooting

### Style Option Not Showing

**Problem**: "Media Album Light Table" doesn't appear in Format dropdown

**Solution**:
1. Ensure module is enabled: `drush pml | grep media_album_light_table`
2. Clear cache: `drush cr`
3. Verify the View displays media entity type
4. Check console for JavaScript errors

### Drag-and-Drop Not Working

**Problem**: Items don't drag smoothly

**Solution**:
1. Enable JavaScript in browser
2. Check for JS errors in console
3. Verify Dragula/SortableJS libraries are loaded
4. Ensure not using conflicting CSS (z-index, position)
5. Clear browser cache

### Images Not Showing

**Problem**: Thumbnail images appear broken

**Solution**:
1. Verify media entities have thumbnails
2. Check image style exists: `/admin/config/media/image-styles`
3. Ensure file permissions are correct
4. Check file path in database
5. Regenerate image derivatives: `drush image-flush`

### Grouping Not Working

**Problem**: Items aren't grouped properly

**Solution**:
1. Add a Relationship for the grouping field
2. Select the grouping field in View filters
3. In Style Settings, configure grouping options
4. Ensure field has values in all items

---

## Performance Tips

1. **Limit Results**: Use pagers to limit items per page
   - Default pagination recommended: 20-50 items per page

2. **Use Appropriate Image Style**:
   - Smaller style = faster loading
   - `thumbnail` or `small` for lists
   - `medium` for galleries

3. **Disable Unused Zones**:
   - Turn off zones you don't need
   - Reduces DOM complexity

4. **Use Caching**:
   - Enable Views caching
   - Set cache duration appropriately

5. **Optimize Images**:
   - Use responsive images
   - WebP format if supported
   - Consider lazy loading

---

## Advanced Features

### Drag-and-Drop Integration

The light table supports drag-and-drop reordering:

```javascript
// Custom JavaScript to handle drop events
Drupal.behaviors.customMediaReorder = {
  attach: function(context) {
    document.addEventListener('dragula:drop', function(e) {
      console.log('Item dropped:', e.detail);
      // Your custom code here
    });
  }
};
```

### Bulk Operations

When VBO zone is enabled, you can:
- Select multiple items with checkboxes
- Choose bulk action from dropdown
- Execute action on all selected items
- Common actions: Delete, Change status, Set field value

### Filtering & Sorting

Add filters and sorting to refine results:
- Filter by media type
- Filter by upload date
- Sort by name, created date, etc.

---

## Module Dependencies

Required (auto-installed if using Composer):
- `drupal:media` ✓
- `drupal:field` ✓
- `drupal:views` ✓
- `drupal:taxonomy` ✓
- `media_album_av_common` ✓

Optional:
- `views_bulk_operations` - For VBO actions support

---

## Uninstallation

To remove the module:

```bash
# First, remove it from all views
# Then disable and uninstall
drush pmu media_album_light_table_style -y
drush pm:uninstall media_album_light_table_style -y

# Clear cache
drush cr
```

**Important**: Views using this style will fall back to default table style.

---

## Getting Help

### Documentation
- See `README.md` for complete feature documentation
- See `MIGRATION.md` for upgrade instructions
- See `EXTRACTION_SUMMARY.md` for technical details

### Debugging
Enable Drupal debug mode in `settings.php`:
```php
$settings['debug'] = TRUE;
$config['system.logging']['error_level'] = 'verbose';
```

### Reporting Issues
Provide:
- Drupal version
- Module version
- Steps to reproduce
- Error messages from logs
- Browser console errors

---

## Version Information

- **Module**: media_album_light_table_style
- **Version**: 1.0
- **Drupal**: 10.0+, 11.0+
- **PHP**: 8.1+

---

**Happy media managing! 🎉**
