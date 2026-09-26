# Total Results - Quick Examples

## Simple Display

### Show total count above results
```ee
{exp:fast_entries channel="products" limit="16"}
    {if count == 1}
        <div class="results-info">
            <h4>{total_results} Products Found</h4>
        </div>
    {/if}
    {embed="partials/product-card" entry_id="{entry_id}"}
{/exp:fast_entries}
```

### Show total count outside the loop
```ee
<div class="results-header">
    <h2>Products ({exp:fast_entries:total_results})</h2>
</div>

{exp:fast_entries channel="products" limit="16"}
    {embed="partials/product-card" entry_id="{entry_id}"}
{/exp:fast_entries}
```

## Advanced Examples

### Show current page range
```ee
{exp:fast_entries channel="products" limit="16"}
    {if count == 1}
        {!-- First item on current page --}
        <p>Showing item {absolute_count} of {total_results}</p>
    {/if}
    {embed="partials/product-card" entry_id="{entry_id}"}
{/exp:fast_entries}
```

### E-commerce style "Showing X-Y of Z results"
```ee
{exp:fast_entries channel="products" limit="24"}
    {if count == 1}
        {!-- Calculate start and end --}
        {!-- Start: absolute_count is the first item --}
        {!-- End: we need to calculate based on page --}
        <div class="showing-results">
            Showing {absolute_count}-{if total_results < absolute_count + 23}{total_results}{if:else}{exp:calculate operand1="{absolute_count}" operator="+" operand2="23"}{/exp:calculate}{/if} of {total_results} products
        </div>
    {/if}
    
    {embed="partials/product-card" entry_id="{entry_id}"}
{/exp:fast_entries}
```

### Simplified version (without complex math)
```ee
{exp:fast_entries channel="products" limit="24"}
    {if count == 1}
        <div class="results-count">
            <strong>{total_results}</strong> products available
        </div>
    {/if}
    {embed="partials/product-card" entry_id="{entry_id}"}
{/exp:fast_entries}
```

### With category name
```ee
{exp:fast_entries channel="products" limit="16"}
    {if count == 1}
        <h3>Electronics ({total_results} items)</h3>
    {/if}
    {embed="partials/product-card" entry_id="{entry_id}"}
{if no_results}
    <p>No products found in this category.</p>
{/if}
{/exp:fast_entries}
```

### Conditional messaging
```ee
{exp:fast_entries channel="products" limit="16"}
    {if count == 1}
        {if total_results == 1}
            <p>1 product found</p>
        {if:elseif total_results < 10}
            <p>Only {total_results} products available</p>
        {if:else}
            <p>{total_results} products available</p>
        {/if}
    {/if}
    {embed="partials/product-card" entry_id="{entry_id}"}
{/exp:fast_entries}
```

## Bootstrap Alert Example

```ee
{exp:fast_entries channel="products" limit="16"}
    {if count == 1}
        <div class="alert alert-info">
            Found <strong>{total_results}</strong> matching products
        </div>
    {/if}
    
    <div class="row">
        {embed="partials/product-card" entry_id="{entry_id}"}
    </div>
{/exp:fast_entries}
```

## Performance Note

The `{total_results}` variable uses the same count query that's already run for pagination, so there's **zero additional database overhead**. It's completely free performance-wise!

## When to Use Each Method

**Inside the loop** (`{total_results}` variable):
- ✅ When you want to show it once at the top of results
- ✅ Use with `{if count == 1}` to display only on first iteration
- ✅ Simpler syntax

**Outside the loop** (`{exp:fast_entries:total_results}` tag):
- ✅ When you need it in page header/title
- ✅ When displaying before the loop starts
- ✅ For breadcrumbs or meta information

Both methods are equally fast - use whichever fits your template structure better!
