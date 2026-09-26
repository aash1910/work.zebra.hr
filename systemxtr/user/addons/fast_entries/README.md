# Fast Entries Plugin for ExpressionEngine

A high-performance SQL-based alternative to `{exp:channel:entries}` for ExpressionEngine 7.5+ (PHP 8.2)

## Installation

1. Upload the `fast_entries` folder to `system/user/addons/`
2. Go to Developer → Add-Ons in your ExpressionEngine control panel
3. Install the "Fast Entries" plugin

## Folder Structure

```
system/user/addons/fast_entries/
├── addon.setup.php
├── pi.fast_entries.php
└── logs/
    ├── .gitkeep
    └── fast_entries.log (created automatically)
```

## Quick Start

Replace `{exp:channel:entries}` with `{exp:fast_entries}` for faster performance:

```ee
{exp:fast_entries channel="products" limit="16"}
    {embed="partials/product-card" entry_id="{entry_id}"}
{/exp:fast_entries}
```

## Available Parameters

| Parameter | Default | Description |
|-----------|---------|-------------|
| `channel` | *required* | Channel short name (supports multiple: `channel="products\|blog"`) |
| `status` | `open` | Entry status (supports multiple: `status="open\|featured"`) |
| `limit` | `16` | Number of entries per page |
| `orderby` | `date` | Order field(s): `date`, `title`, or field ID (supports multiple: `orderby="5\|date"`) |
| `sort` | `desc` | Sort direction: `asc` or `desc` (supports multiple: `sort="asc\|desc"`) |
| `paginate` | `yes` | Enable auto-pagination: `yes` or `no` |

## Pagination (EE-Style)

Pagination uses EE's standard URL format:

- **Page 1**: `/products` (offset 0)
- **Page 2**: `/products/P16` (offset 16)
- **Page 3**: `/products/P32` (offset 32)
- **Page 4**: `/products/P48` (offset 48)

The offset = `(page_number - 1) × limit`

### Auto Pagination (Recommended)

```ee
{exp:fast_entries channel="products" limit="16"}
    {embed="partials/product-card" entry_id="{entry_id}"}
{/exp:fast_entries}
{!-- Pagination automatically appears here --}
```

### Manual Pagination

```ee
{exp:fast_entries channel="products" limit="16" paginate="no"}
    {embed="partials/product-card" entry_id="{entry_id}"}
{/exp:fast_entries}

{!-- Place pagination wherever you want --}
{exp:fast_entries:pagination}
```

## Category Filtering

Categories are **automatically** detected from URL segment 3:

```
yoursite.com/products/electronics
                      ^^^^^^^^^^^ (segment 3)
```

No category parameters needed - just use clean URLs!

## Sorting Examples

### Sort by Date
```ee
{exp:fast_entries channel="blog" orderby="date" sort="desc"}
```

### Sort by Title
```ee
{exp:fast_entries channel="products" orderby="title" sort="asc"}
```

### Sort by View Count
```ee
{!-- Sort by most viewed --}
{exp:fast_entries channel="products" orderby="view_count_one" sort="desc"}

{!-- Multiple view count fields --}
{exp:fast_entries channel="blog" orderby="view_count_two" sort="desc"}
```

Available view count fields: `view_count_one`, `view_count_two`, `view_count_three`, `view_count_four`

### Sort by Custom Field
Use the field ID (find in Developer → Fields):

```ee
{!-- Sort by price field (ID: 5) --}
{exp:fast_entries channel="products" orderby="5" sort="asc"}
```

### Multiple Sort Criteria
```ee
{!-- Sort by featured (field 8) DESC, then price (field 5) ASC, then date DESC --}
{exp:fast_entries 
    channel="products" 
    orderby="8|5|date" 
    sort="desc|asc|desc"
}

{!-- Sort by view count, then date --}
{exp:fast_entries 
    channel="blog" 
    orderby="view_count_one|date" 
    sort="desc|desc"
}
```

## Available Variables

- `{entry_id}` - The entry ID (use to pass to embeds/partials)
- `{count}` - Loop counter (1, 2, 3... resets each page)
- `{absolute_count}` - Absolute count across pages (17, 18, 19... on page 2)
- `{total_results}` - Total number of entries matching your filters (available in loop)

## Displaying Total Results

### Inside the Loop
```ee
{exp:fast_entries channel="products" limit="16"}
    {if count == 1}
        <p>Showing {total_results} products</p>
    {/if}
    {embed="partials/product-card" entry_id="{entry_id}"}
{/exp:fast_entries}
```

### Outside the Loop
```ee
{exp:fast_entries channel="products" limit="16"}
    {embed="partials/product-card" entry_id="{entry_id}"}
{/exp:fast_entries}

<p>Total: {exp:fast_entries:total_results} products found</p>
```

### Showing Range (e.g., "Showing 17-32 of 150")
```ee
{exp:fast_entries channel="products" limit="16"}
    {if count == 1}
        <p>Showing {absolute_count}-{exp:math add="{absolute_count}" operand="{count}"}{/exp:math} of {total_results} products</p>
    {/if}
    {embed="partials/product-card" entry_id="{entry_id}"}
{/exp:fast_entries}
```

Or simpler without exp:math:
```ee
{exp:fast_entries channel="products" limit="16"}
    {if count == 1}
        {!-- Calculate range based on current page --}
        <p>Showing results 1-{total_results} products total</p>
    {/if}
    {embed="partials/product-card" entry_id="{entry_id}"}
{/exp:fast_entries}
```

## No Results

```ee
{exp:fast_entries channel="products"}
    {entry_id}
{if no_results}
    <p>No products found.</p>
{/if}
{/exp:fast_entries}
```

## Pagination Styling

The pagination uses **Bootstrap 4/5 classes** and works out-of-the-box if you have Bootstrap loaded:

```html
<ul class="pagination">
    <li class="page-item"><a href="#" class="page-link">...</a></li>
    <li class="page-item active"><a href="#" class="page-link">1</a></li>
    <li class="page-item disabled"><span class="page-link">...</span></li>
</ul>
```

### If You're Using Bootstrap
No additional CSS needed! Just make sure Bootstrap is loaded in your template.

### If You're NOT Using Bootstrap
Add this minimal CSS to style the pagination:

```html
<style>
.pagination {
    display: flex;
    list-style: none;
    padding: 0;
    gap: 0.25rem;
}
.page-item {
    display: inline-block;
}
.page-link {
    padding: 0.5rem 0.75rem;
    text-decoration: none;
    color: #007bff;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
}
.page-item.active .page-link {
    background: #007bff;
    color: white;
    border-color: #007bff;
}
.page-item.disabled .page-link {
    color: #6c757d;
    pointer-events: none;
    background-color: #fff;
}
</style>
```

## Complete Example

```ee
{!-- URL: /products/electronics/P32 (page 3) --}
{exp:fast_entries 
    channel="products" 
    status="open|featured"
    limit="24"
    orderby="5|date"
    sort="asc|desc"
}
    {partial:product-card entry_id="{entry_id}"}
    
{if no_results}
    <p>No products found.</p>
{/if}
{/exp:fast_entries}
```

**In `partials/product-card.html`:**

```ee
{exp:channel:entries entry_id="{embed:entry_id}" limit="1"}
<div class="product">
    <h3>{title}</h3>
    <img src="{product_image}" alt="{title}">
    <p class="price">${product_price}</p>
</div>
{/exp:channel:entries}
```

## How to Find Custom Field IDs

1. Go to **Developer → Fields** in EE
2. Click your field group
3. URL shows ID: `cp/fields/edit/12` ← Field ID is **12**
4. Or check database table: `exp_channel_fields`

## Debugging

Log file: `system/user/addons/fast_entries/logs/fast_entries.log`

Shows:
- SQL queries executed
- Total row counts
- Offset/limit values

## Performance Benefits

1. **Direct SQL** - Bypasses EE's parser overhead
2. **Minimal data** - Only fetches entry IDs
3. **Deferred loading** - Load full entry data in embeds only
4. **Fast sorting** - Direct database sorting on custom fields

## Requirements

- ExpressionEngine 7.5+
- PHP 8.2+

## Support

For detailed examples, see `USAGE_EXAMPLES.md`
