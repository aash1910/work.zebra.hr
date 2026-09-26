# Fast Entries - Usage Examples

## Basic Usage

### Simple Product Listing
```ee
{exp:fast_entries channel="products" limit="16"}
    {embed="partials/product-card" entry_id="{entry_id}"}
{if no_results}
    <p>No products found.</p>
{/if}
{/exp:fast_entries}
```

## Pagination Examples

### Example 1: Auto Pagination (Recommended)
The plugin automatically adds pagination at the bottom when `paginate="yes"` (default).

```ee
{exp:fast_entries channel="products" limit="16"}
    {embed="partials/product-card" entry_id="{entry_id}"}
{/exp:fast_entries}

{!-- Pagination automatically appears here --}
```

**URLs will be:**
- Page 1: `/products`
- Page 2: `/products/P16` (offset 16)
- Page 3: `/products/P32` (offset 32)
- Page 4: `/products/P48` (offset 48)

### Example 2: Manual Pagination Placement
If you want more control over where pagination appears:

```ee
{exp:fast_entries channel="products" limit="16" paginate="no"}
    {embed="partials/product-card" entry_id="{entry_id}"}
{/exp:fast_entries}

{!-- Place pagination anywhere you want --}
{exp:fast_entries:pagination}
```

### Example 3: With Category Filter
Categories are auto-detected from segment 3:

```ee
{!-- URL: /products/electronics/P32 --}
{exp:fast_entries channel="products" limit="16"}
    {embed="partials/product-card" entry_id="{entry_id}"}
{/exp:fast_entries}

{!-- Automatically filters by "electronics" category --}
{!-- Shows page 3 (offset 32) --}
```

## Sorting Examples

### Example 4: Sort by Custom Field
```ee
{!-- Sort by custom field ID 5 (e.g., price) descending --}
{exp:fast_entries channel="products" orderby="5" sort="asc" limit="20"}
    {embed="partials/product-card" entry_id="{entry_id}"}
{/exp:fast_entries}
```

### Example 5: Multiple Sort Criteria
```ee
{!-- Sort by custom field 8 (featured) DESC, then by date DESC --}
{exp:fast_entries 
    channel="products" 
    orderby="8|date" 
    sort="desc|desc" 
    limit="24"
}
    {embed="partials/product-card" entry_id="{entry_id}"}
{/exp:fast_entries}
```

### Example 6: Sort by Title
```ee
{!-- Alphabetical A-Z --}
{exp:fast_entries channel="products" orderby="title" sort="asc" limit="50"}
    <h3>{count}. {embed="partials/product-name" entry_id="{entry_id}"}</h3>
{/exp:fast_entries}
```

### Example 7: Multiple Custom Fields + Date
```ee
{!-- Sort by: Stock status (field 12), Price (field 5), then Date --}
{exp:fast_entries 
    channel="products" 
    orderby="12|5|date" 
    sort="desc|asc|desc" 
    limit="16"
}
    {embed="partials/product-card" entry_id="{entry_id}"}
{/exp:fast_entries}
```

## Multiple Channels

### Example 8: Combine Multiple Channels
```ee
{exp:fast_entries 
    channel="products|services|downloads" 
    status="open|featured"
    limit="20"
    orderby="date"
    sort="desc"
}
    {embed="partials/entry-card" entry_id="{entry_id}"}
{/exp:fast_entries}
```

## Advanced Examples

### Example 9: Blog with Categories and Pagination
```ee
{!-- URL: /blog/tutorials/P48 --}
{exp:fast_entries 
    channel="blog" 
    status="open" 
    limit="12"
    orderby="date"
    sort="desc"
}
    <article>
        {exp:channel:entries entry_id="{entry_id}" limit="1"}
            <h2><a href="{url_title_path='blog/view'}">{title}</a></h2>
            <p class="meta">Posted on {entry_date format="%F %d, %Y"}</p>
            {blog_excerpt}
        {/exp:channel:entries}
    </article>
{if no_results}
    <p>No blog posts found in this category.</p>
{/if}
{/exp:fast_entries}
```

### Example 10: E-commerce Product Grid
```ee
{exp:fast_entries 
    channel="products" 
    status="open|sale"
    limit="24"
    orderby="5|date"
    sort="asc|desc"
}
    {!-- Using partial instead of embed (EE 7.5+) --}
    {partial:product-grid-item entry_id="{entry_id}"}
{/exp:fast_entries}
```

**Then in `partials/product-grid-item.html`:**
```ee
{exp:channel:entries entry_id="{embed:entry_id}" limit="1"}
<div class="product-card">
    <a href="{url_title_path='products/detail'}">
        <img src="{product_image}" alt="{title}">
        <h3>{title}</h3>
        <p class="price">${product_price}</p>
        {if product_sale_price}
            <span class="badge">SALE</span>
        {/if}
    </a>
</div>
{/exp:channel:entries}
```

## Using Count Variables

### Example 11: Row Classes and Counters
```ee
{exp:fast_entries channel="products" limit="16"}
    <div class="product-item row-{count} {if count == 1}first{/if}">
        Entry #{absolute_count}: 
        {embed="partials/product-card" entry_id="{entry_id}"}
    </div>
{/exp:fast_entries}
```

**Available variables:**
- `{entry_id}` - The entry ID
- `{count}` - Loop counter (1, 2, 3... resets each page)
- `{absolute_count}` - Absolute count across all pages (17, 18, 19... on page 2)

## CSS Placement

### Option 1: In Template Head
```html
{!-- In your template or layout --}
<head>
    <style>
        /* Fast Entries Pagination */
        .pagination {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            justify-content: center;
            margin-top: 2rem;
            padding: 1rem 0;
        }
        
        .pagination__prev,
        .pagination__next,
        .pagination__page {
            padding: 0.5rem 1rem;
            text-decoration: none;
            color: #333;
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        .pagination__prev:hover,
        .pagination__next:hover,
        .pagination__page:hover {
            background: #e0e0e0;
            border-color: #ccc;
        }
        
        .pagination__current {
            padding: 0.5rem 1rem;
            background: #007bff;
            color: white;
            border: 1px solid #007bff;
            border-radius: 4px;
            font-weight: 600;
        }
        
        .pagination__ellipsis {
            padding: 0.5rem;
            color: #999;
        }
    </style>
</head>
```

### Option 2: In External CSS File
Save the pagination CSS to: `themes/user/your-site/css/pagination.css`

```html
<head>
    <link rel="stylesheet" href="{path='css/pagination.css'}">
</head>
```

### Option 3: Using EE's CSS Include
```ee
{!-- In your template --}
{exp:channel:entries channel="pages" url_title="current-page"}
    {stylesheet}
{/exp:channel:entries}
```

## Complete Real-World Example

```ee
<!DOCTYPE html>
<html>
<head>
    <title>Product Catalog</title>
    <style>
        /* Pagination CSS here */
        .pagination { /* ... */ }
    </style>
</head>
<body>
    <header>
        <h1>Our Products</h1>
        <nav>
            <a href="/products">All</a>
            <a href="/products/electronics">Electronics</a>
            <a href="/products/clothing">Clothing</a>
        </nav>
    </header>

    <main>
        <div class="product-grid">
            {exp:fast_entries 
                channel="products" 
                status="open"
                limit="24"
                orderby="5|date"
                sort="asc|desc"
            }
                {partial:product-card entry_id="{entry_id}"}
            
            {if no_results}
                <div class="no-results">
                    <p>No products found in this category.</p>
                    <a href="/products">View all products</a>
                </div>
            {/if}
            {/exp:fast_entries}
        </div>
        
        {!-- Pagination auto-appears here --}
    </main>
</body>
</html>
```

## How to Find Custom Field IDs

To use custom fields in `orderby`, you need the field ID:

1. Go to **Developer → Fields** in EE control panel
2. Click on your field group
3. The URL will show the field ID: `cp/fields/edit/12` ← Field ID is 12
4. Or look in the database: `exp_channel_fields` table

## Debugging

Check the log file at `system/user/addons/fast_entries/logs/fast_entries.log` to see:
- SQL queries being executed
- Total row counts
- Offset and limit values

This helps troubleshoot pagination and sorting issues.
